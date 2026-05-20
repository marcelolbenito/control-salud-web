import { api } from '../api.js';

const $ = (id) => document.getElementById(id);
const $msg = $('mensaje');
const $listaPedidos = $('lista-pedidos');
const $resultadosWrap = $('resultados-wrap');
const $infoTotal = $('info-total');
const $dossierWrap = $('dossier-wrap');
const $dossier = $('dossier');
const $btnPrev = $('btn-prev');
const $btnNext = $('btn-next');

const state = { paciente: null, filtros: {}, offset: 0, limit: 25, total: 0 };

const esc = (s) => String(s ?? '').replace(/[<>&"']/g, (c) => ({
    '<': '&lt;', '>': '&gt;', '&': '&amp;', '"': '&quot;', "'": '&#39;',
}[c]));

const badge = (estado) => `<span class="badge badge-${esc(estado)}">${esc(estado.replace('_', ' '))}</span>`;

function showMsg(text, tipo = 'exito') {
    $msg.className = `mensaje ${tipo}`;
    $msg.textContent = text;
    $msg.hidden = false;
}

async function buscar() {
    $msg.hidden = true;
    $dossierWrap.hidden = true;
    const params = new URLSearchParams();
    params.set('paciente_id', state.paciente);
    if (state.filtros.estado) params.set('estado', state.filtros.estado);
    if (state.filtros.desde) params.set('desde', state.filtros.desde);
    if (state.filtros.hasta) params.set('hasta', state.filtros.hasta);
    params.set('limit', state.limit);
    params.set('offset', state.offset);

    try {
        const data = await api.get(`/api/historial?${params}`);
        state.total = data.total;
        renderPedidos(data.pedidos);
        $infoTotal.textContent = `${data.total} pedido(s) encontrados.`;
        $resultadosWrap.hidden = false;
        $btnPrev.disabled = state.offset === 0;
        $btnNext.disabled = state.offset + state.limit >= data.total;
    } catch (e) {
        $resultadosWrap.hidden = true;
        showMsg(`Error: ${e.message}`, 'error');
    }
}

function renderPedidos(pedidos) {
    if (!pedidos || pedidos.length === 0) {
        $listaPedidos.innerHTML = '<tr><td colspan="7" class="muted" style="text-align:center;">Sin pedidos</td></tr>';
        return;
    }
    $listaPedidos.innerHTML = pedidos.map((p) => `
        <tr>
            <td><strong>${esc(p.numero)}</strong></td>
            <td>${esc((p.fecha_solicitud || '').slice(0, 16))}</td>
            <td>${esc(p.prioridad)}</td>
            <td>${esc(p.items_validados)} / ${esc(p.items_count)}</td>
            <td>${badge(p.estado)}</td>
            <td>${parseInt(p.es_critico, 10) ? '<span class="badge badge-critico">CRITICO</span>' : ''}</td>
            <td><button class="btn btn-ghost btn-sm" data-accion="dossier" data-id="${p.id}"><i class="bi bi-eye"></i> Ver</button></td>
        </tr>
    `).join('');
}

async function abrirDossier(pedidoId) {
    try {
        const d = await api.get(`/api/historial?pedido_id=${pedidoId}`);
        renderDossier(d);
        $dossierWrap.hidden = false;
        $dossier.scrollIntoView({ behavior: 'smooth', block: 'start' });
    } catch (e) {
        showMsg(`Error: ${e.message}`, 'error');
    }
}

function renderDossier(d) {
    const p = d.pedido;
    const snap = p.snapshot_paciente || {};
    const nombre = typeof snap === 'object' ? (snap.nombre || '-') : '-';

    const items = (d.items || []).map((it) => `
        <tr>
            <td>${esc(it.determinacion_nombre || it.determinacion_codigo || '-')}</td>
            <td>${badge(it.estado)}</td>
        </tr>`).join('');

    const resultados = (d.resultados || []).map((r) => {
        const valor = r.valor_numerico ?? r.valor_texto ?? '-';
        const cls = r.es_critico ? 'valor-critico' : (r.es_anormal ? 'valor-anormal' : '');
        return `
        <tr>
            <td>${esc(r.determinacion_nombre || '-')}</td>
            <td><span class="${cls}">${esc(valor)}</span> ${esc(r.unidad || '')}</td>
            <td>${badge(r.estado)}</td>
            <td>v${esc(r.version)}</td>
        </tr>`;
    }).join('');

    const informes = (d.informes || []).map((i) => `
        <tr>
            <td><strong>${esc(i.numero)}</strong></td>
            <td>${esc(i.fecha_emision)}</td>
            <td>${parseInt(i.es_parcial, 10) ? '<span class="badge badge-rectificado">PARCIAL</span>' : '<span class="badge badge-completo">COMPLETO</span>'}</td>
            <td>${parseInt(i.entregado, 10) ? badge('entregado') : badge('pendiente')}</td>
            <td><a class="btn btn-ghost btn-sm" href="/api/informes?id=${i.id}&download=1" target="_blank"><i class="bi bi-file-earmark-pdf"></i> Descargar</a></td>
        </tr>`).join('');

    $dossier.innerHTML = `
        <div class="grid" style="margin-bottom: 1rem;">
            <div><strong>Numero:</strong> ${esc(p.numero)}</div>
            <div><strong>Estado:</strong> ${badge(p.estado)} ${parseInt(p.es_critico, 10) ? '<span class="badge badge-critico">CRITICO</span>' : ''}</div>
            <div><strong>Paciente:</strong> ${esc(nombre)}</div>
            <div><strong>Solicitud:</strong> ${esc((p.fecha_solicitud || '').slice(0, 16))}</div>
        </div>

        <h3>Items</h3>
        <div class="table-wrap" style="margin-bottom: 1rem;">
            <table class="table"><thead><tr><th>Determinacion</th><th>Estado</th></tr></thead><tbody>${items || '<tr><td colspan="2" class="muted">Sin items</td></tr>'}</tbody></table>
        </div>

        <h3>Resultados</h3>
        <div class="table-wrap" style="margin-bottom: 1rem;">
            <table class="table"><thead><tr><th>Determinacion</th><th>Valor</th><th>Estado</th><th>Version</th></tr></thead><tbody>${resultados || '<tr><td colspan="4" class="muted">Sin resultados</td></tr>'}</tbody></table>
        </div>

        <h3>Informes</h3>
        <div class="table-wrap">
            <table class="table"><thead><tr><th>Numero</th><th>Emision</th><th>Tipo</th><th>Estado</th><th></th></tr></thead><tbody>${informes || '<tr><td colspan="5" class="muted">Sin informes emitidos</td></tr>'}</tbody></table>
        </div>
    `;
}

$('form-filtros').addEventListener('submit', async (e) => {
    e.preventDefault();
    const fd = new FormData(e.target);
    state.paciente = parseInt(fd.get('paciente_id'), 10);
    state.filtros = {
        estado: fd.get('estado') || '',
        desde: fd.get('desde') || '',
        hasta: fd.get('hasta') || '',
    };
    state.offset = 0;
    await buscar();
});

$listaPedidos.addEventListener('click', (e) => {
    const btn = e.target.closest('button[data-accion="dossier"]');
    if (btn) abrirDossier(parseInt(btn.dataset.id, 10));
});

$btnPrev.addEventListener('click', async () => {
    state.offset = Math.max(0, state.offset - state.limit);
    await buscar();
});
$btnNext.addEventListener('click', async () => {
    state.offset += state.limit;
    await buscar();
});
