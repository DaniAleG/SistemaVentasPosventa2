<?php
$request = $_SERVER['REQUEST_URI'];
$base_dir = __DIR__ . '/../'; 

if ($request === '/' || $request === '/index.php') {
    require $base_dir . 'index.php';
    exit;
}

$file_path = $base_dir . ltrim(parse_url($request, PHP_URL_PATH), '/');

if (file_exists($file_path) && is_file($file_path)) {
    $ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
    
    if ($ext === 'php') {
        require $file_path;
    } else {
        // Diccionario manual de tipos de archivo para Vercel
        $mime_types = [
            'css' => 'text/css',
            'js' => 'application/javascript',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'json' => 'application/json'
        ];

        if (array_key_exists($ext, $mime_types)) {
            header("Content-Type: " . $mime_types[$ext]);
        } else {
            header("Content-Type: " . mime_content_type($file_path));
        }
        readfile($file_path);
    }
} else {
    http_response_code(404);
    echo "Página no encontrada.";
}
