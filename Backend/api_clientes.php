<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/session_init.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/conexion.php';

function normalizarTexto(?string $value): string
{
    return preg_replace('/\s+/u', ' ', trim((string) $value)) ?? '';
}

function normalizarNombre(?string $value): string
{
    $texto = preg_replace('/[^\p{L}\s]/u', '', (string) $value) ?? '';
    return normalizarTexto($texto);
}

function formatearNombrePropio(string $value): string
{
    $texto = normalizarNombre($value);

    if ($texto === '') {
        return '';
    }

    return mb_convert_case(mb_strtolower($texto, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
}

function normalizarDocumento(?string $value): string
{
    return preg_replace('/\D+/', '', (string) $value) ?? '';
}

function validarCorreo(string $correo): bool
{
    return filter_var($correo, FILTER_VALIDATE_EMAIL) !== false;
}

function existeClienteConCedula(PDO $pdo, string $cedula, ?int $idExcluir = null): bool
{
    if ($idExcluir !== null) {
        $stmt = $pdo->prepare('SELECT id FROM clientes WHERE cedula = ? AND id <> ? LIMIT 1');
        $stmt->execute([$cedula, $idExcluir]);
    } else {
        $stmt = $pdo->prepare('SELECT id FROM clientes WHERE cedula = ? LIMIT 1');
        $stmt->execute([$cedula]);
    }

    return (bool) $stmt->fetch();
}

try {
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    if ($method === 'POST' || $method === 'PUT') {
        $rawInput = file_get_contents('php://input');
        $input = $rawInput !== false && $rawInput !== '' ? json_decode($rawInput, true) : [];

        if ($rawInput !== false && $rawInput !== '' && json_last_error() !== JSON_ERROR_NONE) {
            http_response_code(400);
            echo json_encode([
                'estado' => 'error',
                'mensaje' => 'JSON inválido en la solicitud',
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $tipoDocumento = trim((string) ($input['tipo_documento'] ?? ''));
        $cedula = normalizarDocumento((string) ($input['cedula'] ?? ''));
        $nombre = formatearNombrePropio((string) ($input['nombre'] ?? ''));
        $apellido = formatearNombrePropio((string) ($input['apellido'] ?? ''));
        $nombreCompleto = formatearNombrePropio((string) ($input['nombre_completo'] ?? ''));
        $correo = normalizarTexto((string) ($input['correo'] ?? ''));

        if ($nombre !== '' && $apellido !== '') {
            $nombreCompleto = formatearNombrePropio($nombre . ' ' . $apellido);
        }

        if ($cedula === '' || $nombreCompleto === '') {
            http_response_code(422);
            echo json_encode([
                'estado' => 'error',
                'mensaje' => 'La cédula y el nombre completo son obligatorios',
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if (mb_strlen($nombre) < 2 || mb_strlen($apellido) < 2) {
            http_response_code(422);
            echo json_encode([
                'estado' => 'error',
                'mensaje' => 'El nombre y el apellido deben tener al menos 2 caracteres',
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($correo === '' || !validarCorreo($correo)) {
            http_response_code(422);
            echo json_encode([
                'estado' => 'error',
                'mensaje' => 'Ingresa un correo válido',
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($tipoDocumento !== '') {
            if ($tipoDocumento === 'cedula' && strlen($cedula) !== 10) {
                http_response_code(422);
                echo json_encode([
                    'estado' => 'error',
                    'mensaje' => 'La cédula debe tener 10 dígitos',
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            if ($tipoDocumento === 'ruc') {
                if (strlen($cedula) !== 13) {
                    http_response_code(422);
                    echo json_encode([
                        'estado' => 'error',
                        'mensaje' => 'El RUC debe tener 13 dígitos',
                    ], JSON_UNESCAPED_UNICODE);
                    exit;
                }

                if (substr($cedula, -3) !== '001') {
                    http_response_code(422);
                    echo json_encode([
                        'estado' => 'error',
                        'mensaje' => 'El RUC de persona natural debe terminar en 001',
                    ], JSON_UNESCAPED_UNICODE);
                    exit;
                }
            }

            if ($tipoDocumento !== 'cedula' && $tipoDocumento !== 'ruc') {
                http_response_code(422);
                echo json_encode([
                    'estado' => 'error',
                    'mensaje' => 'El tipo de documento no es válido',
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
        }

        if ($method === 'POST') {
            if (existeClienteConCedula($pdo, $cedula)) {
                http_response_code(409);
                echo json_encode([
                    'estado' => 'error',
                    'mensaje' => 'Ya existe un cliente registrado con esa cedula o RUC en la base de datos',
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $stmt = $pdo->prepare('INSERT INTO clientes (cedula, nombre_completo, correo) VALUES (?, ?, ?)');
            $stmt->execute([$cedula, $nombreCompleto, $correo]);

            echo json_encode([
                'estado' => 'exito',
                'mensaje' => 'Cliente registrado correctamente',
                'cliente' => [
                    'id' => (int) $pdo->lastInsertId(),
                    'cedula' => $cedula,
                    'nombre_completo' => $nombreCompleto,
                    'correo' => $correo,
                    'tipo_documento' => $tipoDocumento !== '' ? $tipoDocumento : null,
                ],
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $id = (int) ($input['id'] ?? 0);
        if ($id <= 0) {
            http_response_code(400);
            echo json_encode([
                'estado' => 'error',
                'mensaje' => 'ID de cliente inválido',
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if (existeClienteConCedula($pdo, $cedula, $id)) {
            http_response_code(409);
            echo json_encode([
                'estado' => 'error',
                'mensaje' => 'Ya existe otro cliente registrado con esa cédula o RUC en la base de datos',
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $stmt = $pdo->prepare('UPDATE clientes SET cedula = ?, nombre_completo = ?, correo = ? WHERE id = ?');
        $stmt->execute([$cedula, $nombreCompleto, $correo, $id]);

        echo json_encode([
            'estado' => 'exito',
            'mensaje' => 'Cliente actualizado correctamente',
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($method === 'DELETE') {
        $usuarioSesionDelete = $_SESSION['usuario_activo'] ?? null;
        $rolUsuarioDelete = strtolower(trim((string) ($usuarioSesionDelete['rol'] ?? '')));

        if ($usuarioSesionDelete === null) {
            http_response_code(401);
            echo json_encode([
                'estado' => 'error',
                'mensaje' => 'Debes iniciar sesión para eliminar un cliente',
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($rolUsuarioDelete !== 'administrador') {
            http_response_code(403);
            echo json_encode([
                'estado' => 'error',
                'mensaje' => 'Solo un administrador puede eliminar clientes',
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $id = (int) ($_GET['id'] ?? 0);

        if ($id <= 0) {
            http_response_code(400);
            echo json_encode([
                'estado' => 'error',
                'mensaje' => 'ID de cliente inválido',
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $stmt = $pdo->prepare('DELETE FROM clientes WHERE id = ?');
        $stmt->execute([$id]);

        echo json_encode([
            'estado' => 'exito',
            'mensaje' => 'Cliente eliminado correctamente',
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (isset($_GET['reporte']) && $_GET['reporte'] === 'frecuentes') {
        $usuarioSesion = $_SESSION['usuario_activo'] ?? null;
        $rolUsuario = strtolower(trim((string) ($usuarioSesion['rol'] ?? '')));

        if ($usuarioSesion === null) {
            http_response_code(401);
            echo json_encode([
                'estado' => 'error',
                'mensaje' => 'Debes iniciar sesión para ver esta información',
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($rolUsuario !== 'administrador') {
            http_response_code(403);
            echo json_encode([
                'estado' => 'error',
                'mensaje' => 'No tienes permisos para ver esta información',
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Consumidor Final agrupa muchas ventas anónimas del POS y no representa
        // a un cliente real, así que se excluye del ranking de frecuencia.
        $stmtFrecuentes = $pdo->prepare(
            "SELECT c.id, c.cedula, c.nombre_completo, c.correo,
                    COUNT(v.id) AS total_compras,
                    COALESCE(SUM(v.total_factura), 0) AS total_gastado,
                    MAX(v.fecha_emision) AS ultima_compra
             FROM clientes c
             INNER JOIN ventas v ON v.cliente_id = c.id
             WHERE c.cedula <> '9999999999999'
             GROUP BY c.id, c.cedula, c.nombre_completo, c.correo
             ORDER BY total_compras DESC, total_gastado DESC"
        );
        $stmtFrecuentes->execute();
        $clientesFrecuentes = $stmtFrecuentes->fetchAll();

        $totalClientesConCompras = count($clientesFrecuentes);
        $clienteMasCompra = $totalClientesConCompras > 0 ? $clientesFrecuentes[0] : null;
        $clienteMenosCompra = $totalClientesConCompras > 1 ? $clientesFrecuentes[$totalClientesConCompras - 1] : null;

        echo json_encode([
            'estado' => 'exito',
            'cliente_mas_compra' => $clienteMasCompra,
            'cliente_menos_compra' => $clienteMenosCompra,
            'clientes_frecuentes' => $clientesFrecuentes,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $search = trim((string) ($_GET['q'] ?? ''));

    if ($search === '') {
        $stmt = $pdo->query(
            'SELECT c.id, c.cedula, c.nombre_completo, c.correo, c.fecha_registro,
                    COALESCE(v.total_compras, 0) AS total_compras,
                    COALESCE(v.total_gastado, 0) AS total_gastado
             FROM clientes c
             LEFT JOIN (
                 SELECT cliente_id, COUNT(*) AS total_compras, SUM(total_factura) AS total_gastado
                 FROM ventas
                 GROUP BY cliente_id
             ) v ON v.cliente_id = c.id
             ORDER BY c.id ASC'
        );
    } else {
        $stmt = $pdo->prepare(
            'SELECT c.id, c.cedula, c.nombre_completo, c.correo, c.fecha_registro,
                    COALESCE(v.total_compras, 0) AS total_compras,
                    COALESCE(v.total_gastado, 0) AS total_gastado
             FROM clientes c
             LEFT JOIN (
                 SELECT cliente_id, COUNT(*) AS total_compras, SUM(total_factura) AS total_gastado
                 FROM ventas
                 GROUP BY cliente_id
             ) v ON v.cliente_id = c.id
             WHERE c.cedula LIKE ?
                OR c.nombre_completo LIKE ?
                OR c.correo LIKE ?
             ORDER BY c.id ASC'
        );
        $like = '%' . $search . '%';
        $stmt->execute([$like, $like, $like]);
    }

    echo json_encode($stmt->fetchAll(), JSON_UNESCAPED_UNICODE);
} catch (Throwable $throwable) {
    http_response_code(500);
    echo json_encode([
        'estado' => 'error',
        'mensaje' => 'No fue posible cargar los clientes',
    ], JSON_UNESCAPED_UNICODE);
}