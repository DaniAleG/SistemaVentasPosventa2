<?php

declare(strict_types=1);

require_once __DIR__ . '/Backend/includes/session_init.php';

if (!isset($_SESSION['usuario_activo'])) {
    header('Location: index.php');
    exit();
}

$usuario = $_SESSION['usuario_activo'];
$rolUsuario = strtolower(trim((string) ($usuario['rol'] ?? '')));
$esAdministrador = $rolUsuario === 'administrador';
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clientes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="Frontend/css/dashboard.css">
</head>

<body>
    <div class="d-flex min-vh-100">
        <?php include 'Backend/includes/sidebar.php'; ?>

        <div id="content" class="flex-grow-1">
            <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm mb-4 p-3">
                <span class="navbar-brand mb-0 h4 text-secondary">Gestión de Clientes</span>
            </nav>

            <div class="container-fluid px-4">
                <div class="alert alert-success shadow-sm">
                    Bienvenido, <?php echo $usuario['usuario']; ?>
                </div>

                <?php if ($esAdministrador): ?>
                <div class="row g-4 mb-4" id="panel-clientes-frecuentes">
                    <div class="col-12">
                        <div class="card shadow-sm h-100">
                            <div class="card-header bg-white fw-semibold">
                                Clientes frecuentes
                            </div>
                            <div class="card-body">
                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <div class="p-3 rounded h-100" style="background-color: #d8f3dc; border-left: 4px solid var(--verde-medio);">
                                            <div class="text-uppercase small fw-semibold" style="color: var(--verde-oscuro);">⭐ Cliente que más compra</div>
                                            <div id="cliente-mas-frecuente" class="mt-2">
                                                <span class="text-secondary">Cargando...</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="p-3 rounded h-100" style="background-color: #f8f9fa; border-left: 4px solid #adb5bd;">
                                            <div class="text-uppercase small fw-semibold text-secondary">Cliente que menos compra</div>
                                            <div id="cliente-menos-frecuente" class="mt-2">
                                                <span class="text-secondary">Cargando...</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <h6 class="fw-semibold text-secondary mb-3">Ranking de clientes por compras</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th>#</th>
                                                <th>Cliente</th>
                                                <th>Cédula/RUC</th>
                                                <th class="text-center">Compras</th>
                                                <th class="text-end">Total Gastado</th>
                                                <th>Última Compra</th>
                                            </tr>
                                        </thead>
                                        <tbody id="clientes-frecuentes-body">
                                            <tr>
                                                <td colspan="6" class="text-center text-secondary py-4">Cargando...</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <div class="row g-4">
                    <div class="col-12">
                        <div class="card shadow-sm h-100">
                            <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
                                <span>Buscador de clientes</span>
                                <button type="button" class="btn btn-success btn-sm" onclick="abrirModal()">
                                    Nuevo Cliente
                                </button>
                            </div>
                            <div class="card-body">
                                <input type="text" id="buscador-clientes" class="form-control form-control-lg mb-4" placeholder="Buscar por cédula, nombre o correo...">

                                <div class="table-responsive">
                                    <table class="table table-hover align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th>ID</th>
                                                <th>Cédula/RUC</th>
                                                <th>Nombre Completo</th>
                                                <th>Correo</th>
                                                <th>Fecha Registro</th>
                                                <th class="text-center">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody id="clientes-body">
                                            <tr>
                                                <td colspan="6" class="text-center text-secondary py-4">
                                                    No hay clientes para mostrar
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalCliente" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTituloCliente">Gestionar Cliente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="cliente-id">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" role="switch" id="cliente-es-ruc">
                        <label class="form-check-label" for="cliente-es-ruc">Registrar como RUC</label>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" id="cliente-documento-etiqueta" for="cliente-cedula">Cédula</label>
                        <input type="text" id="cliente-cedula" class="form-control" inputmode="numeric" autocomplete="off" placeholder="10 dígitos para cédula o 13 para RUC">
                        <div class="form-text" id="cliente-documento-ayuda">La cédula debe tener 10 dígitos.</div>
                        <div class="invalid-feedback d-block d-none" id="cliente-cedula-error"></div>
                    </div>
                    <div class="mb-3">
                        <label for="cliente-nombre" class="form-label">Nombre</label>
                        <input type="text" id="cliente-nombre" class="form-control" autocomplete="given-name" placeholder="Nombre(s)" inputmode="text">
                        <div class="invalid-feedback d-block d-none" id="cliente-nombre-error"></div>
                    </div>
                    <div class="mb-3">
                        <label for="cliente-apellido" class="form-label">Apellido</label>
                        <input type="text" id="cliente-apellido" class="form-control" autocomplete="family-name" placeholder="Apellido(s)" inputmode="text">
                        <div class="invalid-feedback d-block d-none" id="cliente-apellido-error"></div>
                    </div>
                    <div class="mb-3">
                        <label for="cliente-correo" class="form-label">Correo</label>
                        <input type="email" id="cliente-correo" class="form-control" autocomplete="email" placeholder="correo@ejemplo.com">
                        <div class="invalid-feedback d-block d-none" id="cliente-correo-error"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-success" onclick="guardarCliente()">Guardar Cambios</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        window.ES_ADMINISTRADOR = <?php echo $esAdministrador ? 'true' : 'false'; ?>;
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="Frontend/js/clientes.js" defer></script>
</body>

</html>