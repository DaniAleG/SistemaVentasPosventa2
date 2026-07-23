var apiClientesUrl = 'Backend/api_clientes.php';
var modalCliente = null;
var modoEdicion = false;
var clientesCache = [];

function actualizarEstadoEdicionCliente() {
    var soloEdicion = Boolean(modoEdicion);
    var camposSoloLectura = ['cliente-cedula', 'cliente-nombre', 'cliente-apellido'];

    camposSoloLectura.forEach(function (id) {
        var elemento = document.getElementById(id);
        if (elemento) {
            elemento.readOnly = soloEdicion;
        }
    });

    var switchDocumento = document.getElementById('cliente-es-ruc');
    if (switchDocumento) {
        switchDocumento.disabled = soloEdicion;
    }
}

function escapeHtml(value) {
    return String(value || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function formatDate(value) {
    if (!value) {
        return 'N/D';
    }

    var fecha = new Date(value);
    if (isNaN(fecha.getTime())) {
        return escapeHtml(value);
    }

    return fecha.toLocaleDateString('es-ES', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
    });
}

function limpiarFormularioCliente() {
    document.getElementById('cliente-id').value = '';
    document.getElementById('cliente-es-ruc').checked = false;
    document.getElementById('cliente-cedula').value = '';
    document.getElementById('cliente-nombre').value = '';
    document.getElementById('cliente-apellido').value = '';
    document.getElementById('cliente-correo').value = '';
    limpiarErroresCliente();
    actualizarAyudaDocumento();
}

function normalizarDocumento(value) {
    return String(value || '').replace(/\D+/g, '');
}

function normalizarNombre(value) {
    return String(value || '')
        .replace(/[^\p{L}\s]/gu, '')
        .trim()
        .replace(/\s+/g, ' ');
}

function normalizarTexto(value) {
    return String(value || '').trim().replace(/\s+/g, ' ');
}

function validarCorreo(correo) {
    if (!correo) {
        return 'El correo es obligatorio.';
    }

    var patron = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return patron.test(correo) ? '' : 'Ingresa un correo válido.';
}

function validarDigitoVerificadorCedula(numero) {
    if (!/^\d{10}$/.test(numero)) {
        return false;
    }

    var digitos = numero.split('').map(Number);
    var sumaPares = 0;
    var sumaImpares = 0;
    var i;

    for (i = 0; i < 9; i += 2) {
        var mul = digitos[i] * 2;
        if (mul > 9) {
            mul -= 9;
        }
        sumaPares += mul;
    }

    for (i = 1; i < 8; i += 2) {
        sumaImpares += digitos[i];
    }

    var sumaTotal = sumaPares + sumaImpares;
    var residuo = sumaTotal % 10;
    var digitoVerificador = residuo === 0 ? 0 : 10 - residuo;

    return digitoVerificador === digitos[9];
}

function validarDocumento(esRuc, documento) {
    var numero = normalizarDocumento(documento);

    if (!numero) {
        return 'La cédula o RUC es obligatoria.';
    }

    if (esRuc) {
        if (numero.length !== 13) {
            return 'El RUC debe tener 13 dígitos.';
        }

        if (!numero.endsWith('001')) {
            return 'El RUC de persona natural debe terminar en 001.';
        }

        var cedulaBase = numero.slice(0, 10);
        if (!validarDigitoVerificadorCedula(cedulaBase)) {
            return 'El RUC no es válido: los primeros 10 dígitos no corresponden a una cédula válida.';
        }

        return '';
    }

    if (numero.length !== 10) {
        return 'La cédula debe tener 10 dígitos.';
    }

    if (!validarDigitoVerificadorCedula(numero)) {
        return 'La cédula ingresada no es válida.';
    }

    return '';
}

function actualizarAyudaDocumento() {
    var esRuc = document.getElementById('cliente-es-ruc').checked;
    var ayuda = document.getElementById('cliente-documento-ayuda');
    var etiqueta = document.getElementById('cliente-documento-etiqueta');
    var inputDocumento = document.getElementById('cliente-cedula');

    if (ayuda) {
        ayuda.textContent = esRuc ? 'El RUC debe tener 13 dígitos y terminar en 001.' : 'La cédula debe tener 10 dígitos.';
    }

    if (etiqueta) {
        etiqueta.textContent = esRuc ? 'RUC' : 'Cédula';
    }

    if (inputDocumento) {
        inputDocumento.placeholder = esRuc ? '13 dígitos, termina en 001' : '10 dígitos';
        inputDocumento.maxLength = esRuc ? 13 : 10;
    }
}

function limpiarErroresCliente() {
    ['cliente-cedula-error', 'cliente-nombre-error', 'cliente-apellido-error', 'cliente-correo-error'].forEach(function (id) {
        var elemento = document.getElementById(id);
        if (elemento) {
            elemento.textContent = '';
            elemento.classList.add('d-none');
        }
    });
}

function mostrarErrorCliente(id, mensaje) {
    var elemento = document.getElementById(id);
    if (!elemento) {
        return;
    }

    if (mensaje) {
        elemento.textContent = mensaje;
        elemento.classList.remove('d-none');
    } else {
        elemento.textContent = '';
        elemento.classList.add('d-none');
    }
}

function armarNombreCompleto(nombre, apellido) {
    var partes = [normalizarNombre(nombre), normalizarNombre(apellido)].filter(Boolean);
    return partes.join(' ');
}

function renderClientes(clientes) {
    var cuerpoTabla = document.getElementById('clientes-body');

    if (!cuerpoTabla) {
        return;
    }

    if (!Array.isArray(clientes) || clientes.length === 0) {
        cuerpoTabla.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">No hay clientes para mostrar.</td></tr>';
        return;
    }

    cuerpoTabla.innerHTML = clientes.map(function (cliente) {
        var botonEliminar = window.ES_ADMINISTRADOR
            ? '<button class="btn btn-sm btn-outline-danger" onclick="eliminarCliente(' + Number(cliente.id) + ')">Eliminar</button>'
            : '';

        return '<tr>' +
            '<td>' + escapeHtml(cliente.id) + '</td>' +
            '<td>' + escapeHtml(cliente.cedula) + '</td>' +
            '<td>' + escapeHtml(cliente.nombre_completo) + '</td>' +
            '<td>' + escapeHtml(cliente.correo || 'N/D') + '</td>' +
            '<td>' + formatDate(cliente.fecha_registro) + '</td>' +
            '<td class="text-center">' +
            '<button class="btn btn-sm btn-outline-primary me-2" onclick="editarCliente(' + Number(cliente.id) + ')">Editar</button>' +
            botonEliminar +
            '</td>' +
            '</tr>';
    }).join('');
}

async function cargarClientes(query) {
    var termino = query || '';

    try {
        var response = await fetch(apiClientesUrl + '?q=' + encodeURIComponent(termino), {
            headers: {
                Accept: 'application/json',
            },
        });

        if (!response.ok) {
            throw new Error('No se pudo cargar los clientes');
        }

        var data = await response.json();
        clientesCache = Array.isArray(data) ? data : [];
        renderClientes(clientesCache);
    } catch (error) {
        console.error(error);
        renderClientes([]);
    }
}

window.editarCliente = function (id) {
    var cliente = clientesCache.find(function (item) {
        return Number(item.id) === Number(id);
    });

    if (!cliente) {
        return;
    }

    modoEdicion = true;
    document.getElementById('modalTituloCliente').textContent = 'Editar Cliente';
    document.getElementById('cliente-id').value = cliente.id || '';
    document.getElementById('cliente-es-ruc').checked = String(cliente.cedula || '').replace(/\D+/g, '').length === 13;
    document.getElementById('cliente-cedula').value = cliente.cedula || '';

    var nombreCompleto = normalizarTexto(cliente.nombre_completo || '');
    var partes = nombreCompleto.split(' ').filter(Boolean);
    if (partes.length > 1) {
        document.getElementById('cliente-nombre').value = normalizarNombre(partes.slice(0, -1).join(' '));
        document.getElementById('cliente-apellido').value = normalizarNombre(partes.slice(-1).join(' '));
    } else {
        document.getElementById('cliente-nombre').value = normalizarNombre(nombreCompleto);
        document.getElementById('cliente-apellido').value = '';
    }

    document.getElementById('cliente-correo').value = cliente.correo || '';
    actualizarEstadoEdicionCliente();
    limpiarErroresCliente();
    actualizarAyudaDocumento();
    modalCliente.show();
};

window.abrirModal = function () {
    modoEdicion = false;
    limpiarFormularioCliente();
    actualizarEstadoEdicionCliente();
    document.getElementById('modalTituloCliente').textContent = 'Nuevo Cliente';
    actualizarAyudaDocumento();
    modalCliente.show();
};

window.guardarCliente = async function () {
    var id = document.getElementById('cliente-id').value;
    var esRuc = document.getElementById('cliente-es-ruc').checked;
    var cedula = document.getElementById('cliente-cedula').value.trim();
    var nombre = normalizarTexto(document.getElementById('cliente-nombre').value);
    var apellido = normalizarTexto(document.getElementById('cliente-apellido').value);
    var correo = normalizarTexto(document.getElementById('cliente-correo').value);

    limpiarErroresCliente();

    var errorDocumento = validarDocumento(esRuc, cedula);
    if (errorDocumento) {
        mostrarErrorCliente('cliente-cedula-error', errorDocumento);
        return;
    }

    if (nombre.length < 2) {
        mostrarErrorCliente('cliente-nombre-error', 'El nombre debe tener al menos 2 caracteres.');
        return;
    }

    if (apellido.length < 2) {
        mostrarErrorCliente('cliente-apellido-error', 'El apellido debe tener al menos 2 caracteres.');
        return;
    }

    var errorCorreo = validarCorreo(correo);
    if (errorCorreo) {
        mostrarErrorCliente('cliente-correo-error', errorCorreo);
        return;
    }

    var nombreCompleto = armarNombreCompleto(nombre, apellido);

    var payload = {
        id: id ? Number(id) : null,
        tipo_documento: esRuc ? 'ruc' : 'cedula',
        cedula: normalizarDocumento(cedula),
        nombre: nombre,
        apellido: apellido,
        nombre_completo: nombreCompleto,
        correo: correo,
    };

    try {
        var response = await fetch(apiClientesUrl, {
            method: modoEdicion ? 'PUT' : 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
            },
            body: JSON.stringify(payload),
        });

        var data = await response.json();

        if (!response.ok || (data.estado && data.estado !== 'exito')) {
            throw new Error(data.mensaje || 'No se pudo guardar el cliente');
        }

        modalCliente.hide();
        await cargarClientes(document.getElementById('buscador-clientes').value.trim());
    } catch (error) {
        console.error(error);
        mostrarErrorCliente('cliente-cedula-error', error.message || 'No se pudo guardar el cliente.');
    }
};

