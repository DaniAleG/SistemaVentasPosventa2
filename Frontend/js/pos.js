var apiProductosUrl = "Backend/api_productos.php";
var apiClientesUrl = "Backend/api_clientes.php";
var resultadosBusqueda = [];
var resultadosClientes = [];
var carrito = new Map();
var ventaProcesadaActual = null;
var clienteSeleccionado = {
  id: null,
  cedula: "",
  nombre_completo: "Consumidor Final",
  correo: "",
  direccion: "ESPE - LAB 301",
  tipo_documento: null,
};

function formatMoney(value) {
  return Number(value || 0).toFixed(2);
}

function sanitizarDecimal(value) {
  var texto = String(value || "")
    .replace(/,/g, ".")
    .replace(/[^0-9.]/g, "");
  var partes = texto.split(".");

  if (partes.length <= 1) {
    return texto;
  }

  return partes.shift() + "." + partes.join("");
}

function obtenerPagoSeguro() {
  var pagoInput = document.getElementById("pago-input");
  var valorNormalizado = pagoInput ? sanitizarDecimal(pagoInput.value) : "";
  var pago = Number(valorNormalizado || 0);

  if (!Number.isFinite(pago) || pago < 0) {
    return 0;
  }

  return pago;
}

function bloquearSignosPago(event) {
  if (event.ctrlKey || event.metaKey || event.altKey) {
    return;
  }

  if (
    event.key === "-" ||
    event.key === "+" ||
    event.key === "e" ||
    event.key === "E"
  ) {
    event.preventDefault();
  }
}

function obtenerTipoComprobanteActual() {
  var checkboxConsumidor = document.getElementById("cliente-consumidor-final");
  if (checkboxConsumidor && checkboxConsumidor.checked) {
    return "Nota de venta";
  }

  return "Factura";
}

function generarNumeroComprobante() {
  var ahora = new Date();
  var stamp =
    String(ahora.getFullYear()) +
    String(ahora.getMonth() + 1).padStart(2, "0") +
    String(ahora.getDate()).padStart(2, "0") +
    String(ahora.getHours()).padStart(2, "0") +
    String(ahora.getMinutes()).padStart(2, "0") +
    String(ahora.getSeconds()).padStart(2, "0");

  return "FAC-" + stamp;
}

function actualizarEstadoBotonImprimir() {
  var botonImprimir = document.getElementById("imprimir-factura-btn");
  if (!botonImprimir) {
    return;
  }

  botonImprimir.disabled = !ventaProcesadaActual;
}

function crearVentaProcesada(ventaServidor) {
  var totales = calcularTotales();
  var pago = obtenerPagoSeguro();
  var cliente =
    clienteSeleccionado && clienteSeleccionado.nombre_completo
      ? clienteSeleccionado
      : {
          nombre_completo: "Consumidor Final",
          cedula: "9999999999999",
          direccion: "ESPE - LAB 301",
          correo: "",
        };

  var subtotal = ventaServidor
    ? Number(ventaServidor.subtotal)
    : totales.subtotal;
  var descuento = ventaServidor
    ? Number(ventaServidor.descuento || 0)
    : totales.descuento;
  var descuentoPorcentaje = ventaServidor
    ? Number(ventaServidor.descuento_porcentaje || 0)
    : totales.descuentoPorcentaje;
  var iva = ventaServidor ? Number(ventaServidor.iva) : totales.iva;
  var total = ventaServidor
    ? Number(ventaServidor.total_factura)
    : totales.total;

  return {
    numero: generarNumeroComprobante(),
    tipo_comprobante: obtenerTipoComprobanteActual(),
    fecha: new Date(),
    cliente: {
      nombre: cliente.nombre_completo || "Consumidor Final",
      identificacion: cliente.cedula || "9999999999999",
      direccion: cliente.direccion || "ESPE - LAB 301",
      correo: cliente.correo || "",
    },
    items: Array.from(carrito.values()).map(function (item) {
      return {
        codigo: item.codigo,
        nombre: item.nombre,
        cantidad: item.cantidad,
        precio: Number(item.precio),
        subtotal: Number(item.precio) * Number(item.cantidad),
      };
    }),
    subtotal: subtotal,
    descuento: descuento,
    descuentoPorcentaje: descuentoPorcentaje,
    iva: iva,
    total: total,
    pago: pago,
    cambio: pago - total > 0 ? pago - total : 0,
  };
}

