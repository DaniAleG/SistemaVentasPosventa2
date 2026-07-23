<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/session_init.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['estado' => 'error', 'mensaje' => 'Metodo no permitido'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!isset($_SESSION['usuario_activo'])) {
    http_response_code(401);
    echo json_encode(['estado' => 'error', 'mensaje' => 'Debes iniciar sesion para registrar una venta'], JSON_UNESCAPED_UNICODE);
    exit;
}

$rawInput = file_get_contents('php://input');
$input = $rawInput !== false && $rawInput !== '' ? json_decode($rawInput, true) : null;

if (!is_array($input) || json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['estado' => 'error', 'mensaje' => 'JSON invalido en la solicitud'], JSON_UNESCAPED_UNICODE);
    exit;
}

$items = $input['items'] ?? [];
if (!is_array($items) || count($items) === 0) {
    http_response_code(422);
    echo json_encode(['estado' => 'error', 'mensaje' => 'La venta no tiene productos'], JSON_UNESCAPED_UNICODE);
    exit;
}

$clienteId = isset($input['cliente_id']) && is_numeric($input['cliente_id']) ? (int) $input['cliente_id'] : 0;
$clienteCedula = trim((string) ($input['cliente_cedula'] ?? '9999999999999'));
$clienteNombre = trim((string) ($input['cliente_nombre'] ?? 'Consumidor Final'));
$clienteCorreo = trim((string) ($input['cliente_correo'] ?? ''));

