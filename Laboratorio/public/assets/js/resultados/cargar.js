import { api } from '../api.js?v=9';

const $ = (id) => document.getElementById(id);
const $msg = $('mensaje');
const $panel = $('panel-pedido');
const $info = $('info-pedido');
const $lista = $('lista-items');

const ESTADOS_CARGA = new Set(['pendiente', 'en_proceso', 'parcial']);

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

// ========= Buscador de pedidos por DNI / nombre / N° orden =========

const $bq = $('buscar-q');
const $bres = $('buscar-resultados');
let debounceTimer = null;
let busquedaActual = 0;

function ocultarDropdown() { $bres.hidden = true; $bres.innerHTML = ''; }

function fmtFecha(s) {
    if (!s) return '';
    const d = new Date(s.replace(' ', 'T'));
    return Number.isNaN(d.getTime()) ? s : d.toLocaleDateString('es-AR');
}

function fmtPaciente(p) {
    const nombre = [p.paciente_apellido, p.paciente_nombres].filter(Boolean).join(', ');
    const hc = p.paciente_nro_hc ? ` (HC ${p.paciente_nro_hc})` : '';
    return `${esc(nombre || '-')}${hc}`;
}

async function buscarSugerencias(q) {
    const nro = ++busquedaActual;
    try {
        const data = await api.get(
            `/api/pedidos?accion=listar&q=${encodeURIComponent(q)}&page=1`,
        );
        if (nro !== busquedaActual) return;
        const pedidos = (data.pedidos || []).filter((p) => ESTADOS_CARGA.has(p.estado));
        renderSugerencias(pedidos);
    } catch (e) {
        if (nro !== busquedaActual) return;
        ocultarDropdown();
        showMsg(`Error buscando: ${e.message}`, 'error');
    }
}

function renderSugerencias(pedidos) {
    if (pedidos.length === 0) {
        $bres.innerHTML = '<li class="placeholder">Sin resultados</li>';
        $bres.hidden = false;
        return;
    }
    $bres.innerHTML = pedidos.map((p) => `
        <li data-pedido-id="${p.id}">
            <span><strong>N° ${esc(p.numero)}</strong> · ${fmtPaciente(p)}</span>
            <span class="muted">${esc(fmtFecha(p.fecha_solicitud))} · ${badge(p.estado)}</span>
        </li>`).join('');
    $bres.hidden = false;
}

$bq.addEventListener('input', () => {
    const q = $bq.value.trim();
    clearTimeout(debounceTimer);
    if (q.length < 2) { ocultarDropdown(); return; }
    debounceTimer = setTimeout(() => buscarSugerencias(q), 300);
});

$bres.addEventListener('click', async (e) => {
    const li = e.target.closest('li[data-pedido-id]');
    if (!li) return;
    const id = parseInt(li.dataset.pedidoId, 10);
    if (!(id > 0)) return;
    ocultarDropdown();
    $bq.value = '';
    await buscarPedido(id);
});

document.addEventListener('click', (e) => {
    if (!$bres.contains(e.target) && e.target !== $bq) ocultarDropdown();
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