function imprimirVentaPdf() {
  if (!ventaProcesadaActual) {
    alert("Primero procesa una venta para generar el PDF.");
    return;
  }

  if (!window.jspdf || !window.jspdf.jsPDF) {
    alert("No se pudo cargar la libreria PDF.");
    return;
  }

  var jsPDF = window.jspdf.jsPDF;
  var doc = new jsPDF({ orientation: "portrait", unit: "mm", format: "a4" });
  var venta = ventaProcesadaActual;

  // Colores corporativos (basado en el verde de tu interfaz)
  var colorPrincipal = [45, 106, 79];

  // --- 1. ENCABEZADO DE LA EMPRESA ---
  doc.setFontSize(22);
  doc.setTextColor(colorPrincipal[0], colorPrincipal[1], colorPrincipal[2]);
  doc.setFont("helvetica", "bold");
  doc.text("GALAXYHUB STORE", 105, 20, { align: "center" });

  doc.setFontSize(10);
  doc.setTextColor(120, 120, 120);
  doc.setFont("helvetica", "normal");
  doc.text("Tu tienda de tecnología de confianza", 105, 26, { align: "center" });

  // Línea separadora
  doc.setDrawColor(200, 200, 200);
  doc.line(14, 32, 196, 32);

  // --- 2. DATOS DEL COMPROBANTE (Alineados a la derecha) ---
  doc.setFontSize(10);
  doc.setTextColor(0, 0, 0);
  doc.setFont("helvetica", "bold");
  doc.text(venta.tipo_comprobante.toUpperCase() + " N°:", 135, 42);
  doc.setFont("helvetica", "normal");
  doc.text(venta.numero, 165, 42);

  doc.setFont("helvetica", "bold");
  doc.text("Fecha:", 135, 48);
  doc.setFont("helvetica", "normal");
  doc.text(venta.fecha.toLocaleString("es-EC"), 165, 48);

  // --- 3. DATOS DEL CLIENTE (Alineados a la izquierda) ---
  doc.setFont("helvetica", "bold");
  doc.text("Facturar a:", 14, 42);

  doc.setFont("helvetica", "normal");
  doc.text("Cliente: " + venta.cliente.nombre, 14, 48);
  doc.text("RUC/CI: " + venta.cliente.identificacion, 14, 54);
  doc.text("Dirección: " + venta.cliente.direccion, 14, 60);
  if (venta.cliente.correo) {
    doc.text("Correo: " + venta.cliente.correo, 14, 66);
  }

  // --- 4. TABLA DE PRODUCTOS ---
  var tablaBody = venta.items.map(function (item) {
    return [
      String(item.codigo || ""),
      String(item.nombre || ""),
      String(item.cantidad || 0),
      "$ " + formatMoney(item.precio),
      "$ " + formatMoney(item.subtotal),
    ];
  });

  doc.autoTable({
    startY: venta.cliente.correo ? 75 : 70,
    head: [["Código", "Descripción", "Cant.", "P. Unitario", "Subtotal"]],
    body: tablaBody,
    theme: 'striped', // Filas sombreadas alternas
    styles: {
      fontSize: 9,
      cellPadding: 4,
      textColor: [40, 40, 40],
    },
    headStyles: {
      fillColor: colorPrincipal,
      textColor: [255, 255, 255],
      fontStyle: 'bold',
      halign: 'center'
    },
    columnStyles: {
      0: { halign: 'center', cellWidth: 30 },
      1: { halign: 'left' },
      2: { halign: 'center', cellWidth: 15 },
      3: { halign: 'right', cellWidth: 30 },
      4: { halign: 'right', cellWidth: 30 },
    }
  });

  // --- 5. BLOQUE DE TOTALES ---
  var finalY = doc.lastAutoTable.finalY + 10;
  var labelX = 140; // Posición X para las etiquetas
  var valueX = 196; // Margen derecho máximo para alinear los montos

  // Función ayudante para imprimir líneas de totales alineadas
  function imprimirLineaTotal(etiqueta, valor, y, negrita) {
    doc.setFont("helvetica", negrita ? "bold" : "normal");
    doc.setTextColor(0, 0, 0);
    doc.text(etiqueta, labelX, y);
    doc.text(valor, valueX, y, { align: "right" });
  }

  imprimirLineaTotal("Subtotal:", "$ " + formatMoney(venta.subtotal), finalY, false);
  
  if (venta.descuento > 0) {
    finalY += 6;
    var descPorcentaje = Math.round((venta.descuentoPorcentaje || 0) * 100);
    doc.setTextColor(colorPrincipal[0], colorPrincipal[1], colorPrincipal[2]);
    doc.setFont("helvetica", "normal");
    doc.text("Descuento (" + descPorcentaje + "%):", labelX, finalY);
    doc.text("- $ " + formatMoney(venta.descuento), valueX, finalY, { align: "right" });
  }

  finalY += 6;
  imprimirLineaTotal("IVA (15%):", "$ " + formatMoney(venta.iva), finalY, false);
  
  // Línea divisoria antes del total
  finalY += 4;
  doc.setDrawColor(200, 200, 200);
  doc.line(labelX, finalY, valueX, finalY);

  finalY += 6;
  doc.setFontSize(12);
  imprimirLineaTotal("TOTAL:", "$ " + formatMoney(venta.total), finalY, true);

  // --- 6. DATOS DE PAGO Y CAMBIO ---
  doc.setFontSize(9);
  finalY += 12;
  imprimirLineaTotal("Pago recibido:", "$ " + formatMoney(venta.pago), finalY, false);
  finalY += 5;
  imprimirLineaTotal("Cambio:", "$ " + formatMoney(venta.cambio), finalY, false);

  // --- 7. PIE DE PÁGINA ---
  doc.setFontSize(10);
  doc.setTextColor(150, 150, 150);
  doc.setFont("helvetica", "italic");
  doc.text("¡Gracias por su compra!", 105, 280, { align: "center" });

  doc.save(venta.numero + ".pdf");
}

