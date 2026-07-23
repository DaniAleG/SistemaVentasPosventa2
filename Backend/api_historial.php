<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/conexion.php';

function fechaValida(string $fecha): bool
{
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
        return false;
    }

    [$anio, $mes, $dia] = array_map('intval', explode('-', $fecha));
    return checkdate($mes, $dia, $anio);
}

function normalizarFecha(?string $fecha): ?string
{
    if ($fecha === null || $fecha === '') {
        return null;
    }

    return fechaValida($fecha) ? $fecha : null;
}

function ejecutarConsultaHistorial(PDO $pdo_posventa, string $sql, array $parametros): ?array
{
    try {
        $stmt = $pdo_posventa->prepare($sql);
        $stmt->execute($parametros);
        $filas = $stmt->fetchAll();
        return is_array($filas) ? $filas : [];
    } catch (Throwable $throwable) {
        return null;
    }
}

function obtenerColumnasTabla(PDO $pdo_posventa, string $tabla): array
{
    $stmt = $pdo_posventa->prepare('SHOW COLUMNS FROM `' . $tabla . '`');
    $stmt->execute();
    $columnas = [];

    foreach ($stmt->fetchAll() as $fila) {
        $nombre = (string) ($fila['Field'] ?? '');
        if ($nombre !== '') {
            $columnas[$nombre] = true;
        }
    }

    return $columnas;
}

function primeraColumnaDisponible(array $columnas, array $opciones): ?string
{
    foreach ($opciones as $columna) {
        if (isset($columnas[$columna])) {
            return $columna;
        }
    }

    return null;
}

function tablaExiste(PDO $pdo_posventa, string $tabla): bool
{
    try {
        $pdo_posventa->query('SELECT 1 FROM `' . $tabla . '` LIMIT 1');
        return true;
    } catch (Throwable $throwable) {
        return false;
    }
}

