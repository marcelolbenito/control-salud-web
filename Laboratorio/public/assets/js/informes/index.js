import { api, labPath } from '../api.js';

const $ = (id) => document.getElementById(id);
const $msg = $('mensaje');
const $panel = $('panel-pedido');
const $info = $('info-pedido');
const $lista = $('lista-informes');
const $recientes = $('lista-recientes');
const $listos = $('lista-listos');

const state = { pedidoId: null, numero: null };

const esc = (s) => String(s ?? '').replace(/[<>&"']/g, (c) => ({
    '<': '&lt;', '>': '&gt;', '&': '&amp;', '"': '&quot;', "'": '&#39;',
}[c]));

const badge = (estado) => `<span class="badge badge-${esc(estado)}">${esc(estado.replace('_', ' '))}</span>`;

function showMsg(text, tipo = 'exito') {
    $msg.className = `mensaje ${tipo}`;
    $msg.textContent = text;
    $msg.hidden = false;
}

async function buscarPedidoPorNumero(numero) {
    $msg.hidden = true;
    try {
        const dossier = await api.get(`/api/historial?numero=${encodeURIComponent(numero)}`);
        state.pedidoId = dossier.pedido.id;
        state.numero = dossier.pedido.numero;
        renderInfo(dossier.pedido);
        renderInformes(dossier.informes);
        $panel.hidden = false;
    } catch (e) {
        $panel.hidden = true;
        showMsg(`Error: ${e.message}`, 'error');
    }
}

async function recargarPedido() {
    if (state.numero != null) {
        await buscarPedidoPorNumero(state.numero);
    }
}

function renderInfo(p) {
    const snap = p.snapshot_paciente || {};
    const nombre = typeof snap === 'object' ? (snap.nombre || '-') : '-';
    $info.innerHTML = `
        <strong>${esc(p.numero)}</strong> · Paciente: ${esc(nombre)} · Estado: ${badge(p.estado)}
    `;
}

function renderInformes(informes) {
    if (!informes || informes.length === 0) {
        $lista.innerHTML = '<tr><td colspan="5" class="muted" style="text-align:center;">Sin informes emitidos</td></tr>';
        return;
    }
    $lista.innerHTML = informes.map((i) => {
        const tipo = parseInt(i.es_parcial, 10)
            ? '<span class="badge badge-rectificado">PARCIAL</span>'
            : '<span class="badge badge-completo">COMPLETO</span>';
        const estado = parseInt(i.entregado, 10) ? badge('entregado') : badge('pendiente');
        const accionEntrega = parseInt(i.entregado, 10)
            ? ''
            : `<button class="btn btn-ghost btn-sm" data-accion="entregar" data-id="${i.id}"><i class="bi bi-bag-check"></i> Marcar entregado</button>`;
        const pdfUrl = labPath(`/api/informes?id=${i.id}&download=1`);
        return `
        <tr>
            <td><strong>${esc(i.numero)}</strong></td>
            <td>${esc(i.fecha_emision)}</td>
            <td>${tipo}</td>
            <td>${estado}</td>
            <td>
                <a class="btn btn-primary btn-sm" href="${esc(pdfUrl)}" target="_blank"><i class="bi bi-download"></i> Descargar</a>
                ${accionEntrega}
            </td>
        </tr>`;
    }).join('');
}

$('form-buscar').addEventListener('submit', async (e) => {
    e.preventDefault();
    const numero = ($('buscar-pedido-id').value || '').toString().trim();
    if (numero !== '') await buscarPedidoPorNumero(numero);
});

$('form-generar').addEventListener('submit', async (e) => {
    e.preventDefault();
    if (!state.pedidoId) return;
    const fd = new FormData(e.target);
    const payload = {
        pedido_id: state.pedidoId,
        es_parcial: fd.get('es_parcial') === '1',
        observaciones: (fd.get('observaciones') || '').toString().trim() || undefined,
    };
    try {
        const r = await api.post('/api/informes', payload);
        showMsg(`Informe ${r.numero} emitido (hash ${r.hash_pdf.slice(0, 12)}...)`, 'exito');
        await recargarPedido();
        await cargarRecientes();
        await cargarListos();
    } catch (err) {
        showMsg(`Error: ${err.message}`, 'error');
    }
});

$lista.addEventListener('click', async (e) => {
    const btn = e.target.closest('button[data-accion="entregar"]');
    if (!btn) return;
    const destinatario = window.prompt('Destinatario (paciente, familiar, etc.):');
    if (destinatario == null) return;
    try {
        await api.post(`/api/informes?accion=marcar-entregado&id=${btn.dataset.id}`, {
            destinatario: destinatario.trim() || null,
        });
        showMsg('Informe marcado entregado', 'exito');
        await recargarPedido();
        await cargarRecientes();
    } catch (err) {
        showMsg(`Error: ${err.message}`, 'error');
    }
});

function leerFiltros() {
    const form = $('form-filtros-informes');
    if (!form) return {};
    const fd = new FormData(form);
    const out = {};
    for (const [k, v] of fd.entries()) {
        const s = (v || '').toString().trim();
        if (s !== '') out[k] = s;
    }
    return out;
}

function buildQuery(filtros) {
    const qs = new URLSearchParams({ accion: 'recientes', ...filtros });
    return `/api/informes?${qs.toString()}`;
}

async function cargarRecientes(filtros = {}) {
    if (!$recientes) return;
    try {
        const r = await api.get(buildQuery(filtros));
        renderRecientes(r.informes || []);
    } catch (e) {
        $recientes.innerHTML = `<tr><td colspan="8" class="muted">Error: ${esc(e.message)}</td></tr>`;
    }
}