function normalizarDocumento(value) {
  return String(value || "").replace(/\D+/g, "");
}

function formatearTipoDocumento(tipo) {
  if (tipo === "ruc") {
    return "RUC";
  }

  if (tipo === "cedula") {
    return "Cédula";
  }

  return "Documento";
}

function actualizarEstadoClienteUI() {
  var checkboxConsumidor = document.getElementById("cliente-consumidor-final");
  var buscadorCliente = document.getElementById("cliente-buscador");

  if (!checkboxConsumidor || !buscadorCliente) {
    return;
  }

  buscadorCliente.disabled = checkboxConsumidor.checked;
}

function actualizarResumenCliente() {
  var etiqueta = document.getElementById("cliente-seleccionado");
  var detalle = document.getElementById("cliente-seleccionado-detalle");
  var checkboxConsumidor = document.getElementById("cliente-consumidor-final");
  var badgeFrecuente = document.getElementById("cliente-frecuente-badge");

  if (!etiqueta || !detalle) {
    return;
  }

  if (checkboxConsumidor && checkboxConsumidor.checked) {
    etiqueta.textContent = "Consumidor Final";
    detalle.textContent =
      "Nombre: Consumidor Final · Identificación/RUC: 9999999999999 · Dirección: ESPE - LAB 301";
    if (badgeFrecuente) {
      badgeFrecuente.style.display = "none";
    }
    renderTotales();
    return;
  }

  if (!clienteSeleccionado || !clienteSeleccionado.id) {
    etiqueta.textContent = "Sin cliente seleccionado";
    detalle.textContent = "Activa consumidor final o busca un cliente";
    if (badgeFrecuente) {
      badgeFrecuente.style.display = "none";
    }
    renderTotales();
    return;
  }

  etiqueta.textContent =
    clienteSeleccionado.nombre_completo ||
    clienteSeleccionado.cedula ||
    "Cliente";

  var partesDetalle = [];
  if (clienteSeleccionado.tipo_documento) {
    partesDetalle.push(
      formatearTipoDocumento(clienteSeleccionado.tipo_documento),
    );
  }
  if (clienteSeleccionado.cedula) {
    partesDetalle.push(clienteSeleccionado.cedula);
  }
  if (clienteSeleccionado.direccion) {
    partesDetalle.push(clienteSeleccionado.direccion);
  }
  if (clienteSeleccionado.correo) {
    partesDetalle.push(clienteSeleccionado.correo);
  }

  detalle.textContent = partesDetalle.length
    ? partesDetalle.join(" · ")
    : "Cliente seleccionado";

  if (badgeFrecuente) {
    var descuentoPorcentaje = calcularDescuentoPorcentaje();
    if (descuentoPorcentaje > 0) {
      badgeFrecuente.textContent =
        "🏷️ Cliente frecuente · " +
        Math.round(descuentoPorcentaje * 100) +
        "% de descuento";
      badgeFrecuente.style.display = "inline-block";
    } else {
      badgeFrecuente.style.display = "none";
    }
  }

  renderTotales();
}

