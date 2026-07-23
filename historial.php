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
    <title>Historial de Ventas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="Frontend/css/dashboard.css">
    <link rel="stylesheet" href="Frontend/css/historial.css">
</head>

<body>
    <div class="d-flex">
        <?php include 'Backend/includes/sidebar.php'; ?>
        <div id="content" class="w-100">
            <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm mb-4 p-3">
                <span class="navbar-brand mb-0 h4 text-secondary">Historial de Ventas</span>
            </nav>

            <div class="container-fluid px-4">
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <div class="row g-3 align-items-end">
                            <div class="col-12 col-md-3">
                                <label for="filtro-fecha-inicio" class="form-label">Fecha inicio</label>
                                <input type="date" id="filtro-fecha-inicio" class="form-control">
                            </div>
                            <div class="col-12 col-md-3">
                                <label for="filtro-fecha-fin" class="form-label">Fecha fin</label>
                                <input type="date" id="filtro-fecha-fin" class="form-control">
                            </div>
                            <div class="col-12 col-md-3">
                                <label for="filtro-cliente" class="form-label">Cliente (cédula o nombre)</label>
                                <input type="text" id="filtro-cliente" class="form-control" placeholder="Ej: 0912... o Juan Pérez">
                            </div>
                            <div class="col-12 col-md-3">
                                <label for="filtro-factura" class="form-label">N° Factura / Nota de venta</label>
                                <input type="text" id="filtro-factura" class="form-control" placeholder="Ej: FAC-000123">
                            </div>
                            <div class="col-12 col-md-3 d-grid">
                                <button type="button" id="btn-consultar-historial" class="btn btn-success">Consultar</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-12 col-md-6 col-xl-4">
                        <div class="card card-totalizador h-100">
                            <div class="card-body">
                                <p class="titulo">Total Vendido ($)</p>
                                <p id="card-total-vendido" class="valor">$ 0.00</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-xl-4">
                        <div class="card card-totalizador h-100">
                            <div class="card-body">
                                <p class="titulo">Cantidad de Facturas</p>
                                <p id="card-cantidad-facturas" class="valor">0</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-xl-4">
                        <div class="card card-totalizador h-100">
                            <div class="card-body">
                                <p class="titulo">Ticket Promedio</p>
                                <p id="card-ticket-promedio" class="valor">$ 0.00</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-header bg-white fw-semibold">Detalle de transacciones</div>
                    <div class="card-body">
                        <div id="mensaje-historial" class="small text-secondary mb-3"></div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>N° Factura / ID</th>
                                        <th>Fecha y Hora</th>
                                        <th>Cliente</th>
                                        <th>Vendedor / Cajero</th>
                                        <th class="text-end">Total ($)</th>
                                        <th class="text-center">Tipo Factura</th>
                                        <th class="text-center">Estado</th>
                                    </tr>
                                </thead>
                                <tbody id="historial-body">
                                    <tr>
                                        <td colspan="7" class="text-center text-secondary py-4">Sin datos para mostrar</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="Frontend/js/historial.js" defer></script>
</body>

</html>