function renderRecientes(informes) {
    if (informes.length === 0) {
        $recientes.innerHTML = '<tr><td colspan="8" class="muted" style="text-align:center;">Sin informes</td></tr>';
        return;
    }
    $recientes.innerHTML = informes.map((i) => {
        const tipo = parseInt(i.es_parcial, 10)
            ? '<span class="badge badge-rectificado">PARCIAL</span>'
            : '<span class="badge badge-completo">COMPLETO</span>';
        const estado = parseInt(i.entregado, 10) ? badge('entregado') : badge('pendiente');
        const pdfUrl = labPath(`/api/informes?id=${i.id}&download=1`);
        return `
        <tr>
            <td><strong>${esc(i.numero)}</strong></td>
            <td>${esc(i.fecha_emision)}</td>
            <td>${esc(i.pedido_numero ?? '')}</td>
            <td>${esc(i.paciente_nombre ?? '—')}</td>
            <td>${esc(i.paciente_dni ?? '—')}</td>
            <td>${tipo}</td>
            <td>${estado}</td>
            <td>
                <a class="btn btn-primary btn-sm" href="${esc(pdfUrl)}" target="_blank"><i class="bi bi-download"></i> PDF</a>
            </td>
        </tr>`;
    }).join('');
}

const $formFiltros = $('form-filtros-informes');
if ($formFiltros) {
    $formFiltros.addEventListener('submit', (e) => {
        e.preventDefault();
        cargarRecientes(leerFiltros());
    });
}
const $btnLimpiar = $('btn-limpiar-filtros');
if ($btnLimpiar) {
    $btnLimpiar.addEventListener('click', () => {
        $formFiltros.reset();
        cargarRecientes();
    });
}

function fmtFecha(iso) {
    if (!iso) return '';
    const d = new Date(String(iso).replace(' ', 'T'));
    return isNaN(d.getTime()) ? iso : d.toLocaleString('es-AR', { dateStyle: 'short', timeStyle: 'short' });
}

function urlListos(estado) {
    const params = new URLSearchParams({ accion: 'listar', estado, orden: 'fecha_solicitud_desc' });
    const q = ($('listos-q')?.value || '').trim();
    const dia = ($('listos-dia')?.value || '').trim();
    if (q !== '') params.set('q', q);
    if (dia !== '') {
        params.set('fecha_solicitud_desde', dia);
        params.set('fecha_solicitud_hasta', dia);
    }
    return `/api/pedidos?${params.toString()}`;
}

async function cargarListos() {
    if (!$listos) return;
    try {
        const [comp, parc] = await Promise.all([
            api.get(urlListos('completo')),
            api.get(urlListos('parcial')),
        ]);
        const all = [...(comp?.pedidos ?? []), ...(parc?.pedidos ?? [])];
        all.sort((a, b) => String(b.fecha_solicitud).localeCompare(String(a.fecha_solicitud)));
        renderListos(all);
        const info = $('listos-info');
        if (info) info.textContent = `${all.length} pedido(s)`;
    } catch (e) {
        $listos.innerHTML = `<tr><td colspan="5" class="muted">Error: ${esc(e.message)}</td></tr>`;
    }
}

let listosDebounce = null;
$('listos-q')?.addEventListener('input', () => {
    clearTimeout(listosDebounce);
    listosDebounce = setTimeout(cargarListos, 300);
});
$('listos-dia')?.addEventListener('change', cargarListos);
$('listos-limpiar')?.addEventListener('click', () => {
    const q = $('listos-q'); const d = $('listos-dia');
    if (q) q.value = '';
    if (d) d.value = '';
    cargarListos();
});

function renderListos(pedidos) {
    if (!pedidos || pedidos.length === 0) {
        $listos.innerHTML = '<tr><td colspan="5" class="muted" style="text-align:center;">No hay pedidos cargados pendientes de informe.</td></tr>';
        return;
    }
    $listos.innerHTML = pedidos.map((p) => {
        const nombre = p.paciente_nombre || `${p.paciente_apellido || ''}, ${p.paciente_nombres || ''}`.replace(/^, $|^, |, $/g, '') || '-';
        return `
        <tr>
            <td><strong>${esc(p.numero)}</strong></td>
            <td>${esc(fmtFecha(p.fecha_solicitud))}</td>
            <td>${esc(nombre)}</td>
            <td>${badge(p.estado)}</td>
            <td>
                <button type="button" class="btn btn-primary btn-sm" data-accion="emitir" data-numero="${esc(p.numero)}">
                    <i class="bi bi-file-earmark-plus"></i> Emitir informe
                </button>
            </td>
        </tr>`;
    }).join('');
}

if ($listos) {
    $listos.addEventListener('click', async (e) => {
        const btn = e.target.closest('button[data-accion="emitir"]');
        if (!btn) return;
        const numero = btn.dataset.numero;
        if (!numero) return;
        await buscarPedidoPorNumero(numero);
        $panel?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
}

async function cargarFirmante() {
    const el = $('firmante-info');
    if (!el) return;
    try {
        const cfg = await api.get('/api/lab-config');
        const f = cfg.firmante || cfg;
        const partes = [f.titulo, f.apellido, f.nombres].filter(Boolean).join(' ');
        const mat = f.matricula ? ` · Mat. ${f.matricula}` : '';
        el.value = partes ? `${partes}${mat}` : '—';
    } catch {
        el.value = '—';
    }
}

cargarRecientes();
cargarListos();
cargarFirmante();
