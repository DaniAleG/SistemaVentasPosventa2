<?php
$usuarioSesion = $_SESSION['usuario_activo'] ?? [];
$rolUsuario = strtolower(trim((string) ($usuarioSesion['rol'] ?? '')));
$esAdministrador = $rolUsuario === 'administrador';
?>

<button id="sidebar-toggle" class="sidebar-toggle-btn" type="button" aria-label="Abrir menu" aria-expanded="false">
    ☰
</button>

<div id="sidebar-hover-zone" aria-hidden="true"></div>

<div id="sidebar-backdrop" aria-hidden="true"></div>

<nav id="sidebar" class="d-flex flex-column p-3 text-white">
    <div class="text-center mb-4 mt-2">
        <h3 class="fw-bold m-0 text-light">🛒 SISTEMA POS </h3>
        <small style="color: var(--verde-claro);">GESTIÓN COMERCIAL</small>
    </div>
    <hr style="border-color: var(--verde-medio);">

    <ul class="nav nav-pills flex-column mb-auto mt-2">
        <li class="nav-item mb-2">
            <a href="dashboard.php" class="nav-link text-white fw-semibold menu-item" aria-current="page">
                🏠INICIO
            </a>
        </li>
        <?php if ($esAdministrador): ?>
        <li class="nav-item mb-2">
            <a href="catalogo.php" class="nav-link text-white fw-semibold menu-item" aria-current="page">
                📦CATALOGO
            </a>
        </li>
        <?php endif; ?>
        <li class="nav-item mb-2">
            <a href="pos.php" class="nav-link text-white fw-semibold menu-item" aria-current="page">
                💻PUNTO DE VENTA
            </a>
        </li>
        <li class="nav-item mb-2">
            <a href="clientes.php" class="nav-link text-white fw-semibold menu-item" aria-current="page">
                👥CLIENTES
            </a>
        </li>
        <?php if ($esAdministrador): ?>
        <li class="nav-item mb-2">
            <a href="historial.php" class="nav-link text-white fw-semibold menu-item" aria-current="page">
                📊REPORTES
            </a>
        </li>
        <?php endif; ?>

    </ul>

    <hr style="border-color: var(--verde-medio);">
    <div class="text-center pb-2">
        <small class="text-white-50">Versión 1.0</small>
    </div>
</nav>

<script src="Frontend/js/sidebar.js" defer></script>