/**
 * Detalle de pedido — sub-proyecto 2.
 * Carga el pedido + items y permite anular o eliminar.
 */
import { api, labPath } from '../api.js?v=9';

const ENDPOINT = '/api/pedidos';

export function initVer(id) {
    if (!id || id <= 0) {
        mostrarError('Falta el id del pedido en la URL (?id=N)');
        return;
    }
    cargar(id);
}

async function cargar(id) {
    try {
        const pedido = await api.get(`${ENDPOINT}?id=${id}`);
        render(pedido);
        cablearAcciones(pedido);
    } catch (err) {
        mostrarError(err.status === 404
            ? `Pedido ${id} no encontrado o eliminado.`
            : (err.message || 'Error al cargar el pedido'));
    }
}

function render(p) {
    document.getElementById('ord-loading').hidden = true;
    document.getElementById('ord-cuerpo').hidden = false;

    const set = (id, val) => {
        const el = document.getElementById(id);
        if (el) el.textContent = val ?? '—';
    };

    set('d-numero', p.numero);

    // Render con badges para estado, prioridad y critico (clases definidas en app.css).
    const safeKey = (s) => String(s ?? '').replace(/[^a-z_]/gi, '');
    document.getElementById('d-estado').innerHTML =
        `<span class="badge badge-${safeKey(p.estado)}">${escapeHtml(String(p.estado ?? '').replace('_', ' '))}</span>`;
    document.getElementById('d-prioridad').innerHTML =
        `<span class="badge badge-prio-${safeKey(p.prioridad)}">${escapeHtml(String(p.prioridad ?? ''))}</span>`;
    document.getElementById('d-critico').innerHTML = p.es_critico
        ? '<span class="badge badge-critico">CRITICO</span>'
        : '<span class="muted">No</span>';
    set('d-fsol', formatearFecha(p.fecha_solicitud));
    set('d-fext', formatearFecha(p.fecha_extraccion));
    set('d-fent', formatearFecha(p.fecha_entrega));

    const snapshot = p.snapshot_paciente && typeof p.snapshot_paciente === 'string'
        ? safeParse(p.snapshot_paciente)
        : (p.snapshot_paciente ?? {});
    set('d-pac-nombre', snapshot?.nombre ?? '—');
    set('d-pac-dni', snapshot?.dni ?? '—');
    set('d-pac-sexo', snapshot?.sexo ?? '—');
    set('d-pac-fnac', snapshot?.fecha_nac ?? '—');
    set('d-hc', `(paciente_id=${p.paciente_id})`);

    const link = document.getElementById('d-link-historial');
    link.href = `/historial?paciente_id=${p.paciente_id}`;

    set('d-medico', p.medico_externo ?? (p.medico_id ? `medico_id=${p.medico_id}` : '—'));
    set('d-os', p.obra_social_id ?? '—');
    set('d-afiliado', p.numero_afiliado ?? '—');
    set('d-diag', p.diagnostico ?? '—');

    // SP5: facturacion
    const estadoLbl = { A: 'A facturar', F: 'Facturada', P: 'Pagada', N: 'No aplica' };
    set('d-estado-pac', estadoLbl[p.estado_paciente] ?? p.estado_paciente ?? '—');
    set('d-estado-seg', estadoLbl[p.estado_seguro] ?? p.estado_seguro ?? '—');
    set('d-monto-pac',  formatMoney(p.monto_paciente));
    set('d-monto-seg',  formatMoney(p.monto_seguro));
    set('d-honorarios', formatMoney(p.monto_honorarios));

    const items = p.items ?? [];
    document.getElementById('d-items').innerHTML = items.length === 0
        ? `<tr><td colspan="6" class="muted" style="text-align:center; padding: 1.5rem;">Sin items.</td></tr>`
        : items.map((it) => `
            <tr>
              <td>${escapeHtml(it.determinacion_codigo ?? '')}</td>
              <td>${escapeHtml(it.determinacion_nombre ?? '')}</td>
              <td>${escapeHtml(it.perfil_nombre ?? '')}</td>
              <td>${escapeHtml(it.unidad ?? '')}</td>
              <td>${escapeHtml(it.estado ?? '')}</td>
              <td>${it.precio !== null && it.precio !== undefined ? escapeHtml(String(it.precio)) : ''}</td>
            </tr>`).join('');

    // Habilita/deshabilita acciones según estado.
    const btnAnular = document.getElementById('btn-anular');
    const btnEliminar = document.getElementById('btn-eliminar');
    if (['entregado', 'anulado'].includes(p.estado)) {
        btnAnular.disabled = true;
        btnAnular.title = `No se puede anular un pedido ${p.estado}`;
    }
}

function cablearAcciones(p) {
    document.getElementById('btn-portada').addEventListener('click', () => {
        window.open(`${ENDPOINT}?accion=portada&id=${p.id}`, '_blank', 'noopener');
    });
    document.getElementById('btn-talon').addEventListener('click', () => {
        window.open(`${ENDPOINT}?accion=talon&id=${p.id}`, '_blank', 'noopener');
    });

    document.getElementById('btn-anular').addEventListener('click', async () => {
        const motivo = window.prompt('Motivo de anulación:');
        if (motivo === null) return; // canceló
        if (motivo.trim() === '') {
            alert('El motivo es obligatorio.');
            return;
        }
        try {
            await api.post(`${ENDPOINT}?accion=anular&id=${p.id}`, { motivo });
            alert('Pedido anulado.');
            window.location.reload();
        } catch (err) {
            alert('No se pudo anular: ' + (err.message || 'error'));
        }
    });

    document.getElementById('btn-eliminar').addEventListener('click', async () => {
        if (!window.confirm('¿Eliminar este pedido? Se hace soft-delete (es reversible desde la BD).')) return;
        try {
            await api.post(`${ENDPOINT}?accion=eliminar&id=${p.id}`, {});
            alert('Pedido eliminado.');
            window.location.href = labPath('/pedidos');
        } catch (err) {
            alert('No se pudo eliminar: ' + (err.message || 'error'));
        }
    });
}

function mostrarError(msg) {
    document.getElementById('ord-loading').hidden = true;
    const errEl = document.getElementById('ord-error');
    errEl.textContent = msg;
    errEl.hidden = false;
}

function formatMoney(n) {
    if (n === null || n === undefined) return '—';
    return '$ ' + parseFloat(n).toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function formatearFecha(iso) {
    if (!iso) return '—';
    const d = new Date(String(iso).replace(' ', 'T'));
    if (isNaN(d.getTime())) return iso;
    return d.toLocaleString('es-AR', { dateStyle: 'short', timeStyle: 'short' });
}

function safeParse(s) {
    try { return JSON.parse(s); } catch { return {}; }
}

function escapeHtml(s) {
    return String(s ?? '').replace(/[&<>"']/g, (c) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
    }[c]));
}