function actualizarModoConsumidorFinal(activo) {
  var checkboxConsumidor = document.getElementById("cliente-consumidor-final");
  var buscadorCliente = document.getElementById("cliente-buscador");

  if (checkboxConsumidor) {
    checkboxConsumidor.checked = Boolean(activo);
  }

  if (activo) {
    clienteSeleccionado = {
      id: null,
      cedula: "9999999999999",
      nombre_completo: "Consumidor Final",
      correo: "",
      direccion: "ESPE - LAB 301",
      tipo_documento: null,
    };
    if (buscadorCliente) {
      buscadorCliente.value = "";
    }
  } else {
    clienteSeleccionado = {
      id: null,
      cedula: "",
      nombre_completo: "",
      correo: "",
      direccion: "",
      tipo_documento: null,
    };
    if (buscadorCliente) {
      buscadorCliente.value = "";
    }
  }

  resultadosClientes = [];
  ocultarResultadosClientes();
  actualizarEstadoClienteUI();
  actualizarResumenCliente();
}

function crearContenedorResultados() {
  var buscador = document.getElementById("buscador-productos");
  var buscadorWrapper = document.getElementById("buscador-wrapper");
  if (!buscador || document.getElementById("resultados-productos")) {
    return;
  }

  var wrapper = document.createElement("div");
  wrapper.id = "resultados-productos";
  wrapper.className = "list-group shadow-sm position-absolute w-100";
  wrapper.style.zIndex = "1080";
  wrapper.style.display = "none";
  wrapper.style.top = "calc(100% + 6px)";
  wrapper.style.left = "0";

  var parent = buscadorWrapper || buscador.parentElement;
  parent.style.position = "relative";
  parent.appendChild(wrapper);
}

function crearContenedorClientes() {
  var clienteInput = document.getElementById("cliente-buscador");
  var clienteWrapper = document.getElementById("cliente-wrapper");
  if (!clienteInput || document.getElementById("resultados-clientes")) {
    return;
  }

  var wrapper = document.createElement("div");
  wrapper.id = "resultados-clientes";
  wrapper.className = "list-group shadow-sm position-absolute w-100";
  wrapper.style.zIndex = "1080";
  wrapper.style.display = "none";
  wrapper.style.top = "calc(100% + 6px)";
  wrapper.style.left = "0";

  var parent = clienteWrapper || clienteInput.parentElement;
  parent.style.position = "relative";
  parent.appendChild(wrapper);
}

function mostrarResultados() {
  var wrapper = document.getElementById("resultados-productos");

  if (!wrapper) {
    return;
  }

  if (!resultadosBusqueda.length) {
    wrapper.style.display = "none";
    wrapper.innerHTML = "";
    return;
  }

  wrapper.innerHTML = resultadosBusqueda
    .map(function (producto) {
      return (
        '<button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" data-product-id="' +
        Number(producto.id) +
        '">' +
        "<span>" +
        (producto.nombre_producto || "") +
        "</span>" +
        '<small class="text-muted">' +
        (producto.codigo_barras || "") +
        " | $ " +
        formatMoney(producto.precio_actual) +
        "</small>" +
        "</button>"
      );
    })
    .join("");

  wrapper.style.display = "block";
}

