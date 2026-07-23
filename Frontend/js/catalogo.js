var apiProductosUrl = "Backend/api_productos.php";
var modalProducto = null;
var modoEdicion = false;
var productosCache = [];

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

function sanitizarEntero(value) {
  return String(value || "").replace(/\D+/g, "");
}

function bloquearSignosNumero(event, permiteDecimal) {
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
    return;
  }

  if (!permiteDecimal && (event.key === "." || event.key === ",")) {
    event.preventDefault();
  }
}

function actualizarEstadoEdicionProducto() {
  var soloEdicion = Boolean(modoEdicion);
  var camposSoloLectura = ["prod-codigo", "prod-nombre"];

  camposSoloLectura.forEach(function (id) {
    var elemento = document.getElementById(id);
    if (elemento) {
      elemento.readOnly = soloEdicion;
    }
  });
}

function renderProductos(productos) {
  var cuerpoTabla = document.getElementById("cuerpo-tabla");

  if (!cuerpoTabla) {
    return;
  }

  if (!productos.length) {
    cuerpoTabla.innerHTML =
      '<tr><td colspan="5" class="text-center text-muted py-4">No hay productos para mostrar.</td></tr>';
    return;
  }

  cuerpoTabla.innerHTML = productos
    .map(function (producto) {

      var stock = Number(producto.stock_disponible || 0);
      var colorStock = "success";

      if (stock === 0) {
        colorStock = "danger";
      } else if (stock < 10) {
        colorStock = "warning";
      }

      return (
        "<tr>" +
        "<td>" +
        (producto.codigo_barras || "") +
        "</td>" +
        "<td>" +
        (producto.nombre_producto || "") +
        "</td>" +
        "<td>$ " +
        formatMoney(producto.precio_actual) +
        "</td>" +
        "<td class='text-" +
        colorStock +
        " fw-bold'>" +
        stock +
        "</td>" +
        "<td>" +
        '<button class="btn btn-sm btn-outline-primary me-2" onclick="editarProducto(' +
        Number(producto.id) +
        ')">Editar</button>' +
        '<button class="btn btn-sm btn-outline-danger" onclick="eliminarProducto(' +
        Number(producto.id) +
        ')">Eliminar</button>' +
        "</td>" +
        "</tr>"
      );
    })
    .join("");
}

async function cargarProductos(query) {
  var termino = query || "";
  var url = apiProductosUrl + "?q=" + encodeURIComponent(termino);

  try {
    var response = await fetch(url);
    if (!response.ok) {
      throw new Error("No se pudo cargar el catalogo");
    }

    var data = await response.json();
    productosCache = Array.isArray(data) ? data : [];
    renderProductos(productosCache);
  } catch (error) {
    console.error(error);
    renderProductos([]);
  }
}

function mostrarErrorProducto(mensaje) {
  var elemento = document.getElementById("prod-codigo-error");
  if (!elemento) {
    return;
  }

  if (mensaje) {
    elemento.textContent = mensaje;
    elemento.classList.remove("d-none");
  } else {
    elemento.textContent = "";
    elemento.classList.add("d-none");
  }
}

function limpiarFormularioProducto() {
  document.getElementById("prod-id").value = "";
  document.getElementById("prod-codigo").value = "";
  document.getElementById("prod-nombre").value = "";
  document.getElementById("prod-precio").value = "";
  document.getElementById("prod-stock").value = "";
  mostrarErrorProducto("");
}

window.abrirModal = function () {
  modoEdicion = false;
  limpiarFormularioProducto();
  actualizarEstadoEdicionProducto();
  document.getElementById("modalTitulo").textContent = "Nuevo Producto";
  modalProducto.show();
};

window.editarProducto = function (id) {
  var producto = productosCache.find(function (item) {
    return Number(item.id) === Number(id);
  });

  if (!producto) {
    return;
  }

  modoEdicion = true;
  document.getElementById("modalTitulo").textContent = "Editar Producto";
  document.getElementById("prod-id").value = producto.id;
  document.getElementById("prod-codigo").value = producto.codigo_barras || "";
  document.getElementById("prod-nombre").value = producto.nombre_producto || "";
  document.getElementById("prod-precio").value = producto.precio_actual || "";
  document.getElementById("prod-stock").value = producto.stock_disponible || 0;
  mostrarErrorProducto("");
  actualizarEstadoEdicionProducto();
  modalProducto.show();
};

