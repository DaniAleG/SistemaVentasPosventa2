var apiHistorialUrl = 'Backend/api_historial.php';

function formatMoney(value) {
    return Number(value || 0).toFixed(2);
}

function escapeHtml(value) {
    return String(value || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/\"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function normalizarEstado(estado) {
    var texto = String(estado || '').toLowerCase().trim();

    if (!texto) {
        return { label: 'Pagada', clase: 'text-bg-success' };
    }

    if (texto === 'anulada' || texto === 'anulado' || texto === 'cancelada' || texto === 'cancelado' || texto === '0' || texto === 'false') {
        return { label: 'Anulada', clase: 'text-bg-danger' };
    }

    return { label: 'Pagada', clase: 'text-bg-success' };
}

function normalizarTipoFactura(tipoFactura) {
    var texto = String(tipoFactura || '').trim();
    return texto || 'Factura';
}

function setMensaje(texto) {
    var mensaje = document.getElementById('mensaje-historial');
    if (mensaje) {
        mensaje.textContent = texto || '';
    }
}

function actualizarCards(resumen) {
    document.getElementById('card-total-vendido').textContent = '$ ' + formatMoney(resumen.total_vendido);
    document.getElementById('card-cantidad-facturas').textContent = String(Number(resumen.cantidad_facturas || 0));
    document.getElementById('card-ticket-promedio').textContent = '$ ' + formatMoney(resumen.ticket_promedio);
}

function renderTabla(registros) {
    var cuerpo = document.getElementById('historial-body');
    if (!cuerpo) {
        return;
    }

    if (!Array.isArray(registros) || registros.length === 0) {
        cuerpo.innerHTML = '<tr><td colspan="7" class="text-center text-secondary py-4">Sin datos para mostrar</td></tr>';
        return;
    }

    cuerpo.innerHTML = registros.map(function (item) {
        var estado = normalizarEstado(item.estado);
        var tipoFactura = normalizarTipoFactura(item.tipo_factura);
        return '<tr>' +
            '<td>' + escapeHtml(item.numero || item.id || '') + '</td>' +
            '<td>' + escapeHtml(item.fecha || '') + '</td>' +
            '<td>' + escapeHtml(item.cliente || 'Consumidor Final') + '</td>' +
            '<td>' + escapeHtml(item.vendedor || 'Sin asignar') + '</td>' +
            '<td class="text-end">' + formatMoney(item.total) + '</td>' +
            '<td class="text-center">' + escapeHtml(tipoFactura) + '</td>' +
            '<td class="text-center"><span class="badge ' + estado.clase + '">' + estado.label + '</span></td>' +
            '</tr>';
    }).join('');
}

async function cargarHistorial() {
    var fechaInicio = document.getElementById('filtro-fecha-inicio').value;
    var fechaFin = document.getElementById('filtro-fecha-fin').value;
    var filtroCliente = document.getElementById('filtro-cliente').value.trim();
    var filtroFactura = document.getElementById('filtro-factura').value.trim();
    var botonConsultar = document.getElementById('btn-consultar-historial');

    if (fechaInicio && fechaFin && fechaInicio > fechaFin) {
        setMensaje('La fecha inicio no puede ser mayor que la fecha fin.');
        return;
    }

    if (botonConsultar) {
        botonConsultar.disabled = true;
        botonConsultar.textContent = 'Consultando...';
    }

    setMensaje('');

    try {
        var params = new URLSearchParams();
        if (fechaInicio) {
            params.set('fecha_inicio', fechaInicio);
        }
        if (fechaFin) {
            params.set('fecha_fin', fechaFin);
        }
        if (filtroCliente) {
            params.set('cliente', filtroCliente);
        }
        if (filtroFactura) {
            params.set('factura', filtroFactura);
        }

        var response = await fetch(apiHistorialUrl + '?' + params.toString(), {
            headers: {
                Accept: 'application/json'
            }
        });

        var data = await response.json();

        if (!response.ok || data.estado === 'error') {
            throw new Error(data.mensaje || 'No se pudo cargar el historial');
        }

        actualizarCards(data.resumen || {
            total_vendido: 0,
            cantidad_facturas: 0,
            ticket_promedio: 0
        });
        renderTabla(data.registros || []);

        if (data.mensaje) {
            setMensaje(data.mensaje);
        }
    } catch (error) {
        actualizarCards({
            total_vendido: 0,
            cantidad_facturas: 0,
            ticket_promedio: 0
        });
        renderTabla([]);
        setMensaje(error.message || 'No se pudo cargar el historial.');
    } finally {
        if (botonConsultar) {
            botonConsultar.disabled = false;
            botonConsultar.textContent = 'Consultar';
        }
    }
}

function obtenerFechaISO(date) {
    return date.toISOString().slice(0, 10);
}

document.addEventListener('DOMContentLoaded', function () {
    var inputInicio = document.getElementById('filtro-fecha-inicio');
    var inputFin = document.getElementById('filtro-fecha-fin');
    var inputCliente = document.getElementById('filtro-cliente');
    var inputFactura = document.getElementById('filtro-factura');
    var botonConsultar = document.getElementById('btn-consultar-historial');

    var hoy = new Date();
    var inicioMes = new Date(hoy.getFullYear(), hoy.getMonth(), 1);

    if (inputInicio) {
        inputInicio.value = obtenerFechaISO(inicioMes);
    }

    if (inputFin) {
        inputFin.value = obtenerFechaISO(hoy);
    }

    if (botonConsultar) {
        botonConsultar.addEventListener('click', cargarHistorial);
    }

    [inputCliente, inputFactura].forEach(function (input) {
        if (!input) {
            return;
        }

        input.addEventListener('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                cargarHistorial();
            }
        });
    });

    cargarHistorial();
});