window.eliminarCliente = async function (id) {
    if (!window.ES_ADMINISTRADOR) {
        alert('Solo un administrador puede eliminar clientes.');
        return;
    }

    if (!confirm('¿Deseas eliminar este cliente?')) {
        return;
    }

    try {
        var response = await fetch(apiClientesUrl + '?id=' + encodeURIComponent(String(id)), {
            method: 'DELETE',
        });

        if (!response.ok) {
            throw new Error('No se pudo eliminar el cliente');
        }

        await cargarClientes(document.getElementById('buscador-clientes').value.trim());
    } catch (error) {
        console.error(error);
        alert('No se pudo eliminar el cliente.');
    }
};

function formatDinero(valor) {
    var numero = Number(valor || 0);
    return '$' + numero.toFixed(2);
}

function renderTarjetaClienteFrecuente(contenedorId, cliente, esTop) {
    var contenedor = document.getElementById(contenedorId);
    if (!contenedor) {
        return;
    }

    if (!cliente) {
        contenedor.innerHTML = '<span class="text-secondary">Aún no hay compras registradas.</span>';
        return;
    }

    contenedor.innerHTML =
        '<div class="fw-bold fs-5">' + escapeHtml(cliente.nombre_completo) + '</div>' +
        '<div class="text-secondary small mb-1">' + escapeHtml(cliente.cedula) + '</div>' +
        '<div>' + (esTop ? '🛒 ' : '') + Number(cliente.total_compras) + ' compra(s) &middot; ' + formatDinero(cliente.total_gastado) + ' gastado</div>';
}

