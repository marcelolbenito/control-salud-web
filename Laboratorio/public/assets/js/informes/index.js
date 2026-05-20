import { api } from '/assets/js/api.js';

const $ = (id) => document.getElementById(id);
const $msg = $('mensaje');
const $panel = $('panel-pedido');
const $info = $('info-pedido');
const $lista = $('lista-informes');

const state = { pedidoId: null };

const esc = (s) => String(s ?? '').replace(/[<>&"']/g, (c) => ({
    '<': '&lt;', '>': '&gt;', '&': '&amp;', '"': '&quot;', "'": '&#39;',
}[c]));

const badge = (estado) => `<span class="badge badge-${esc(estado)}">${esc(estado.replace('_', ' '))}</span>`;

function showMsg(text, tipo = 'exito') {
    $msg.className = `mensaje ${tipo}`;
    $msg.textContent = text;
    $msg.hidden = false;
}

async function buscarPedido(pedidoId) {
    $msg.hidden = true;
    try {
        const dossier = await api.get(`/api/historial?pedido_id=${pedidoId}`);
        state.pedidoId = pedidoId;
        renderInfo(dossier.pedido);
        renderInformes(dossier.informes);
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
        return `
        <tr>
            <td><strong>${esc(i.numero)}</strong></td>
            <td>${esc(i.fecha_emision)}</td>
            <td>${tipo}</td>
            <td>${estado}</td>
            <td>
                <a class="btn btn-primary btn-sm" href="/api/informes?id=${i.id}&download=1" target="_blank"><i class="bi bi-download"></i> Descargar</a>
                ${accionEntrega}
            </td>
        </tr>`;
    }).join('');
}

$('form-buscar').addEventListener('submit', async (e) => {
    e.preventDefault();
    const id = parseInt($('buscar-pedido-id').value, 10);
    if (id > 0) await buscarPedido(id);
});

$('form-generar').addEventListener('submit', async (e) => {
    e.preventDefault();
    if (!state.pedidoId) return;
    const fd = new FormData(e.target);
    const payload = {
        pedido_id: state.pedidoId,
        es_parcial: fd.get('es_parcial') === '1',
        firma: (fd.get('firma') || '').toString().trim() || undefined,
        observaciones: (fd.get('observaciones') || '').toString().trim() || undefined,
    };
    try {
        const r = await api.post('/api/informes', payload);
        showMsg(`Informe ${r.numero} emitido (hash ${r.hash_pdf.slice(0, 12)}...)`, 'exito');
        await buscarPedido(state.pedidoId);
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
        await buscarPedido(state.pedidoId);
    } catch (err) {
        showMsg(`Error: ${err.message}`, 'error');
    }
});
