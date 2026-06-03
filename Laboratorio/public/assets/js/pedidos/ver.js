/**
 * Detalle de pedido — sub-proyecto 2.
 * Carga el pedido + items y permite anular o eliminar.
 */
import { api, labPath } from '../api.js?v=10';

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
        const [pedido, resData] = await Promise.all([
            api.get(`${ENDPOINT}?id=${id}`),
            api.get(`/api/resultados?pedido_id=${id}`).catch(() => ({ resultados: [] })),
        ]);
        const resPorItem = new Map();
        for (const r of (resData?.resultados ?? [])) {
            resPorItem.set(r.pedido_item_id, r);
        }
        render(pedido, resPorItem);
        cablearAcciones(pedido);
    } catch (err) {
        mostrarError(err.status === 404
            ? `Pedido ${id} no encontrado o eliminado.`
            : (err.message || 'Error al cargar el pedido'));
    }
}

function render(p, resPorItem = new Map()) {
    document.getElementById('ord-loading').hidden = true;
    document.getElementById('ord-cuerpo').hidden = false;

    const set = (id, val) => {
        const el = document.getElementById(id);
        if (el) el.textContent = val ?? '—';
    };

    set('d-numero', p.numero);

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

    const histWrap = document.getElementById('d-historial-wrap');
    if (p.paciente_id) {
        document.getElementById('d-link-historial').href = labPath(`/historial?paciente_id=${p.paciente_id}`);
        if (histWrap) histWrap.hidden = false;
    } else if (histWrap) {
        histWrap.hidden = true;
    }

    set('d-medico', p.medico_externo ?? (p.medico_id ? `medico_id=${p.medico_id}` : '—'));
    set('d-os', p.obra_social_nombre ?? '—');
    set('d-afiliado', p.numero_afiliado ?? '—');
    set('d-diag', p.diagnostico ?? '—');

    const estadoLbl = { A: 'A facturar', F: 'Facturada', P: 'Pagada', N: 'No aplica' };
    set('d-estado-pac', estadoLbl[p.estado_paciente] ?? p.estado_paciente ?? '—');
    set('d-estado-seg', estadoLbl[p.estado_seguro] ?? p.estado_seguro ?? '—');
    set('d-monto-pac',  formatMoney(p.monto_paciente));
    set('d-monto-seg',  formatMoney(p.monto_seguro));

    const items = p.items ?? [];
    document.getElementById('d-items').innerHTML = items.length === 0
        ? `<tr><td colspan="8" class="muted" style="text-align:center; padding: 1.5rem;">Sin items.</td></tr>`
        : items.map((it) => {
            const r = resPorItem.get(it.id);
            return `
            <tr>
              <td>${escapeHtml(it.determinacion_codigo ?? '')}</td>
              <td>${escapeHtml(it.determinacion_nombre ?? '')}</td>
              <td>${escapeHtml(it.perfil_nombre ?? '')}</td>
              <td>${renderResultado(r)}</td>
              <td>${escapeHtml(it.unidad ?? (r?.unidad ?? ''))}</td>
              <td>${renderReferencia(r)}</td>
              <td>${escapeHtml(it.estado ?? '')}</td>
              <td>${it.precio !== null && it.precio !== undefined ? escapeHtml(String(it.precio)) : ''}</td>
            </tr>`;
        }).join('');

    const btnAnular = document.getElementById('btn-anular');
    const btnEliminar = document.getElementById('btn-eliminar');
    if (['entregado', 'anulado'].includes(p.estado)) {
        btnAnular.disabled = true;
        btnAnular.title = `No se puede anular un pedido ${p.estado}`;
    }
}

function cablearAcciones(p) {
    document.getElementById('btn-portada').addEventListener('click', () => {
        window.open(labPath(`${ENDPOINT}?accion=portada&id=${p.id}`), '_blank', 'noopener');
    });
    document.getElementById('btn-talon').addEventListener('click', () => {
        window.open(labPath(`${ENDPOINT}?accion=talon&id=${p.id}`), '_blank', 'noopener');
    });

    document.getElementById('btn-anular').addEventListener('click', async () => {
        const motivo = window.prompt('Motivo de anulación:');
        if (motivo === null) return;
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

function renderResultado(r) {
    if (!r) return '<span class="muted">—</span>';
    const raw = r.valor_numerico ?? r.valor_texto;
    if (raw === null || raw === undefined || raw === '') return '<span class="muted">—</span>';
    let txt = String(raw);
    if (r.valor_numerico !== null && r.valor_numerico !== undefined) {
        const n = parseFloat(r.valor_numerico);
        if (!isNaN(n)) {
            const dec = Number.isInteger(r.decimales) ? r.decimales : 2;
            txt = n.toLocaleString('es-AR', { minimumFractionDigits: dec, maximumFractionDigits: dec });
        }
    }
    const flag = parseInt(r.es_critico, 10) === 1
        ? ' <span class="badge badge-critico" title="Valor crítico">!!</span>'
        : (parseInt(r.es_anormal, 10) === 1 ? ' <span class="badge badge-anormal" title="Fuera de rango">!</span>' : '');
    const cls = parseInt(r.es_critico, 10) === 1 ? 'val-critico'
              : (parseInt(r.es_anormal, 10) === 1 ? 'val-anormal' : '');
    return `<span class="${cls}"><strong>${escapeHtml(txt)}</strong></span>${flag}`;
}

function renderReferencia(r) {
    if (!r) return '<span class="muted">—</span>';
    if (r.texto_referencia) return escapeHtml(r.texto_referencia);
    const min = r.valor_referencia_min;
    const max = r.valor_referencia_max;
    const has = (v) => v !== null && v !== undefined && v !== '';
    if (has(min) && has(max)) return `${escapeHtml(String(min))} – ${escapeHtml(String(max))}`;
    if (has(min)) return `≥ ${escapeHtml(String(min))}`;
    if (has(max)) return `≤ ${escapeHtml(String(max))}`;
    return '<span class="muted">—</span>';
}
