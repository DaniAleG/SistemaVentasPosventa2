<?php
// Este archivo actúa como un puente para Vercel.
// Atrapa la URL que el usuario pide y busca el archivo real en tu raíz.

$request = $_SERVER['REQUEST_URI'];
$base_dir = __DIR__ . '/../'; 

// Si piden la raíz, cargar tu index.php principal
if ($request === '/' || $request === '/index.php') {
    require $base_dir . 'index.php';
    exit;
}

// Limpiar la URL para buscar el archivo exacto
$file_path = $base_dir . ltrim(parse_url($request, PHP_URL_PATH), '/');

if (file_exists($file_path) && is_file($file_path)) {
    // Si el archivo termina en .php, lo ejecutamos
    if (pathinfo($file_path, PATHINFO_EXTENSION) === 'php') {
        require $file_path;
    } else {
        // Si es un CSS, JS o imagen, lo servimos con su tipo correcto
        $mime_type = mime_content_type($file_path);
        header("Content-Type: $mime_type");
        readfile($file_path);
    }
} else {
    // Si no existe, error 404
    http_response_code(404);
    echo "Página no encontrada.";
}
