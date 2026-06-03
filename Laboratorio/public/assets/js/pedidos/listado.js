/**
 * Listado de pedidos — sub-proyecto 2.
 * Reusa PacienteSelector (sub-proyecto 1) para el filtro de paciente.
 */
import { api, labPath } from '../api.js?v=10';
import { PacienteSelector } from '../pacientes/paciente-selector.js?v=10';

const ENDPOINT = '/api/pedidos';

const els = {
    form:            document.getElementById('ord-form'),
    pacienteId:      document.getElementById('f-paciente-id'),
    pacienteLabel:   document.getElementById('f-paciente-label'),
    btnElegir:       document.getElementById('btn-elegir-paciente'),
    btnLimpiarPac:   document.getElementById('btn-limpiar-paciente'),
    btnLimpiar:      document.getElementById('btn-limpiar'),
    obraSocialSel:   document.getElementById('f-obra'),
    tbody:           document.getElementById('ord-tbody'),
    info:            document.getElementById('ord-info'),
    bannerSlot:      document.getElementById('ord-banner-slot'),
    btnPrev:         document.getElementById('btn-prev'),
    btnNext:         document.getElementById('btn-next'),
    paginacionInfo:  document.getElementById('paginacion-info'),
};

let estado = {
    page: 1,
    orden: 'fecha_solicitud_desc',
    ultimo: null, // último resultado para sostener paginación
};

// ========= Init =========

(async function init() {
    await Promise.all([cargarObrasSociales(), cargarMedicos()]);
    cablearEventos();
    cablearModalPlanilla();
    ejecutarBusqueda();
})();

async function cargarObrasSociales() {
    try {
        const data = await api.get('/api/pacientes?accion=obras_sociales');
        const obras = data.obras_sociales ?? [];
        for (const os of obras) {
            const opt = document.createElement('option');
            opt.value = String(os.id);
            opt.textContent = os.nombre;
            els.obraSocialSel.appendChild(opt);
        }
    } catch (e) {
        mostrarBannerError('No se pudieron cargar las obras sociales: ' + e.message);
    }
}

async function cargarMedicos() {
    const sel = document.getElementById('f-medico-id');
    if (!sel) return;
    try {
        const data = await api.get('/api/medicos?accion=buscar&limite=100');
        for (const m of (data.medicos ?? [])) {
            const opt = document.createElement('option');
            opt.value = String(m.id);
            opt.textContent = `${m.apellido}, ${m.nombres}` + (m.especialidad ? ` — ${m.especialidad}` : '');
            sel.appendChild(opt);
        }
    } catch (e) {
        mostrarBannerError('No se pudieron cargar los médicos: ' + e.message);
    }
}

// ========= Modal planilla =========
const elsPlanilla = {
    overlay:    document.getElementById('planilla-overlay'),
    btnAbrir:   document.getElementById('btn-abrir-planilla'),
    btnCerrar:  document.getElementById('btn-cancelar-planilla'),
    btnGenerar: document.getElementById('btn-generar-planilla'),
    selectPerfil: document.getElementById('planilla-perfil'),
    info:       document.getElementById('planilla-info'),
};

let perfilesCache = null;

async function abrirModalPlanilla(e) {
    e?.preventDefault?.();
    if (!estado.ultimo || !estado.ultimo.pedidos || estado.ultimo.pedidos.length === 0) {
        alert('Primero hacé una búsqueda con al menos un resultado para incluir órdenes en la planilla.');
        return;
    }
    if (!perfilesCache) {
        try {
            const data = await api.get('/api/perfiles');
            perfilesCache = data.perfiles ?? [];
            elsPlanilla.selectPerfil.innerHTML = '<option value="">— elegir perfil —</option>' +
                perfilesCache.map(p =>
                    `<option value="${p.id}">${escapeHtml(p.nombre)} (${p.determinaciones?.length ?? 0} det.)</option>`
                ).join('');
        } catch (err) {
            alert('No se pudieron cargar los perfiles: ' + (err.message || 'error'));
            return;
        }
    }
    const totalPed = estado.ultimo.pedidos.length;
    elsPlanilla.info.textContent = `Se incluirán las ${totalPed} órdenes visibles en la página actual.`;
    elsPlanilla.btnGenerar.disabled = !elsPlanilla.selectPerfil.value;
    elsPlanilla.overlay.hidden = false;
}

function cerrarModalPlanilla() {
    elsPlanilla.overlay.hidden = true;
}