window.guardarProducto = async function () {
  var id = document.getElementById("prod-id").value;
  var codigo = document.getElementById("prod-codigo").value.trim();
  var nombre = document.getElementById("prod-nombre").value.trim();
  var precio = Number(
    sanitizarDecimal(document.getElementById("prod-precio").value),
  );
  var stock = Number(
    sanitizarEntero(document.getElementById("prod-stock").value),
  );

  if (
    !codigo ||
    !nombre ||
    !Number.isFinite(precio) ||
    !Number.isFinite(stock)
  ) {
    alert("Completa todos los campos del producto.");
    return;
  }

  if (precio < 0.01) {
    alert("El precio no permitido");
    return;
  } else if (stock < 1) {
    alert("El stock no permitido");
    return;
  }

  mostrarErrorProducto("");

  var payload = {
    codigo: codigo,
    nombre: nombre,
    precio: precio,
    stock: stock,
    id: id ? Number(id) : null,
  };

  try {
    var response = await fetch(apiProductosUrl, {
      method: modoEdicion ? "PUT" : "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify(payload),
    });

    var data = await response.json();

    if (!response.ok || (data.estado && data.estado !== "exito")) {
      throw new Error(data.mensaje || "No se pudo guardar el producto");
    }

    modalProducto.hide();
    await cargarProductos(
      document.getElementById("input-busqueda").value.trim(),
    );
  } catch (error) {
    console.error(error);
    mostrarErrorProducto(
      error.message ||
        "No se pudo guardar el producto. Revisa la conexion con el servidor.",
    );
  }
};

window.eliminarProducto = async function (id) {
  if (!confirm("Deseas eliminar este producto?")) {
    return;
  }

  try {
    var response = await fetch(
      apiProductosUrl + "?id=" + encodeURIComponent(String(id)),
      {
        method: "DELETE",
      },
    );

    if (!response.ok) {
      throw new Error("No se pudo eliminar");
    }

    await cargarProductos(
      document.getElementById("input-busqueda").value.trim(),
    );
  } catch (error) {
    console.error(error);
    alert("No se pudo eliminar el producto.");
  }
};

document.addEventListener("DOMContentLoaded", function () {
  var modalElement = document.getElementById("modalProducto");
  if (typeof bootstrap !== "undefined" && modalElement) {
    modalProducto = new bootstrap.Modal(modalElement);
    
    // Apagar la cámara automáticamente cuando se oculta el modal
    modalElement.addEventListener('hidden.bs.modal', function () {
      detenerScannerModal();
    });
  }

  var inputBusqueda = document.getElementById("input-busqueda");
  var inputPrecio = document.getElementById("prod-precio");
  var inputStock = document.getElementById("prod-stock");
  if (inputBusqueda) {
    inputBusqueda.addEventListener("input", function (event) {
      cargarProductos(event.target.value.trim());
    });
  }

  if (inputPrecio) {
    inputPrecio.addEventListener("keydown", function (event) {
      bloquearSignosNumero(event, true);
    });
    inputPrecio.addEventListener("input", function (event) {
      var valor = sanitizarDecimal(event.target.value);
      if (event.target.value !== valor) {
        event.target.value = valor;
      }
    });
  }

  if (inputStock) {
    inputStock.addEventListener("keydown", function (event) {
      bloquearSignosNumero(event, false);
    });
    inputStock.addEventListener("input", function (event) {
      var valor = sanitizarEntero(event.target.value);
      if (event.target.value !== valor) {
        event.target.value = valor;
      }
    });
  }

  cargarProductos("");
});
// Variable para almacenar la instancia del escáner del modal
var modalScannerInstance = null;

function abrirScannerModal() {
  var lectorModal = document.getElementById("reader-modal");
  
  if (modalScannerInstance) {
    return;
  }

  lectorModal.style.display = "block";
  modalScannerInstance = new Html5Qrcode("reader-modal");

  modalScannerInstance.start(
    { facingMode: "environment" },
    {
      fps: 10,
      qrbox: { width: 300, height: 120 } // Formato rectangular para códigos de barras
    },
    function (codigoLeido) {
      document.getElementById("prod-codigo").value = codigoLeido;
      detenerScannerModal();
      mostrarErrorProducto("");
    },
    function (errorMessage) {
      // Ignoramos los errores silenciosos de lectura
    }
  ).catch(function (err) {
    console.error("Error al iniciar el escáner:", err);
    alert("No se pudo acceder a la cámara.");
    lectorModal.style.display = "none";
    modalScannerInstance = null;
  });
}

function detenerScannerModal() {
  var lectorModal = document.getElementById("reader-modal");
  
  if (modalScannerInstance) {
    modalScannerInstance.stop().then(function () {
      modalScannerInstance.clear(); // ¡Esta línea es la magia para que no se congele!
      lectorModal.style.display = "none";
      modalScannerInstance = null;
    }).catch(function (err) {
      // Si falla el stop por alguna razón, forzamos la limpieza
      modalScannerInstance.clear();
      lectorModal.style.display = "none";
      modalScannerInstance = null;
    });
  } else {
    lectorModal.style.display = "none";
  }
}