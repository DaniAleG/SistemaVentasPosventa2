<?php

declare(strict_types=1);

require_once __DIR__ . '/Backend/includes/session_init.php';

if (!isset($_SESSION['usuario_activo'])) {
    header('Location: index.php');
    exit();
}

$usuario = $_SESSION['usuario_activo'];
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Punto de Venta</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="Frontend/css/dashboard.css">
</head>

<body>
    <div class="d-flex">
        <?php include 'Backend/includes/sidebar.php'; ?>

        <div id="content" class="w-100">
            <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm mb-4 p-3">
                <span class="navbar-brand mb-0 h4 text-secondary">Punto de Venta</span>
            </nav>

            <div class="container-fluid px-4">
                <div class="alert alert-success shadow-sm">
                    Bienvenido, <?php echo $usuario['usuario']; ?>
                </div>

                <div class="row g-4">
                    <div class="col-lg-8">
                        <div class="card shadow-sm h-100">
                            <div class="card-header bg-white fw-semibold">
                                Buscador rápido / lector de código de barras
                            </div>
                            <div class="card-body">
                                <div id="buscador-wrapper" class="position-relative mb-4">
                                    <input type="text" id="buscador-productos" class="form-control form-control-lg" placeholder="Escanea o busca un producto...">
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-hover align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Producto</th>
                                                <th class="text-center">Cantidad</th>
                                                <th class="text-end">Precio</th>
                                                <th class="text-end">Subtotal</th>
                                                <th class="text-center">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody id="carrito-body">
                                            <tr>
                                                <td colspan="5" class="text-center text-secondary py-4">
                                                    El carrito está vacío
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card shadow-sm mb-4">
                            <div class="card-header bg-white fw-semibold">
                                Facturación
                            </div>
                            <div class="card-body">
                                <div class="mb-3 p-3 bg-light rounded border">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="fw-semibold">Cliente de la venta</span>
                                        <div class="form-check form-switch mb-0">
                                            <input class="form-check-input" type="checkbox" role="switch" id="cliente-consumidor-final" checked>
                                            <label class="form-check-label" for="cliente-consumidor-final">Consumidor final</label>
                                        </div>
                                    </div>
                                    <div id="cliente-resumen-venta" class="small text-secondary">
                                        <div class="fw-semibold text-dark" id="cliente-seleccionado">Consumidor Final</div>
                                        <div id="cliente-seleccionado-detalle">Nombre: Consumidor Final · Identificación/RUC: 9999999999999 · Dirección: ESPE - LAB 301</div>
                                        <div id="cliente-frecuente-badge" class="badge bg-success mt-2" style="display: none;">🏷️ Cliente frecuente · 10% de descuento</div>
                                    </div>
                                </div>

                                <label class="form-label">Buscar cliente</label>
                                <div id="cliente-wrapper" class="position-relative mb-2">
                                    <input type="text" id="cliente-buscador" class="form-control" placeholder="Buscar cliente por cédula, nombre o correo...">
                                </div>

                                <div class="small text-secondary mb-3">
                                    Usa la búsqueda para seleccionar un cliente existente.
                                </div>

                                <div class="mb-3 p-3 bg-light rounded">
                                    <div class="d-flex justify-content-between">
                                        <span>Subtotal</span>
                                        <strong id="subtotal-resumen">$ 0.00</strong>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span>IVA (15%)</span>
                                        <strong id="iva-resumen">$ 0.00</strong>
                                    </div>
                                    <div class="d-none justify-content-between text-success" id="descuento-fila">
                                        <span id="descuento-etiqueta">🏷️ Descuento cliente frecuente</span>
                                        <strong id="descuento-resumen">- $ 0.00</strong>
                                    </div>
                                    <div class="d-flex justify-content-between fs-5">
                                        <span>Total</span>
                                        <strong id="total-resumen" class="text-success">$ 0.00</strong>
                                    </div>
                                </div>

                                <label class="form-label">Pago</label>
                                <input type="text" id="pago-input" class="form-control mb-3" inputmode="decimal" placeholder="0.00">

                                <div class="mb-3 p-3 bg-light rounded d-flex justify-content-between">
                                    <span>Cambio</span>
                                    <strong id="cambio-texto">$ 0.00</strong>
                                </div>

                                <button type="button" id="procesar-venta-btn" class="btn btn-success w-100 btn-lg">
                                    Procesar Venta
                                </button>
                            </div>
                        </div>

                        <div class="card shadow-sm">
                            <div class="card-body">
                                <button type="button" id="imprimir-factura-btn" class="btn btn-outline-success w-100" disabled>
                                    Imprimir recibo / PDF
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/jspdf-autotable@3.8.2/dist/jspdf.plugin.autotable.min.js" crossorigin="anonymous"></script>
    <script src="Frontend/js/pos.js" defer></script>
</body>

</html>