function ocultarResultados() {
  var wrapper = document.getElementById("resultados-productos");
  if (!wrapper) {
    return;
  }

  wrapper.style.display = "none";
}

function mostrarResultadosClientes() {
  var wrapper = document.getElementById("resultados-clientes");

  if (!wrapper) {
    return;
  }

  if (!resultadosClientes.length) {
    wrapper.style.display = "none";
    wrapper.innerHTML = "";
    return;
  }

  wrapper.innerHTML = resultadosClientes
    .map(function (cliente) {
      return (
        '<button type="button" class="list-group-item list-group-item-action" data-cliente-id="' +
        Number(cliente.id) +
        '">' +
        '<div class="d-flex justify-content-between align-items-center">' +
        "<span><strong>" +
        (cliente.nombre_completo || "") +
        '</strong><br><small class="text-muted">' +
        (cliente.cedula || "") +
        " | " +
        (cliente.correo || "") +
        "</small></span>" +
        "</div>" +
        "</button>"
      );
    })
    .join("");

  wrapper.style.display = "block";
}

function ocultarResultadosClientes() {
  var wrapper = document.getElementById("resultados-clientes");
  if (!wrapper) {
    return;
  }

  wrapper.style.display = "none";
}

function buscarProductoPorId(id) {
  return resultadosBusqueda.find(function (item) {
    return Number(item.id) === Number(id);
  });
}

function buscarClientePorId(id) {
  return resultadosClientes.find(function (item) {
    return Number(item.id) === Number(id);
  });
}

function renderClienteSeleccionado() {
  actualizarResumenCliente();
}

function agregarAlCarrito(producto) {
  var id = Number(producto.id);
  var stockDisponible = Number(producto.stock_disponible || 0);
  var existente = carrito.get(id);

  if (existente) {
    if (existente.cantidad >= stockDisponible) {
      alert(
        "No puedes agregar más unidades. El producto solo tiene " +
          stockDisponible +
          " en stock.",
      );
      return;
    }

    existente.cantidad += 1;
    carrito.set(id, existente);
  } else {
    if (stockDisponible <= 0) {
      alert("Este producto no tiene stock disponible.");
      return;
    }

    carrito.set(id, {
      id: id,
      nombre: producto.nombre_producto,
      codigo: producto.codigo_barras,
      precio: Number(producto.precio_actual),
      stock: stockDisponible,
      cantidad: 1,
    });
  }

  renderCarrito();
}

function cambiarCantidad(id, delta) {
  var item = carrito.get(Number(id));
  if (!item) {
    return;
  }

  if (delta > 0 && item.cantidad >= item.stock) {
    alert(
      "No puedes superar el stock disponible de " + item.stock + " unidades.",
    );
    return;
  }

  item.cantidad += delta;
  if (item.cantidad <= 0) {
    carrito.delete(Number(id));
  } else {
    carrito.set(Number(id), item);
  }

  renderCarrito();
}

function quitarDelCarrito(id) {
  carrito.delete(Number(id));
  renderCarrito();
}

function calcularDescuentoPorcentaje() {
  if (!clienteSeleccionado || !clienteSeleccionado.id) {
    return 0;
  }

  var cedula = normalizarDocumento(clienteSeleccionado.cedula);
  if (cedula === "9999999999999") {
    return 0;
  }

  var totalGastado = Number(clienteSeleccionado?.total_gastado || 0);

  if (totalGastado >= 5000) {
    return 0.15;
  }

  if (totalGastado >= 2000) {
    return 0.1;
  }

  return 0;
}

function calcularTotales() {
  var subtotal = 0;

  carrito.forEach(function (item) {
    subtotal += item.precio * item.cantidad;
  });

  var descuentoPorcentaje = calcularDescuentoPorcentaje();
  var descuento = subtotal * descuentoPorcentaje;
  var baseImponible = subtotal - descuento;
  var iva = baseImponible * 0.15;
  var total = baseImponible + iva;

  return {
    subtotal: subtotal,
    descuento: descuento,
    descuentoPorcentaje: descuentoPorcentaje,
    iva: iva,
    total: total,
    esFrecuente: descuentoPorcentaje > 0,
  };
}