// Normalizamos los items recibidos
$itemsNormalizados = [];
foreach ($items as $item) {
    $productoId = isset($item['producto_id']) ? (int) $item['producto_id'] : 0;
    $cantidad = isset($item['cantidad']) ? (int) $item['cantidad'] : 0;

    if ($productoId <= 0 || $cantidad <= 0) {
        http_response_code(422);
        echo json_encode(['estado' => 'error', 'mensaje' => 'Hay un producto invalido en el carrito'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $itemsNormalizados[] = ['producto_id' => $productoId, 'cantidad' => $cantidad];
}

try {
    $pdo->beginTransaction();

    // 1) Resolver el cliente. Si no viene un id valido, buscamos o creamos "Consumidor Final".
    if ($clienteId > 0) {
        $stmtCliente = $pdo->prepare('SELECT id FROM clientes WHERE id = ?');
        $stmtCliente->execute([$clienteId]);
        if (!$stmtCliente->fetch()) {
            $clienteId = 0; // el id que mando el frontend ya no existe, caemos al flujo de consumidor final
        }
    }

    if ($clienteId <= 0) {
        $stmtBuscar = $pdo->prepare('SELECT id FROM clientes WHERE cedula = ? LIMIT 1');
        $stmtBuscar->execute([$clienteCedula]);
        $clienteExistente = $stmtBuscar->fetch();

        if ($clienteExistente) {
            $clienteId = (int) $clienteExistente['id'];
        } else {
            $correoGenerico = $clienteCorreo !== '' ? $clienteCorreo : 'consumidor.final@posventa.local';
            $stmtCrear = $pdo->prepare('INSERT INTO clientes (cedula, nombre_completo, correo) VALUES (?, ?, ?)');
            $stmtCrear->execute([$clienteCedula, $clienteNombre !== '' ? $clienteNombre : 'Consumidor Final', $correoGenerico]);
            $clienteId = (int) $pdo->lastInsertId();
        }
    }

    // 2) Determinar el porcentaje de descuento del cliente según el monto monetario
    // TOTAL que ha comprado históricamente (suma de sus ventas anteriores).
    // Reglas: desde $2000 acumulados => 10%, desde $5000 acumulados => 15%.
    // El Consumidor Final (cédula genérica) nunca accede al descuento.
    $descuentoPorcentaje = 0.0;
    $stmtCedulaCliente = $pdo->prepare('SELECT cedula FROM clientes WHERE id = ?');
    $stmtCedulaCliente->execute([$clienteId]);
    $filaCliente = $stmtCedulaCliente->fetch();
    $cedulaClienteActual = (string) ($filaCliente['cedula'] ?? '');

    if ($cedulaClienteActual !== '9999999999999') {
        $stmtGastoCliente = $pdo->prepare('SELECT COALESCE(SUM(total_factura), 0) AS total_gastado FROM ventas WHERE cliente_id = ?');
        $stmtGastoCliente->execute([$clienteId]);
        $filaGasto = $stmtGastoCliente->fetch();
        $totalGastadoPrevio = (float) ($filaGasto['total_gastado'] ?? 0);

        if ($totalGastadoPrevio >= 5000) {
            $descuentoPorcentaje = 0.15;
        } elseif ($totalGastadoPrevio >= 2000) {
            $descuentoPorcentaje = 0.10;
        }
    }

    // 3) Validar stock y calcular el total en el servidor (no confiamos en el total que manda el navegador)
    $stmtProducto = $pdo->prepare('SELECT id, precio_actual, stock_disponible FROM productos WHERE id = ? FOR UPDATE');
    $totalCalculado = 0.0;
    $detalleFinal = [];

    foreach ($itemsNormalizados as $item) {
        $stmtProducto->execute([$item['producto_id']]);
        $producto = $stmtProducto->fetch();

        if (!$producto) {
            throw new RuntimeException('Un producto del carrito ya no existe (id ' . $item['producto_id'] . ')');
        }

        if ((int) $producto['stock_disponible'] < $item['cantidad']) {
            throw new RuntimeException('Stock insuficiente para el producto id ' . $item['producto_id']);
        }

        $precioCongelado = (float) $producto['precio_actual'];
        $subtotalItem = $precioCongelado * $item['cantidad'];
        $totalCalculado += $subtotalItem;

        $detalleFinal[] = [
            'producto_id' => $item['producto_id'],
            'cantidad' => $item['cantidad'],
            'precio_congelado' => $precioCongelado,
        ];
    }

    $descuento = $descuentoPorcentaje > 0 ? round($totalCalculado * $descuentoPorcentaje, 2) : 0.0;
    $baseImponible = $totalCalculado - $descuento;
    $iva = $baseImponible * 0.15;
    $totalFactura = round($baseImponible + $iva, 2);

    // 4) Insertar la cabecera de la venta (incluye el cajero que la registro)
    $usuarioId = (int) ($_SESSION['usuario_activo']['id'] ?? 0);

    $stmtVenta = $pdo->prepare('INSERT INTO ventas (cliente_id, usuario_id, total_factura, fecha_emision) VALUES (?, ?, ?, NOW())');
    $stmtVenta->execute([$clienteId, $usuarioId > 0 ? $usuarioId : null, $totalFactura]);
    $ventaId = (int) $pdo->lastInsertId();

    // 5) Insertar el detalle y descontar stock
    $stmtDetalle = $pdo->prepare('INSERT INTO detalles_venta (venta_id, producto_id, cantidad, precio_congelado) VALUES (?, ?, ?, ?)');
    $stmtStock = $pdo->prepare('UPDATE productos SET stock_disponible = stock_disponible - ? WHERE id = ? AND stock_disponible >= ?');

    foreach ($detalleFinal as $detalle) {
        $stmtDetalle->execute([$ventaId, $detalle['producto_id'], $detalle['cantidad'], $detalle['precio_congelado']]);

        $stmtStock->execute([$detalle['cantidad'], $detalle['producto_id'], $detalle['cantidad']]);
        if ($stmtStock->rowCount() === 0) {
            throw new RuntimeException('No se pudo descontar el stock del producto id ' . $detalle['producto_id']);
        }
    }

    $pdo->commit();

    echo json_encode([
        'estado' => 'exito',
        'mensaje' => 'Venta registrada correctamente',
        'venta' => [
            'id' => $ventaId,
            'cliente_id' => $clienteId,
            'subtotal' => round($totalCalculado, 2),
            'descuento_porcentaje' => $descuentoPorcentaje,
            'descuento' => $descuento,
            'iva' => round($iva, 2),
            'total_factura' => $totalFactura,
        ],
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $throwable) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(422);
    echo json_encode([
        'estado' => 'error',
        'mensaje' => $throwable->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}