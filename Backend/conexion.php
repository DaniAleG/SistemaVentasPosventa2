<?php
    declare(strict_types=1);

    // --- 1. CREDENCIALES BASE PRINCIPAL (posventa2) ---
    $host_posventa = getenv('DB_HOST') ?: 'localhost';
    $port_posventa = getenv('DB_PORT') ?: '3306';
    $user_posventa = getenv('DB_USER') ?: 'root';
    $pass_posventa = getenv('DB_PASSWORD') ?: '';
    $name_posventa = getenv('DB_NAME') ?: 'posventa2';

    // --- 2. CREDENCIALES BASE SESIONES ---
    // En Vercel, deberás crear estas variables con el sufijo _SESIONES
    $host_sesiones = getenv('DB_SESIONES_HOST') ?: 'localhost';
    $port_sesiones = getenv('DB_SESIONES_PORT') ?: '3306';
    $user_sesiones = getenv('DB_SESIONES_USER') ?: 'root';
    $pass_sesiones = getenv('DB_SESIONES_PASSWORD') ?: '';
    $name_sesiones = getenv('DB_SESIONES_NAME') ?: 'sesiones';

    $charset = 'utf8mb4';

    $opciones = [
        // Obliga a PDO a lanzar excepciones en caso de error SQL
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    try {
        // Instancia PDO para Posventa (Usuarios, inventario, etc.)
        $dns_posventa = "mysql:host=$host_posventa;port=$port_posventa;dbname=$name_posventa;charset=$charset";
        $pdo_posventa = new PDO($dns_posventa, $user_posventa, $pass_posventa, $opciones);

        // Instancia PDO exclusiva para el Control de Sesiones
        $dns_sesiones = "mysql:host=$host_sesiones;port=$port_sesiones;dbname=$name_sesiones;charset=$charset";
        $pdo_sesiones = new PDO($dns_sesiones, $user_sesiones, $pass_sesiones, $opciones);

    } catch (PDOException $e) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'estado' => 'error',
            'mensaje' => 'Error crítico de conexión a los nodos de datos'
        ]);
        exit;
    }
?>