function renderClientesFrecuentesTabla(clientes) {
    var cuerpoTabla = document.getElementById('clientes-frecuentes-body');
    if (!cuerpoTabla) {
        return;
    }

    if (!Array.isArray(clientes) || clientes.length === 0) {
        cuerpoTabla.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">Aún no hay compras registradas.</td></tr>';
        return;
    }

    cuerpoTabla.innerHTML = clientes.map(function (cliente, indice) {
        return '<tr>' +
            '<td>' + (indice + 1) + '</td>' +
            '<td>' + escapeHtml(cliente.nombre_completo) + '</td>' +
            '<td>' + escapeHtml(cliente.cedula) + '</td>' +
            '<td class="text-center">' + Number(cliente.total_compras) + '</td>' +
            '<td class="text-end">' + formatDinero(cliente.total_gastado) + '</td>' +
            '<td>' + formatDate(cliente.ultima_compra) + '</td>' +
            '</tr>';
    }).join('');
}

async function cargarClientesFrecuentes() {
    var panel = document.getElementById('panel-clientes-frecuentes');
    if (!panel) {
        // Este panel solo existe para el administrador; si no está en el DOM
        // (por ejemplo, sesión de cajero), no hacemos nada.
        return;
    }

    try {
        var response = await fetch(apiClientesUrl + '?reporte=frecuentes', {
            headers: {
                Accept: 'application/json',
            },
        });

        var data = await response.json();

        if (!response.ok || data.estado !== 'exito') {
            throw new Error(data.mensaje || 'No se pudo cargar la información de clientes frecuentes');
        }

        renderTarjetaClienteFrecuente('cliente-mas-frecuente', data.cliente_mas_compra, true);
        renderTarjetaClienteFrecuente('cliente-menos-frecuente', data.cliente_menos_compra, false);
        renderClientesFrecuentesTabla(data.clientes_frecuentes);
    } catch (error) {
        console.error(error);
        renderTarjetaClienteFrecuente('cliente-mas-frecuente', null, true);
        renderTarjetaClienteFrecuente('cliente-menos-frecuente', null, false);
        renderClientesFrecuentesTabla([]);
    }
}

