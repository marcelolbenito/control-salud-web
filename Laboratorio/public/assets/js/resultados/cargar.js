import { api } from '../api.js?v=10';

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

function fmtNumRef(v) {
    const n = parseFloat(v);
    return Number.isNaN(n) ? String(v) : String(n).replace('.', ',');
}

function fmtRango(r) {
    if (!r) return '';
    if (r.texto_referencia) return r.texto_referencia;
    const min = r.valor_referencia_min;
    const max = r.valor_referencia_max;
    const u = r.unidad ? ` ${r.unidad}` : '';
    if (min != null && max != null) return `${fmtNumRef(min)} a ${fmtNumRef(max)}${u}`;
    if (min != null) return `> ${fmtNumRef(min)}${u}`;
    if (max != null) return `< ${fmtNumRef(max)}${u}`;
    return '';
}

function fmtFechaCorta(s) {
    if (!s) return '';
    const d = new Date(String(s).replace(' ', 'T'));
    return Number.isNaN(d.getTime()) ? String(s) : d.toLocaleDateString('es-AR');
}

function fmtAnteriores(it) {
    const arr = it.anteriores || [];
    if (arr.length === 0) return '';
    const partes = arr.map((a) => {
        const v = (a.valor_numerico !== null && a.valor_numerico !== undefined && a.valor_numerico !== '')
            ? String(parseFloat(a.valor_numerico)).replace('.', ',')
            : (a.valor_texto || '');
        const color = a.es_anormal ? ' style="color:#c0392b;font-weight:600;"' : '';
        return `<span${color}>${esc(v)}</span> <span class="muted">(${esc(fmtFechaCorta(a.fecha))})</span>`;
    });
    return `<div class="muted" style="font-size:.74rem;margin-top:3px;" title="Resultados anteriores del paciente"><i class="bi bi-graph-up"></i> ${partes.join(' · ')}</div>`;
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

function valorParaInput(r) {
    if (!r) return '';
    const n = r.valor_numerico;
    if (n !== null && n !== undefined && n !== '') {
        const f = parseFloat(n);
        return Number.isNaN(f) ? String(n) : String(f);
    }
    return r.valor_texto ?? '';
}

function renderItems() {
    if (state.items.length === 0) {
        $lista.innerHTML = '<tr><td colspan="7" class="muted" style="text-align:center;">Sin items</td></tr>';
        return;
    }
    $lista.innerHTML = state.items.map((it) => {
        const r = state.resultados[it.id];
        const valActual = valorParaInput(r);
        const estadoItem = it.estado;
        const disabled = estadoItem === 'validado' ? 'disabled' : '';
        let marca = '';
        if (r) {
            if (r.es_critico) marca = '<span class="badge badge-critico">CRITICO</span>';
            else if (r.es_anormal) marca = '<span class="badge badge-anormal">ANORMAL</span>';
        }
        const accion = r
            ? `<button type="button" class="btn btn-ghost btn-sm" data-accion="quitar" data-item-id="${it.id}" title="Quitar resultado"><i class="bi bi-trash"></i> Quitar</button>`
            : '';
        return `
        <tr data-item-id="${it.id}">
            <td>
                <strong>${esc(it.determinacion_nombre || '-')}</strong>
                ${it.determinacion_codigo ? `<div class="muted" style="font-size:.78rem;">${esc(it.determinacion_codigo)}</div>` : ''}
                ${fmtAnteriores(it)}
            </td>
            <td><input type="text" name="valor" value="${esc(valActual)}" ${disabled} placeholder="valor" inputmode="decimal"></td>
            <td>${esc(it.unidad || '')}</td>
            <td class="muted" style="white-space: pre-line;">${esc(fmtRango(r) || fmtRango(it))}</td>
            <td>${badge(estadoItem)}</td>
            <td>${marca}</td>
            <td>${accion}</td>
        </tr>`;
    }).join('');
}

$lista.addEventListener('click', async (e) => {
    const btn = e.target.closest('button[data-accion="quitar"]');
    if (!btn) return;
    const itemId = parseInt(btn.dataset.itemId, 10);
    if (!(itemId > 0)) return;
    if (!confirm('¿Quitar el resultado cargado de este análisis?')) return;
    btn.disabled = true;
    try {
        await api.delete(`/api/resultados?pedido_item_id=${itemId}`);
        await buscarPedido(state.pedidoId);
        await cargarPedidos();
        showMsg('Resultado quitado', 'exito');
    } catch (err) {
        btn.disabled = false;
        showMsg(`Error al quitar: ${err.message}`, 'error');
    }
});

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

async function buscarSugerencias(q) {
    const nro = ++busquedaActual;
    try {
        const data = await api.get(`/api/pedidos?accion=listar&q=${encodeURIComponent(q)}&page=1`);
        if (nro !== busquedaActual) return;
        renderSugerencias(data.pedidos || []);
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
    $bres.innerHTML = pedidos.map((p) => {
        const pac = esc(p.paciente_nombre || `${p.paciente_apellido || ''}, ${p.paciente_nombres || ''}`.replace(/^, $|^, |, $/g, '') || '-');
        const hc = (p.paciente_nro_hc || p.paciente_dni) ? ` (${esc(p.paciente_nro_hc || p.paciente_dni)})` : '';
        return `
        <li data-pedido-id="${p.id}">
            <span><strong>N° ${esc(p.numero)}</strong> · ${pac}${hc}</span>
            <span class="muted">${esc(fmtFecha(p.fecha_solicitud))} · ${badge(p.estado)}</span>
        </li>`;
    }).join('');
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

$lista.addEventListener('keydown', (e) => {
    if (e.key !== 'Enter' || e.target.name !== 'valor') return;
    e.preventDefault();
    const inputs = Array.from($lista.querySelectorAll('input[name="valor"]:not([disabled])'));
    const idx = inputs.indexOf(e.target);
    if (idx >= 0 && idx < inputs.length - 1) {
        inputs[idx + 1].focus();
        inputs[idx + 1].select();
    } else {
        e.target.blur();
    }
});

const $btnGuardarTodos = $('btn-guardar-todos');
$btnGuardarTodos?.addEventListener('click', guardarTodos);

function leerValorFila(tr) {
    const input = tr.querySelector('input[name="valor"]');
    if (!input || input.disabled) return null;
    const raw = (input.value || '').trim();
    if (raw === '') return null;
    const itemId = parseInt(tr.dataset.itemId, 10);
    const numerico = raw.replace(',', '.');
    const payload = { pedido_item_id: itemId };
    if (/^-?\d+(\.\d+)?$/.test(numerico)) payload.valor_numerico = parseFloat(numerico);
    else payload.valor_texto = raw;
    return payload;
}

async function guardarTodos() {
    if (!state.pedidoId) return;
    const aGuardar = Array.from($lista.querySelectorAll('tr[data-item-id]'))
        .map(leerValorFila)
        .filter((p) => p !== null);

    if (aGuardar.length === 0) {
        showMsg('No hay valores cargados para guardar', 'error');
        return;
    }

    if ($btnGuardarTodos) { $btnGuardarTodos.disabled = true; }
    let ok = 0;
    const errores = [];
    for (const payload of aGuardar) {
        try {
            await api.post('/api/resultados', payload);
            ok++;
        } catch (err) {
            errores.push(`item ${payload.pedido_item_id}: ${err.message}`);
        }
    }
    if ($btnGuardarTodos) { $btnGuardarTodos.disabled = false; }

    await buscarPedido(state.pedidoId);
    await cargarPedidos();

    if (errores.length === 0) {
        showMsg(`${ok} resultado(s) guardado(s)`, 'exito');
    } else {
        showMsg(`Guardados ${ok}, con error ${errores.length}: ${errores.join(' · ')}`, 'error');
    }
}

const $pedidos = document.getElementById('lista-pedidos');
const $filtroDia = document.getElementById('filtro-dia');
const $btnHoy = document.getElementById('btn-hoy');
const $btnTodos = document.getElementById('btn-todos');
const $pedidosInfo = document.getElementById('pedidos-info');

async function cargarPedidos() {
    if (!$pedidos) return;
    const dia = ($filtroDia?.value || '').trim();
    const params = new URLSearchParams({ accion: 'listar', orden: 'fecha_solicitud_desc', page: '1' });
    if (dia !== '') {
        params.set('fecha_solicitud_desde', dia);
        params.set('fecha_solicitud_hasta', dia);
    }
    $pedidos.innerHTML = '<tr><td colspan="6" class="muted" style="text-align:center;">Cargando…</td></tr>';
    try {
        const data = await api.get(`/api/pedidos?${params.toString()}`);
        renderPedidos(data?.pedidos ?? []);
        if ($pedidosInfo) {
            const total = data?.total ?? (data?.pedidos?.length ?? 0);
            $pedidosInfo.textContent = dia !== ''
                ? `${total} pedido(s) del ${formatSoloFecha(dia)}`
                : `${total} pedido(s) en total`;
        }
    } catch (e) {
        $pedidos.innerHTML = `<tr><td colspan="6" class="muted">Error: ${esc(e.message)}</td></tr>`;
    }
}

function renderPedidos(pedidos) {
    if (pedidos.length === 0) {
        $pedidos.innerHTML = '<tr><td colspan="6" class="muted" style="text-align:center;">No hay pedidos para ese criterio.</td></tr>';
        return;
    }
    $pedidos.innerHTML = pedidos.map((p) => {
        const nombre = p.paciente_nombre || `${p.paciente_apellido || ''}, ${p.paciente_nombres || ''}`.replace(/^, $|^, |, $/g, '') || '-';
        return `
        <tr>
            <td><strong>${esc(p.numero)}</strong></td>
            <td>${esc(formatFecha(p.fecha_solicitud))}</td>
            <td>${esc(nombre)}</td>
            <td>${badge(p.estado)}</td>
            <td>${esc(p.prioridad ?? '')}</td>
            <td>
                <button type="button" class="btn btn-ghost btn-sm" data-accion="abrir-pedido" data-id="${p.id}">
                    <i class="bi bi-arrow-right-circle"></i> Cargar
                </button>
            </td>
        </tr>`;
    }).join('');
}

function formatFecha(iso) {
    if (!iso) return '';
    const d = new Date(String(iso).replace(' ', 'T'));
    if (isNaN(d.getTime())) return iso;
    return d.toLocaleString('es-AR', { dateStyle: 'short', timeStyle: 'short' });
}

function formatSoloFecha(ymd) {
    const d = new Date(`${ymd}T00:00:00`);
    return isNaN(d.getTime()) ? ymd : d.toLocaleDateString('es-AR');
}

if ($pedidos) {
    $pedidos.addEventListener('click', async (e) => {
        const btn = e.target.closest('button[data-accion="abrir-pedido"]');
        if (!btn) return;
        const id = parseInt(btn.dataset.id, 10);
        if (id > 0) {
            await buscarPedido(id);
            $panel?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });
    $filtroDia?.addEventListener('change', cargarPedidos);
    $btnHoy?.addEventListener('click', () => {
        const hoy = new Date();
        const ymd = `${hoy.getFullYear()}-${String(hoy.getMonth() + 1).padStart(2, '0')}-${String(hoy.getDate()).padStart(2, '0')}`;
        if ($filtroDia) $filtroDia.value = ymd;
        cargarPedidos();
    });
    $btnTodos?.addEventListener('click', () => {
        if ($filtroDia) $filtroDia.value = '';
        cargarPedidos();
    });
    cargarPedidos();
}