function renderTotales() {
  var totales = calcularTotales();
  document.getElementById("subtotal-resumen").textContent =
    "$ " + formatMoney(totales.subtotal);
  document.getElementById("iva-resumen").textContent =
    "$ " + formatMoney(totales.iva);
  document.getElementById("total-resumen").textContent =
    "$ " + formatMoney(totales.total);

  var filaDescuento = document.getElementById("descuento-fila");

  if (filaDescuento) {
    var etiquetaDescuento = document.getElementById("descuento-etiqueta");
    var totalGastado = Number(clienteSeleccionado?.total_gastado || 0);

    if (
      clienteSeleccionado &&
      clienteSeleccionado.id &&
      totalGastado >= 2000 &&
      totales.descuentoPorcentaje > 0 &&
      totales.descuento > 0
    ) {
      // Muestra la fila usando clases de Bootstrap
      filaDescuento.classList.remove("d-none");
      filaDescuento.classList.add("d-flex");

      if (etiquetaDescuento) {
        etiquetaDescuento.textContent =
          "🏷️ Descuento cliente frecuente (" +
          Math.round(totales.descuentoPorcentaje * 100) +
          "%)";
      }

      document.getElementById("descuento-resumen").textContent =
        "- $ " + formatMoney(totales.descuento);
    } else {
      // Oculta la fila usando clases de Bootstrap
      filaDescuento.classList.remove("d-flex");
      filaDescuento.classList.add("d-none");
    }
  }

  var pago = obtenerPagoSeguro();
  var cambio = pago - totales.total;
  document.getElementById("cambio-texto").textContent =
    "$ " + formatMoney(cambio > 0 ? cambio : 0);
}

function renderCarrito() {
  var body = document.getElementById("carrito-body");

  if (!carrito.size) {
    body.innerHTML =
      '<tr><td colspan="5" class="text-center text-secondary py-4">El carrito esta vacio</td></tr>';
    renderTotales();
    return;
  }

  body.innerHTML = Array.from(carrito.values())
    .map(function (item) {
      var subtotal = item.precio * item.cantidad;
      var puedeIncrementar = item.cantidad < item.stock;
      return (
        "<tr>" +
        "<td><strong>" +
        item.nombre +
        '</strong><br><small class="text-muted">' +
        item.codigo +
        "</small></td>" +
        '<td class="text-center">' +
        '<button class="btn btn-sm btn-outline-secondary me-1" data-minus-id="' +
        item.id +
        '">-</button>' +
        '<span class="mx-1 fw-semibold">' +
        item.cantidad +
        "</span>" +
        '<button class="btn btn-sm btn-outline-secondary ms-1" data-plus-id="' +
        item.id +
        '"' +
        (puedeIncrementar ? "" : " disabled") +
        ">+</button>" +
        "</td>" +
        '<td class="text-end">$ ' +
        formatMoney(item.precio) +
        "</td>" +
        '<td class="text-end">$ ' +
        formatMoney(subtotal) +
        "</td>" +
        '<td class="text-center"><button class="btn btn-sm btn-outline-danger" data-remove-id="' +
        item.id +
        '">Quitar</button></td>' +
        "</tr>"
      );
    })
    .join("");

  renderTotales();
}

async function buscarProductos(termino) {
  var query = (termino || "").trim();

  if (!query) {
    resultadosBusqueda = [];
    mostrarResultados();
    return;
  }

  try {
    var response = await fetch(
      apiProductosUrl + "?q=" + encodeURIComponent(query),
    );
    if (!response.ok) {
      throw new Error("Error de busqueda");
    }

    var data = await response.json();
    resultadosBusqueda = Array.isArray(data) ? data : [];
    mostrarResultados();
  } catch (error) {
    console.error(error);
    resultadosBusqueda = [];
    mostrarResultados();
  }
}

