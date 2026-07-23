<?php

declare(strict_types=1);

require_once __DIR__ . '/Backend/includes/session_init.php';

if (!isset($_SESSION['usuario_activo'])) {
    header('Location: index.php');
    exit();
}
$usuario = $_SESSION['usuario_activo'];
$rolUsuario = strtolower(trim((string) ($usuario['rol'] ?? '')));
if ($rolUsuario !== 'administrador') {
    header('Location: pos.php');
    exit();
}
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
    <style>
        .btn-verde {
            background-color: var(--verde-oscuro);
            color: white;
        }

        .btn-verde:hover {
            background-color: var(--verde-medio);
            color: white;
        }
    </style>
</head>

<body>
    <div class="d-flex">
        <?php include 'Backend/includes/sidebar.php'; ?>
        <div id="content" class="w-100">
            <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm mb-4 p-3">
                <span class="navbar-brand mb-0 h4 text-secondary">Catalogo de Productos</span>
            </nav>

            <div class="container-fluid px-4">
                <div class="d-flex justify-content-between align-items-center mb-4">

                    <input
                        type="text"
                        id="input-busqueda"
                        class="form-control w-25"
                        placeholder="🔍 Buscar por nombre o código...">

                    <div>

                        <button class="btn btn-verde" onclick="abrirModal()">
                            Nuevo Producto
                        </button>

                    </div>

                </div>

                <div id="reader"
                    style="display:none;width:350px;margin-bottom:20px;">
                </div>
                <!--Tabla para los datos-->
                <div class="card shadow-sm">
                    <div class="card-body">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col">Codigo</th>
                                    <th scope="col">Nombre</th>
                                    <th scope="col">Precio</th>
                                    <th scope="col">Stock</th>
                                    <th scope="col">Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="cuerpo-tabla">
                                <!-- Tabla de Productos-->

                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--Modal para insertar producto-->
    <div class="modal fade" id="modalProducto" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitulo">Gestionar Producto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="prod-id">
                    <div class="mb-3">
                        <label>Código de Barras</label>
                        <div class="input-group">
                            <input type="text" id="prod-codigo" class="form-control" placeholder="Escribe o escanea el código...">
                            <button class="btn btn-outline-secondary" type="button" onclick="abrirScannerModal()" id="btn-scan-modal" title="Escanear código">
                                📷 Escanear
                            </button>
                        </div>
                        <div id="reader-modal" style="display:none; width:100%; margin-top: 10px; border-radius: 8px; overflow: hidden;"></div>
                        <div class="invalid-feedback d-block d-none" id="prod-codigo-error"></div>
                    </div>
                    <div class="mb-3">
                        <label>Nombre</label>
                        <input type="text" id="prod-nombre" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label>Precio</label>
                        <input type="text" id="prod-precio" class="form-control" inputmode="decimal" placeholder="0.00">
                    </div>
                    <div class="mb-3">
                        <label>Stock</label>
                        <input type="text" id="prod-stock" class="form-control" inputmode="numeric" placeholder="0">
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button class="btn btn-verde" onclick="guardarProducto()">Guardar Cambios</button>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
    <script src="https://unpkg.com/html5-qrcode"></script>
    <script src="Frontend/js/catalogo.js" defer></script>
</body>

</html>