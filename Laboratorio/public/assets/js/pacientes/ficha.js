import { api, labPath } from '../api.js';
import { PacienteSelector } from './paciente-selector.js';

const $ = (id) => document.getElementById(id);

const esc = (s) => String(s ?? '').replace(/[<>&"']/g, (c) => ({
    '<': '&lt;', '>': '&gt;', '&': '&amp;', '"': '&quot;', "'": '&#39;',
}[c]));

const badge = (estado) =>
    `<span class="badge badge-${esc(estado)}">${esc(String(estado).replace('_', ' '))}</span>`;

function fmtFecha(s) {
    if (!s) return '';
    const d = new Date(String(s).replace(' ', 'T'));
    return Number.isNaN(d.getTime()) ? String(s) : d.toLocaleString('es-AR', { dateStyle: 'short', timeStyle: 'short' });
}

function renderFicha(p) {
    const out = $('resultado');
    out.className = '';
    out.innerHTML = `
        <dl class="data-grid">
            <dt>HC</dt><dd>${esc(p.nro_hc)}</dd>
            <dt>Paciente</dt><dd><strong>${esc(p.apellido)}, ${esc(p.nombres)}</strong></dd>
            <dt>DNI</dt><dd>${esc(p.dni)}</dd>
            <dt>Sexo</dt><dd>${esc(p.sexo)}</dd>
            <dt>Obra social</dt><dd>${esc(p.obra_social_nombre ?? '(sin)')}</dd>
            <dt>N&deg; afiliado</dt><dd>${esc(p.nro_afiliado ?? '(sin)')}</dd>
        </dl>
        <div class="form-actions" style="margin-top:1rem;">
            <a class="btn btn-primary" href="${labPath(`/pedidos/nuevo?paciente_id=${encodeURIComponent(p.id)}`)}">
                <i class="bi bi-clipboard2-plus"></i> Generar an&aacute;lisis nuevo
            </a>
        </div>`;
}

async function cargarPedidos(pacienteId) {
    const cont = $('pedidos-paciente');
    const info = $('pedidos-paciente-info');
    cont.innerHTML = '<tr><td colspan="6" class="muted" style="text-align:center;">Cargando&hellip;</td></tr>';
    try {
        const data = await api.get(`/api/historial?paciente_id=${encodeURIComponent(pacienteId)}`);
        const pedidos = data?.pedidos ?? [];
        if (info) info.textContent = `${data?.total ?? pedidos.length} análisis`;
        if (pedidos.length === 0) {
            cont.innerHTML = '<tr><td colspan="6" class="muted" style="text-align:center;">Este paciente no tiene an&aacute;lisis cargados.</td></tr>';
            return;
        }
        cont.innerHTML = pedidos.map((p) => `
            <tr>
                <td><strong>${esc(p.numero)}</strong></td>
                <td>${esc(fmtFecha(p.fecha_solicitud))}</td>
                <td>${badge(p.estado)}${p.es_critico ? ' <span class="badge badge-critico">CRITICO</span>' : ''}</td>
                <td>${esc(p.items_count ?? '')}</td>
                <td>${esc(p.prioridad ?? '')}</td>
                <td><a class="btn btn-ghost btn-sm" href="${labPath(`/pedidos/ver?id=${esc(p.id)}`)}"><i class="bi bi-eye"></i> Ver resultados</a></td>
            </tr>`).join('');
    } catch (e) {
        cont.innerHTML = `<tr><td colspan="6" class="muted">Error: ${esc(e.message)}</td></tr>`;
    }
}

async function abrirSelector() {
    const p = await PacienteSelector.elegir({ titulo: 'Elegir paciente del sistema' });
    const out = $('resultado');
    if (!p) {
        out.className = 'muted';
        out.textContent = 'Cancelado.';
        return;
    }
    renderFicha(p);
    $('panel-analisis').hidden = false;
    await cargarPedidos(p.id);
}

$('abrir-selector').addEventListener('click', abrirSelector);
