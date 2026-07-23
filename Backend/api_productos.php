<?php
declare(strict_types=1);

//cabeceras para el intercambio de JSON
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type");

//incluir la conexión
require_once("conexion.php");

//incluir el metodo de peticion GET, POST, etc

$method = $_SERVER["REQUEST_METHOD"];

//capturar el cuerpo de la peticion para el POST, PUT

$rawInput = file_get_contents("php://input");
$input = $rawInput !== false && $rawInput !== "" ? json_decode($rawInput, true) : [];
if ($rawInput !== false && $rawInput !== "" && json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode([
        "estado" => "error",
        "mensaje" => "JSON invalido en el cuerpo de la peticion",
    ]);
    exit;
}

function validarProducto(array $input): array
{
    $codigo = trim((string)($input["codigo"] ?? ""));
    $nombre = trim((string)($input["nombre"] ?? ""));
    $precio = $input["precio"] ?? null;
    $stock = $input["stock"] ?? null;

    if ($codigo === "" || $nombre === "") {
        return [false, "Codigo y nombre son obligatorios"];
    }

    if (!is_numeric($precio) || !is_numeric($stock)) {
        return [false, "Precio y stock deben ser numericos"];
    }

    if ((float)$precio < 0 || (float)$stock < 0) {
        return [false, "Precio y stock no pueden ser negativos"];
    }

    return [true, ""];
}

function existeProductoConCodigo(PDO $pdo, string $codigo, ?int $idExcluir = null): bool
{
    if ($idExcluir !== null) {
        $stmt = $pdo->prepare("SELECT id FROM productos WHERE codigo_barras = ? AND id <> ? LIMIT 1");
        $stmt->execute([$codigo, $idExcluir]);
    } else {
        $stmt = $pdo->prepare("SELECT id FROM productos WHERE codigo_barras = ? LIMIT 1");
        $stmt->execute([$codigo]);
    }

    return (bool) $stmt->fetch();
}

try {
    switch ($method) {
        case "GET":
            $search = $_GET["q"] ?? "";
            $sql =
                "SELECT * FROM productos WHERE nombre_producto LIKE ? OR codigo_barras LIKE ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(["%$search%", "%$search%"]);
            echo json_encode($stmt->fetchAll());
            break;

        case "POST":
            [$esValido, $mensaje] = validarProducto($input);
            if (!$esValido) {
                http_response_code(400);
                echo json_encode([
                    "estado" => "error",
                    "mensaje" => $mensaje,
                ]);
                break;
            }

            if (existeProductoConCodigo($pdo, trim((string)$input["codigo"]))) {
                http_response_code(409);
                echo json_encode([
                    "estado" => "error",
                    "mensaje" => "Ya existe un producto registrado con ese código de barras",
                ]);
                break;
            }

            $sql =
                "INSERT INTO productos (codigo_barras, nombre_producto, precio_actual, stock_disponible) VALUES (?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                trim((string)$input["codigo"]),
                trim((string)$input["nombre"]),
                (float)$input["precio"],
                (float)$input["stock"],
            ]);
            echo json_encode([
                "estado" => "exito",
                "mensaje" => "Valor insertado",
            ]);
            break;

        case "PUT":
            [$esValido, $mensaje] = validarProducto($input);
            if (!$esValido || !isset($input["id"]) || !is_numeric($input["id"])) {
                http_response_code(400);
                echo json_encode([
                    "estado" => "error",
                    "mensaje" => $esValido ? "Id invalido" : $mensaje,
                ]);
                break;
            }

            if (existeProductoConCodigo($pdo, trim((string)$input["codigo"]), (int)$input["id"])) {
                http_response_code(409);
                echo json_encode([
                    "estado" => "error",
                    "mensaje" => "Ya existe otro producto registrado con ese código de barras",
                ]);
                break;
            }

            $sql =
                "UPDATE productos SET codigo_barras = ?, nombre_producto = ?, precio_actual = ?, stock_disponible = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                trim((string)$input["codigo"]),
                trim((string)$input["nombre"]),
                (float)$input["precio"],
                (float)$input["stock"],
                (int)$input["id"],
            ]);
            echo json_encode([
                "estado" => "exito",
                "mensaje" => "Producto actualizado",
            ]);
            break;

        case "DELETE":
            $id = $_GET["id"] ?? 0;
            $sql = "DELETE FROM productos WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id]);
            echo json_encode([
                "estado" => "exito",
                "mensaje" => "Producto eliminado",
            ]);
            break;

        default:
            http_response_code(500);
            echo json_encode([
                "estado" => "error",
                "mensaje" => "metodo no soportado",
            ]);
            break;
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "estado" => "error",
        "mensaje" => $e->getMessage(),
    ]);
}

?>