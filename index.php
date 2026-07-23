<?php

require_once __DIR__ . '/Backend/includes/session_init.php';
if (isset($_SESSION['usuario_activo'])) {
    $rol = strtolower(trim((string) ($_SESSION['usuario_activo']['rol'] ?? '')));
    $destino = $rol === 'administrador' ? 'dashboard.php' : 'pos.php';
    header('Location: ' . $destino);
    exit;
}

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso al Sistema</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
</head>

<body class="bg-light d-flex align-items-center justify-content-center vh-100">
    <div class="card shadow p-4" style="width:100% ; max-width: 400px;">

        <div class="text-center mb-4">
            <h3 class="text-primary">Sistema POS</h3>
            <p class="text-muted">Ingrese sus credenciales.</p>
        </div>

        <?php if(isset($_GET['error'])): ?>
            <div class="alert alert-danger" role="alert">
            Usuario o contraseña incorrectos.
            </div>
        <?php endif; ?>

        <form method="POST" action="Backend/procesar_login.php">
            <div class="mb-3">
                <label for="usuario" class="form-label">Usuario</label>
                <input type="text" class="form-control" id="usuario" name="usuario" required autocomplete="off">
            </div>
            <div class="mb-4">
                <label for="password" class="form-label">Contraseña</label>
                <input type="password" class="form-control" id="password" name="password" required autocomplete="off">
            </div>
            <button type="submit" class="btn btn-primary w-100">Iniciar Sesión</button>
        </form>

    </div>

</body>

</html>