document.addEventListener('DOMContentLoaded', function () {
    var modalElement = document.getElementById('modalCliente');
    if (typeof bootstrap !== 'undefined' && modalElement) {
        modalCliente = new bootstrap.Modal(modalElement);
    }

    actualizarEstadoEdicionCliente();

    var inputBusqueda = document.getElementById('buscador-clientes');
    var temporizador = null;

    if (inputBusqueda) {
        inputBusqueda.addEventListener('input', function (event) {
            clearTimeout(temporizador);
            temporizador = window.setTimeout(function () {
                cargarClientes(event.target.value.trim());
            }, 250);
        });
    }

    var switchDocumento = document.getElementById('cliente-es-ruc');
    if (switchDocumento) {
        switchDocumento.addEventListener('change', function () {
            actualizarAyudaDocumento();
            limpiarErroresCliente();
        });
    }

    ['cliente-cedula', 'cliente-nombre', 'cliente-apellido', 'cliente-correo'].forEach(function (id) {
        var elemento = document.getElementById(id);
        if (elemento) {
            elemento.addEventListener('input', function () {
                if (id === 'cliente-cedula') {
                    elemento.value = normalizarDocumento(elemento.value);
                }

                if (id === 'cliente-nombre' || id === 'cliente-apellido') {
                    elemento.value = normalizarNombre(elemento.value);
                }

                limpiarErroresCliente();
            });
        }
    });

    actualizarAyudaDocumento();

    cargarClientes('');
    cargarClientesFrecuentes();
});