try {
    $fechaInicio = normalizarFecha((string) ($_GET['fecha_inicio'] ?? ''));
    $fechaFin = normalizarFecha((string) ($_GET['fecha_fin'] ?? ''));
    $filtroCliente = trim((string) ($_GET['cliente'] ?? ''));
    $filtroFactura = trim((string) ($_GET['factura'] ?? ''));

    if ((isset($_GET['fecha_inicio']) && $_GET['fecha_inicio'] !== '' && $fechaInicio === null) ||
        (isset($_GET['fecha_fin']) && $_GET['fecha_fin'] !== '' && $fechaFin === null)) {
        http_response_code(422);
        echo json_encode([
            'estado' => 'error',
            'mensaje' => 'Formato de fecha inválido. Usa AAAA-MM-DD.',
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $tablaCabecera = null;
    foreach (['facturas', 'ventas'] as $candidata) {
        if (tablaExiste($pdo_posventa, $candidata)) {
            $tablaCabecera = $candidata;
            break;
        }
    }

    if ($tablaCabecera === null) {
        echo json_encode([
            'estado' => 'ok',
            'resumen' => [
                'total_vendido' => 0,
                'cantidad_facturas' => 0,
                'ticket_promedio' => 0,
            ],
            'registros' => [],
            'mensaje' => 'No se encontró una tabla de cabeceras de venta (facturas/ventas).',
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $colCabecera = obtenerColumnasTabla($pdo_posventa, $tablaCabecera);
    $idCol = primeraColumnaDisponible($colCabecera, ['id', 'id_factura', 'id_venta']) ?? 'id';
    $fechaCol = primeraColumnaDisponible($colCabecera, ['fecha_emision', 'fecha_venta', 'fecha', 'created_at']) ?? 'fecha';
    $totalCol = primeraColumnaDisponible($colCabecera, ['total_factura', 'total', 'total_venta', 'monto_total', 'valor_total']) ?? 'total_factura';
    $numeroCol = primeraColumnaDisponible($colCabecera, ['numero_factura', 'numero', 'nro_factura', 'numero_venta', 'nro_venta']);
    $estadoCol = primeraColumnaDisponible($colCabecera, ['estado', 'estado_factura', 'situacion', 'anulada']);
    $tipoCol = primeraColumnaDisponible($colCabecera, ['tipo_factura', 'tipo_documento', 'tipo_comprobante', 'comprobante']);
    $clienteFkCol = primeraColumnaDisponible($colCabecera, ['cliente_id', 'id_cliente']);
    $usuarioFkCol = primeraColumnaDisponible($colCabecera, ['usuario_id', 'id_usuario', 'vendedor_id', 'cajero_id']);

    $joinCliente = '';
    $exprCliente = "'Consumidor Final'";
    $filtroClienteSql = '';
    $filtroClienteParams = [];

    if ($clienteFkCol !== null && tablaExiste($pdo_posventa, 'clientes')) {
        $colCliente = obtenerColumnasTabla($pdo_posventa, 'clientes');
        $clienteIdCol = primeraColumnaDisponible($colCliente, ['id', 'id_cliente']);
        $clienteNombreCol = primeraColumnaDisponible($colCliente, ['nombre_completo', 'nombre', 'nombres']);
        $clienteCedulaCol = primeraColumnaDisponible($colCliente, ['cedula', 'documento', 'identificacion', 'dni', 'ruc']);

        if ($clienteIdCol !== null && $clienteNombreCol !== null) {
            $joinCliente = " LEFT JOIN clientes c ON c.`$clienteIdCol` = v.`$clienteFkCol`";
            $exprCliente = "COALESCE(c.`$clienteNombreCol`, 'Consumidor Final')";

            if ($filtroCliente !== '') {
                if ($clienteCedulaCol !== null) {
                    $filtroClienteSql = " AND (c.`$clienteNombreCol` LIKE ? OR c.`$clienteCedulaCol` LIKE ?)";
                    $likeCliente = '%' . $filtroCliente . '%';
                    $filtroClienteParams[] = $likeCliente;
                    $filtroClienteParams[] = $likeCliente;
                } else {
                    $filtroClienteSql = " AND c.`$clienteNombreCol` LIKE ?";
                    $filtroClienteParams[] = '%' . $filtroCliente . '%';
                }
            }
        }
    }

    $joinUsuario = '';
    $exprUsuario = "'Sin asignar'";

    if ($usuarioFkCol !== null) {
        $tablaUsuario = null;
        foreach (['usuarios', 'usuario', 'users'] as $candUsuario) {
            if (tablaExiste($pdo_posventa, $candUsuario)) {
                $tablaUsuario = $candUsuario;
                break;
            }
        }

        if ($tablaUsuario !== null) {
            $colUsuario = obtenerColumnasTabla($pdo_posventa, $tablaUsuario);
            $usuarioIdCol = primeraColumnaDisponible($colUsuario, ['id', 'id_usuario', 'user_id']);
            $usuarioNombreCol = primeraColumnaDisponible($colUsuario, ['usuario', 'nombre', 'nombre_completo', 'username']);

            if ($usuarioIdCol !== null && $usuarioNombreCol !== null) {
                $joinUsuario = " LEFT JOIN `$tablaUsuario` u ON u.`$usuarioIdCol` = v.`$usuarioFkCol`";
                $exprUsuario = "COALESCE(u.`$usuarioNombreCol`, 'Sin asignar')";
            }
        }
    }

    $exprNumero = $numeroCol !== null ? "COALESCE(CAST(v.`$numeroCol` AS CHAR), CAST(v.`$idCol` AS CHAR))" : "CAST(v.`$idCol` AS CHAR)";
    $exprEstado = $estadoCol !== null ? "CAST(v.`$estadoCol` AS CHAR)" : "'pagada'";
    $exprTipoFactura = $tipoCol !== null ? "CAST(v.`$tipoCol` AS CHAR)" : "'Factura'";

    $wherePartes = ['1 = 1'];
    $parametros = [];

    if ($fechaInicio !== null) {
        $wherePartes[] = "DATE(v.`$fechaCol`) >= ?";
        $parametros[] = $fechaInicio;
    }

    if ($fechaFin !== null) {
        $wherePartes[] = "DATE(v.`$fechaCol`) <= ?";
        $parametros[] = $fechaFin;
    }

    if ($filtroFactura !== '') {
        $wherePartes[] = $exprNumero . ' LIKE ?';
        $parametros[] = '%' . $filtroFactura . '%';
    }

    $whereSql = ' WHERE ' . implode(' AND ', $wherePartes) . $filtroClienteSql;
    $parametros = array_merge($parametros, $filtroClienteParams);

    $sql = "SELECT
                v.`$idCol` AS id,
                $exprNumero AS numero,
                DATE_FORMAT(v.`$fechaCol`, '%Y-%m-%d %H:%i') AS fecha,
                $exprCliente AS cliente,
                $exprUsuario AS vendedor,
                COALESCE(v.`$totalCol`, 0) AS total,
                $exprTipoFactura AS tipo_factura,
                $exprEstado AS estado
            FROM `$tablaCabecera` v
            $joinCliente
            $joinUsuario
            $whereSql
            ORDER BY v.`$fechaCol` DESC";

    $registros = ejecutarConsultaHistorial($pdo_posventa, $sql, $parametros);
    if ($registros === null) {
        throw new RuntimeException('Consulta de historial no disponible');
    }

    $totalVendido = 0.0;
    foreach ($registros as $registro) {
        $totalVendido += (float) ($registro['total'] ?? 0);
    }

    $cantidadFacturas = count($registros);
    $ticketPromedio = $cantidadFacturas > 0 ? $totalVendido / $cantidadFacturas : 0;

    $mensaje = '';
    if ($cantidadFacturas === 0) {
        $mensaje = 'No se encontraron transacciones para el rango seleccionado.';
    }

    echo json_encode([
        'estado' => 'ok',
        'resumen' => [
            'total_vendido' => round($totalVendido, 2),
            'cantidad_facturas' => $cantidadFacturas,
            'ticket_promedio' => round($ticketPromedio, 2),
        ],
        'registros' => $registros,
        'mensaje' => $mensaje,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $throwable) {
    http_response_code(500);
    echo json_encode([
        'estado' => 'error',
        'mensaje' => 'No fue posible cargar el historial de ventas.',
    ], JSON_UNESCAPED_UNICODE);
}