async function buscarClientes(termino) {
  var query = (termino || "").trim();

  try {
    var response = await fetch(
      apiClientesUrl + "?q=" + encodeURIComponent(query),
      {
        headers: {
          Accept: "application/json",
        },
      },
    );

    if (!response.ok) {
      throw new Error("Error de busqueda de clientes");
    }

    var data = await response.json();
    resultadosClientes = Array.isArray(data) ? data : [];
    mostrarResultadosClientes();
  } catch (error) {
    console.error(error);
    resultadosClientes = [];
    mostrarResultadosClientes();
  }
}

function seleccionarCliente(cliente) {
  clienteSeleccionado = cliente || {
    id: null,
    cedula: "",
    nombre_completo: "Consumidor Final",
    correo: "",
    direccion: "La del establecimiento",
    tipo_documento: null,
  };

  var buscadorCliente = document.getElementById("cliente-buscador");
  var checkboxConsumidor = document.getElementById("cliente-consumidor-final");
  if (buscadorCliente) {
    buscadorCliente.value = clienteSeleccionado.id
      ? clienteSeleccionado.nombre_completo + " - " + clienteSeleccionado.cedula
      : "";
  }

  if (checkboxConsumidor) {
    checkboxConsumidor.checked = !clienteSeleccionado.id;
  }

  renderClienteSeleccionado();
  resultadosClientes = [];
  ocultarResultadosClientes();
  actualizarEstadoClienteUI();
}

function activarConsumidorFinal() {
  actualizarModoConsumidorFinal(true);
}

function construirPayloadVenta() {
  return {
    cliente_id:
      clienteSeleccionado && clienteSeleccionado.id
        ? Number(clienteSeleccionado.id)
        : null,
    cliente_cedula:
      (clienteSeleccionado && clienteSeleccionado.cedula) || "9999999999999",
    cliente_nombre:
      (clienteSeleccionado && clienteSeleccionado.nombre_completo) ||
      "Consumidor Final",
    cliente_correo: (clienteSeleccionado && clienteSeleccionado.correo) || "",
    items: Array.from(carrito.values()).map(function (item) {
      return {
        producto_id: item.id,
        cantidad: item.cantidad,
      };
    }),
  };
}

async function procesarVentaDemo() {
  if (!carrito.size) {
    alert("Agrega productos antes de procesar la venta.");
    return;
  }

  var totales = calcularTotales();
  var pago = obtenerPagoSeguro();
  if (pago < totales.total) {
    alert("El pago debe ser mayor o igual al total de la venta.");
    return;
  }

  var procesarBtn = document.getElementById("procesar-venta-btn");
  if (procesarBtn) {
    procesarBtn.disabled = true;
  }

  try {
    var response = await fetch("Backend/api_venta.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(construirPayloadVenta()),
    });

    var resultado = await response.json();

    if (!response.ok || resultado.estado !== "exito") {
      alert(
        "No se pudo registrar la venta: " +
          (resultado.mensaje || "Error desconocido."),
      );
      return;
    }

    ventaProcesadaActual = crearVentaProcesada(resultado.venta);
    ventaProcesadaActual.id = resultado.venta.id;
    actualizarEstadoBotonImprimir();

    carrito.clear();
    renderCarrito();
    document.getElementById("pago-input").value = "";
    renderTotales();

    alert("Venta procesada correctamente. Ya puedes imprimir el recibo/PDF.");
  } catch (error) {
    console.error(error);
    alert("No se pudo conectar con el servidor para registrar la venta.");
  } finally {
    if (procesarBtn) {
      procesarBtn.disabled = false;
    }
  }
}

