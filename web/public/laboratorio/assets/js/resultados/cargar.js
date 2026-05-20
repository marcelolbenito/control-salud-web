import { api } from '/assets/js/api.js';

const $ = (id) => document.getElementById(id);
const $msg = $('mensaje');
const $panel = $('panel-pedido');
const $info = $('info-pedido');
const $lista = $('lista-items');

const state = { pedidoId: null, items: [], resultados: {} };

const esc = (s) => String(s ?? '').replace(/[<>&"']/g, (c) => ({
    '<': '&lt;', '>': '&gt;', '&': '&amp;', '"': '&quot;', "'": '&#39;',
}[c]));

const badge = (estado) => `<span class="badge badge-${esc(estado)}">${esc(estado.replace('_', ' '))}</span>`;

function showMsg(text, tipo = 'exito') {
    $msg.className = `mensaje ${tipo}`;
    $msg.textContent = text;
    $msg.hidden = false;
}

function fmtRango(r) {
    if (!r) return '';
    if (r.texto_referencia) return r.texto_referencia;
    if (r.valor_referencia_min != null && r.valor_referencia_max != null) {
        return `${r.valor_referencia_min} - ${r.valor_referencia_max}`;
    }
    if (r.valor_referencia_min != null) return `> ${r.valor_referencia_min}`;
    if (r.valor_referencia_max != null) return `< ${r.valor_referencia_max}`;
    return '';
}

function indexResultados(resultados) {
    const map = {};
    for (const r of resultados || []) {
        map[r.pedido_item_id] = r;
    }
    return map;
}

async function buscarPedido(pedidoId) {
    $msg.hidden = true;
    try {
        const data = await api.get(`/api/historial?pedido_id=${pedidoId}`);
        state.pedidoId = pedidoId;
        state.items = data.items;
        state.resultados = indexResultados(data.resultados);
        renderInfo(data.pedido);
        renderItems();
        $panel.hidden = false;
    } catch (e) {
        $panel.hidden = true;
        showMsg(`Error: ${e.message}`, 'error');
    }
}

function renderInfo(p) {
    const snap = p.snapshot_paciente || {};
    const nombre = typeof snap === 'object' ? (snap.nombre || '-') : '-';
    $info.innerHTML = `
        <strong>${esc(p.numero)}</strong> · Paciente: ${esc(nombre)} · Estado: ${badge(p.estado)}
        ${p.es_critico ? ' · <span class="badge badge-critico">CRITICO</span>' : ''}
    `;
}

function renderItems() {
    if (state.items.length === 0) {
        $lista.innerHTML = '<tr><td colspan="7" class="muted" style="text-align:center;">Sin items</td></tr>';
        return;
    }
    $lista.innerHTML = state.items.map((it) => {
        const r = state.resultados[it.id];
        const valActual = r ? (r.valor_numerico ?? r.valor_texto ?? '') : '';
        const estadoItem = it.estado;
        const disabled = estadoItem === 'validado' ? 'disabled' : '';
        let marca = '';
        if (r) {
            if (r.es_critico) marca = '<span class="badge badge-critico">CRITICO</span>';
            else if (r.es_anormal) marca = '<span class="badge badge-anormal">ANORMAL</span>';
        }
        return `
        <tr data-item-id="${it.id}">
            <td>
                <strong>${esc(it.determinacion_nombre || '-')}</strong>
                ${it.determinacion_codigo ? `<div class="muted" style="font-size:.78rem;">${esc(it.determinacion_codigo)}</div>` : ''}
            </td>
            <td><input type="text" name="valor" value="${esc(valActual)}" ${disabled} placeholder="valor" inputmode="decimal"></td>
            <td>${esc(it.unidad || '')}</td>
            <td class="muted">${esc(fmtRango(r))}</td>
            <td>${badge(estadoItem)}</td>
            <td>${marca}</td>
            <td>
                ${disabled ? '' : `<button class="btn btn-primary btn-sm" data-accion="guardar"><i class="bi bi-save"></i> Guardar</button>`}
            </td>
        </tr>`;
    }).join('');
}

$('form-buscar').addEventListener('submit', async (e) => {
    e.preventDefault();
    const id = parseInt($('buscar-pedido-id').value, 10);
    if (id > 0) await buscarPedido(id);
});

$lista.addEventListener('click', async (e) => {
    const btn = e.target.closest('button[data-accion="guardar"]');
    if (!btn) return;
    await guardarFila(btn.closest('tr'));
});

$lista.addEventListener('keydown', async (e) => {
    if (e.key !== 'Enter' || e.target.name !== 'valor') return;
    e.preventDefault();
    await guardarFila(e.target.closest('tr'));
});

async function guardarFila(tr) {
    const itemId = parseInt(tr.dataset.itemId, 10);
    const raw = (tr.querySelector('input[name="valor"]').value || '').trim();
    if (raw === '') {
        showMsg('Ingrese un valor', 'error');
        return;
    }
    const numerico = raw.replace(',', '.');
    const esNum = /^-?\d+(\.\d+)?$/.test(numerico);
    const payload = { pedido_item_id: itemId };
    if (esNum) payload.valor_numerico = parseFloat(numerico);
    else payload.valor_texto = raw;

    try {
        await api.post('/api/resultados', payload);
        showMsg('Resultado guardado', 'exito');
        await buscarPedido(state.pedidoId);
    } catch (err) {
        showMsg(`Error: ${err.message}`, 'error');
    }
}
