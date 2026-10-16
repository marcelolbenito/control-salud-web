import { api } from '../api.js?v=13';
import { crearItemsPanel } from '../pedidos/items-panel.js?v=13';

const $ = (id) => document.getElementById(id);
const $msg = $('mensaje');
const $panel = $('panel-pedido');

const esc = (s) => String(s ?? '').replace(/[<>&"']/g, (c) => ({
    '<': '&lt;', '>': '&gt;', '&': '&amp;', '"': '&quot;', "'": '&#39;',
}[c]));

const badge = (estado) => `<span class="badge badge-${esc(estado)}">${esc(String(estado).replace('_', ' '))}</span>`;

function showMsg(text, tipo = 'exito') {
    $msg.className = `mensaje ${tipo}`;
    $msg.textContent = text;
    $msg.hidden = false;
}

// Panel compartido: carga de resultados + agregar/sacar analisis + anteriores.
const panel = crearItemsPanel({
    tbody: $('lista-items'),
    infoEl: $('info-pedido'),
    addInput: $('cargar-add'),
    addList: $('cargar-add-resultados'),
    guardarBtn: $('btn-guardar-todos'),
    msg: showMsg,
    onChange: () => cargarPedidos(),
});

async function abrirPedido(pedidoId) {
    $msg.hidden = true;
    try {
        await panel.open(pedidoId);
        $panel.hidden = false;
    } catch (e) {
        $panel.hidden = true;
        showMsg(`Error: ${e.message}`, 'error');
    }
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

async function buscarSugerencias(q) {
    const nro = ++busquedaActual;
    try {
        const data = await api.get(`/api/pedidos?accion=listar&q=${encodeURIComponent(q)}&page=1`);
        if (nro !== busquedaActual) return; // descarta resultados viejos
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
    await abrirPedido(id);
});

document.addEventListener('click', (e) => {
    if (!$bres.contains(e.target) && e.target !== $bq) ocultarDropdown();
});

// ----- Listado de pedidos con filtro por día -----

const $pedidos = $('lista-pedidos');
const $filtroDia = $('filtro-dia');
const $btnHoy = $('btn-hoy');
const $btnTodos = $('btn-todos');
const $pedidosInfo = $('pedidos-info');

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
            await abrirPedido(id);
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

// Permite abrir directo un pedido via ?pedido_id=N (ej: link desde otra vista).
const preId = parseInt(new URLSearchParams(window.location.search).get('pedido_id') || '', 10);
if (preId > 0) {
    abrirPedido(preId).then(() => $panel?.scrollIntoView({ behavior: 'smooth', block: 'start' }));
}