document.addEventListener("DOMContentLoaded", function () {
  crearContenedorResultados();
  crearContenedorClientes();
  renderCarrito();
  renderClienteSeleccionado();

  var buscador = document.getElementById("buscador-productos");
  var buscadorCliente = document.getElementById("cliente-buscador");
  var wrapper = document.getElementById("resultados-productos");
  var wrapperClientes = document.getElementById("resultados-clientes");
  var pagoInput = document.getElementById("pago-input");
  var procesarBtn = document.getElementById("procesar-venta-btn");
  var imprimirFacturaBtn = document.getElementById("imprimir-factura-btn");
  var clienteConsumidorCheck = document.getElementById(
    "cliente-consumidor-final",
  );

  actualizarModoConsumidorFinal(true);
  actualizarEstadoBotonImprimir();

  buscador.addEventListener("input", function (event) {
    buscarProductos(event.target.value);
  });

  buscador.addEventListener("keydown", function (event) {
    if (event.key !== "Enter") {
      return;
    }

    event.preventDefault();
    if (!resultadosBusqueda.length) {
      return;
    }

    agregarAlCarrito(resultadosBusqueda[0]);
    buscador.value = "";
    resultadosBusqueda = [];
    ocultarResultados();
  });

  buscadorCliente.addEventListener("focus", function () {
    buscarClientes(buscadorCliente.value);
  });

  buscadorCliente.addEventListener("click", function () {
    buscarClientes(buscadorCliente.value);
  });

  buscadorCliente.addEventListener("input", function (event) {
    buscarClientes(event.target.value);
  });

  buscadorCliente.addEventListener("keydown", function (event) {
    if (event.key !== "Enter") {
      return;
    }

    event.preventDefault();
    if (!resultadosClientes.length) {
      return;
    }

    if (buscadorCliente.disabled) {
      return;
    }

    seleccionarCliente(resultadosClientes[0]);
  });

  clienteConsumidorCheck.addEventListener("change", function (event) {
    if (event.target.checked) {
      activarConsumidorFinal();
      return;
    }

    clienteSeleccionado = {
      id: null,
      cedula: "",
      nombre_completo: "",
      correo: "",
      tipo_documento: null,
    };
    resultadosClientes = [];
    ocultarResultadosClientes();
    actualizarEstadoClienteUI();
    actualizarResumenCliente();
  });

  document.addEventListener("click", function (event) {
    var target = event.target;
    var productButton = target.closest("[data-product-id]");
    var clienteButton = target.closest("[data-cliente-id]");
    var minusButton = target.closest("[data-minus-id]");
    var plusButton = target.closest("[data-plus-id]");
    var removeButton = target.closest("[data-remove-id]");

    if (productButton) {
      var producto = buscarProductoPorId(
        productButton.getAttribute("data-product-id"),
      );
      if (producto) {
        agregarAlCarrito(producto);
        buscador.value = "";
        resultadosBusqueda = [];
        ocultarResultados();
      }
      return;
    }

    if (clienteButton) {
      var cliente = buscarClientePorId(
        clienteButton.getAttribute("data-cliente-id"),
      );
      if (cliente) {
        seleccionarCliente(cliente);
        if (clienteConsumidorCheck) {
          clienteConsumidorCheck.checked = false;
        }
        actualizarResumenCliente();
        actualizarEstadoClienteUI();
      }
      return;
    }

    if (minusButton) {
      cambiarCantidad(minusButton.getAttribute("data-minus-id"), -1);
      return;
    }

    if (plusButton) {
      cambiarCantidad(plusButton.getAttribute("data-plus-id"), 1);
      return;
    }

    if (removeButton) {
      quitarDelCarrito(removeButton.getAttribute("data-remove-id"));
      return;
    }

    if (wrapper && !wrapper.contains(target) && target !== buscador) {
      ocultarResultados();
    }

    if (
      wrapperClientes &&
      !wrapperClientes.contains(target) &&
      target !== buscadorCliente
    ) {
      ocultarResultadosClientes();
    }
  });

  pagoInput.addEventListener("keydown", bloquearSignosPago);
  pagoInput.addEventListener("input", function (event) {
    var valor = sanitizarDecimal(event.target.value);
    if (event.target.value !== valor) {
      event.target.value = valor;
    }

    renderTotales();
  });
  if (imprimirFacturaBtn) {
    imprimirFacturaBtn.addEventListener("click", imprimirVentaPdf);
  }

  procesarBtn.addEventListener("click", function () {
    if (
      clienteConsumidorCheck &&
      !clienteConsumidorCheck.checked &&
      !clienteSeleccionado.id
    ) {
      alert(
        "Selecciona un cliente o activa consumidor final antes de procesar la venta.",
      );
      return;
    }

    procesarVentaDemo();
  });
});