async function generarPlanilla() {
    const perfilId = parseInt(elsPlanilla.selectPerfil.value, 10);
    if (!perfilId) return;
    const pedidoIds = (estado.ultimo?.pedidos ?? []).map(p => p.id);

    try {
        const resp = await fetch(labPath('/api/planillas?accion=por_perfil'), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ perfil_id: perfilId, pedido_ids: pedidoIds }),
        });
        if (!resp.ok) {
            const txt = await resp.text();
            alert('No se pudo generar la planilla: ' + txt);
            return;
        }
        // Abrir el blob en pestaña nueva.
        const blob = await resp.blob();
        const url = URL.createObjectURL(blob);
        window.open(url, '_blank', 'noopener');
        cerrarModalPlanilla();
    } catch (err) {
        alert('Error de red: ' + (err.message || ''));
    }
}

function cablearModalPlanilla() {
    elsPlanilla.btnAbrir.addEventListener('click', abrirModalPlanilla);
    elsPlanilla.btnCerrar.addEventListener('click', cerrarModalPlanilla);
    elsPlanilla.btnGenerar.addEventListener('click', generarPlanilla);
    elsPlanilla.selectPerfil.addEventListener('change', () => {
        elsPlanilla.btnGenerar.disabled = !elsPlanilla.selectPerfil.value;
        const perfilId = parseInt(elsPlanilla.selectPerfil.value, 10);
        const totalPed = estado.ultimo?.pedidos?.length ?? 0;
        const baseMsg = `Se incluirán las ${totalPed} órdenes visibles en la página actual.`;
        if (!perfilId) {
            elsPlanilla.info.textContent = baseMsg;
            return;
        }
        const perfil = (perfilesCache || []).find((p) => p.id === perfilId);
        const dets = perfil?.determinaciones ?? [];
        const detalle = dets.length > 0
            ? dets.map((d) => d.codigo || d.nombre).join(' · ')
            : '(sin determinaciones)';
        elsPlanilla.info.innerHTML = `${escapeHtml(baseMsg)}<br><small class="perfil-detalle">Incluye: ${escapeHtml(detalle)}</small>`;
    });
    elsPlanilla.overlay.addEventListener('click', (e) => {
        if (e.target === elsPlanilla.overlay) cerrarModalPlanilla();
    });
}

function cablearEventos() {
    els.form.addEventListener('submit', (e) => {
        e.preventDefault();
        estado.page = 1;
        ejecutarBusqueda();
    });

    els.btnLimpiar.addEventListener('click', () => {
        els.form.reset();
        limpiarPaciente();
        estado.page = 1;
        estado.orden = 'fecha_solicitud_desc';
        els.tbody.innerHTML = `<tr><td colspan="9" class="muted" style="text-align:center; padding: 1.5rem;">Aplicá filtros y presioná Buscar.</td></tr>`;
        els.info.textContent = '— sin búsqueda —';
        els.paginacionInfo.textContent = 'Página 0 de 0';
        els.btnPrev.disabled = true;
        els.btnNext.disabled = true;
        els.bannerSlot.innerHTML = '';
    });

    els.btnElegir.addEventListener('click', async () => {
        const p = await PacienteSelector.elegir({ titulo: 'Filtrar órdenes por paciente' });
        if (p) setPaciente(p);
    });

    els.btnLimpiarPac.addEventListener('click', limpiarPaciente);

    els.btnPrev.addEventListener('click', () => {
        if (estado.page > 1) {
            estado.page--;
            ejecutarBusqueda();
        }
    });

    els.btnNext.addEventListener('click', () => {
        if (estado.ultimo && estado.page < estado.ultimo.total_paginas) {
            estado.page++;
            ejecutarBusqueda();
        }
    });

    // Sort por click en headers
    document.querySelectorAll('.table th[data-sort]').forEach((th) => {
        th.addEventListener('click', () => {
            const base = th.getAttribute('data-sort');
            // Toggle asc/desc si hace click sobre la misma columna.
            if (estado.orden === base) {
                estado.orden = base.replace(/_(asc|desc)$/, (_, d) => d === 'asc' ? '_desc' : '_asc');
            } else {
                estado.orden = base;
            }
            estado.page = 1;
            ejecutarBusqueda();
        });
    });
}

function setPaciente(p) {
    els.pacienteId.value = String(p.id);
    els.pacienteLabel.textContent = `${p.apellido}, ${p.nombres} (HC ${p.nro_hc})`;
    els.btnLimpiarPac.disabled = false;
}

