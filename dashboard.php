<?php
declare(strict_types=1);

require_once __DIR__ . '/Backend/includes/session_init.php';

if (!isset($_SESSION['usuario_activo'])) {
    header('Location: index.php');
    exit();
}
$usuario = $_SESSION['usuario_activo'];
$rolUsuario = strtolower(trim((string) ($usuario['rol'] ?? '')));

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catalogo</title> 
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="Frontend/css/dashboard.css">
</head>
<body>
    <div class="d-flex">
        <?php include 'Backend/includes/sidebar.php'; ?>
        <div id="content" class="w-100">
            <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm mb-4 p-3">
                <div class="container-fluid" d-flex justify-content-between>
                    <span class="navbar-brand mb-0 h4 text-secondary">Dashboard General</span>
                    <div class="d-flex align-items-center">
                        <span class="me-4 fw-bold" style="color: var(--verde-oscuro);">
                            👤 <?php echo strtoupper($usuario['usuario']) . '| Rol: ' . ucfirst($usuario['rol']); ?>
                        </span>
                        <a href="Backend/logout.php" class="btn btn-outline-danger btn-sm">Cerrar sesión</a>
                    </div>
                    
                </div>
            </nav>

            <div class="container-fluid px-4">
                <div class="row">
                    <div class="col-12">
                        <div class="card shadow-sm border-0 border-top border-4" style="border-color: var(--verde-medio);">
                            <div class="card-body py-5 text-center bg-white rounded">
                                <h2 class="color:var(--verde-oscuro);">Bienvenido <?php echo $usuario['usuario']; ?></h2>
                                <p class="text-muted fs-5 mt-3">Seleccione una opcion del menu lateral para operar el sistema.</p>
                            </div>
                        </div>
                    </div>
                </div>
    
    
    
    </div>
    </div>
</body>
</html>