function limpiarPaciente() {
    els.pacienteId.value = '';
    els.pacienteLabel.textContent = '— ninguno —';
    els.btnLimpiarPac.disabled = true;
}

// ========= Búsqueda =========

async function ejecutarBusqueda() {
    els.bannerSlot.innerHTML = '';
    els.tbody.innerHTML = `<tr><td colspan="9" class="muted" style="text-align:center; padding: 1.5rem;">Buscando…</td></tr>`;

    const fd = new FormData(els.form);
    const params = new URLSearchParams({ accion: 'listar', page: String(estado.page), orden: estado.orden });
    for (const [k, v] of fd.entries()) {
        const val = String(v).trim();
        if (val !== '') params.set(k, val);
    }

    try {
        const data = await api.get(`${ENDPOINT}?${params.toString()}`);
        estado.ultimo = data;
        renderResultados(data);
    } catch (err) {
        if (err.status === 422 && err.fields) {
            mostrarBannerError('Filtros inválidos: ' + Object.entries(err.fields).map(([k,v]) => `${k}: ${v}`).join(' · '));
        } else {
            mostrarBannerError(err.message || 'Error al buscar');
        }
        els.tbody.innerHTML = `<tr><td colspan="9" class="muted" style="text-align:center; padding: 1.5rem;">— error —</td></tr>`;
    }
}

function renderResultados(data) {
    const pedidos = data.pedidos ?? [];
    const desde = pedidos.length === 0 ? 0 : (data.page - 1) * data.por_pagina + 1;
    const hasta = Math.min(data.total, (data.page - 1) * data.por_pagina + pedidos.length);

    els.info.textContent = data.total === 0
        ? 'No se encontraron órdenes con esos filtros.'
        : `Mostrando ${desde}–${hasta} de ${data.total}`;

    els.paginacionInfo.textContent = `Página ${data.page} de ${data.total_paginas}`;
    els.btnPrev.disabled = data.page <= 1;
    els.btnNext.disabled = data.page >= data.total_paginas;

    if (pedidos.length === 0) {
        els.tbody.innerHTML = `<tr><td colspan="9" class="muted" style="text-align:center; padding: 1.5rem;">No se encontraron órdenes.</td></tr>`;
        return;
    }

    els.tbody.innerHTML = pedidos.map((p) => {
        const paciente = p.paciente_nombre || [p.paciente_apellido, p.paciente_nombres].filter(Boolean).join(', ');
        const fecha = formatearFecha(p.fecha_solicitud);
        return `
        <tr data-id="${escapeAttr(String(p.id))}">
          <td>${escapeHtml(p.numero ?? '')}</td>
          <td>${escapeHtml(fecha)}</td>
          <td>${escapeHtml(p.paciente_nro_hc || p.paciente_dni || '')}</td>
          <td>${escapeHtml(paciente)}</td>
          <td>${escapeHtml(p.medico_externo ?? '')}</td>
          <td>${escapeHtml(p.obra_social_nombre ?? '')}</td>
          <td><span class="badge badge-${escapeAttr(p.estado)}">${escapeHtml(p.estado.replace('_', ' '))}</span></td>
          <td><span class="badge badge-prio-${escapeAttr(p.prioridad)}">${escapeHtml(p.prioridad)}</span></td>
          <td>${p.es_critico ? '<span class="badge badge-critico" title="Critico">CRITICO</span>' : ''}</td>
        </tr>`;
    }).join('');

    // Click en fila → ver detalle.
    els.tbody.querySelectorAll('tr[data-id]').forEach((tr) => {
        tr.classList.add('is-clickable');
        tr.addEventListener('click', () => {
            window.location.href = labPath(`/pedidos/ver?id=${tr.getAttribute('data-id')}`);
        });
    });
}

function mostrarBannerError(msg) {
    els.bannerSlot.innerHTML = `<span class="alert alert-error" style="display:inline-block; padding: 0.25rem 0.65rem;">${escapeHtml(msg)}</span>`;
}

function formatearFecha(iso) {
    if (!iso) return '';
    const d = new Date(iso.replace(' ', 'T'));
    if (isNaN(d.getTime())) return iso;
    return d.toLocaleString('es-AR', { dateStyle: 'short', timeStyle: 'short' });
}

function escapeHtml(s) {
    return String(s ?? '').replace(/[&<>"']/g, (c) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
    }[c]));
}

function escapeAttr(s) {
    return String(s ?? '').replace(/["'<>&]/g, '');
}
