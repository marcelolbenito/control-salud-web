import { api } from '../api.js?v=10';
import { PacienteSelector } from '../pacientes/paciente-selector.js?v=10';

const state = {
    determinaciones: [],
    perfiles: [],
    seleccionados: new Map(),
};

const $ = (id) => document.getElementById(id);
const $form = $('form-pedido');
const $listaDet = $('lista-det');
const $listaPerfil = $('lista-perfil');
const $listaSel = $('lista-seleccionados');
const $searchDet = $('search-det');
const $searchPerfil = $('search-perfil');
const $msg = $('mensaje');
const $btn = $('btn-submit');
const $contador = $('contador-sel');

const $pacienteId = $('paciente-id');
const $pacienteLabel = $('paciente-label');
const $btnElegirPac = $('btn-elegir-paciente');
const $btnLimpiarPac = $('btn-limpiar-paciente');
const $busquedaPac = $('busqueda-paciente');
const $btnBusquedaPac = $('btn-busqueda-paciente');
const $resultadosPac = $('busqueda-paciente-resultados');
const $snapNombre = $('snap-nombre');
const $snapDni = $('snap-dni');
const $snapSexo = $('snap-sexo');
const $snapFecha = $('snap-fecha-nac');

const FECHA_INVALIDA = '9999-12-31';

async function init() {
    try {
        const [det, perf] = await Promise.all([
            api.get('/api/determinaciones'),
            api.get('/api/perfiles'),
        ]);
        state.determinaciones = det?.determinaciones ?? [];
        state.perfiles = perf?.perfiles ?? [];
        renderListas();
    } catch (e) {
        showError('No se pudo cargar el catalogo: ' + e.message);
    }

    $searchDet.addEventListener('input', renderListas);
    $searchPerfil.addEventListener('input', renderListas);
    $form.addEventListener('submit', onSubmit);
    $('btn-reset').addEventListener('click', () => {
        state.seleccionados.clear();
        limpiarPaciente();
        hideMsg();
        setTimeout(renderListas, 0);
    });

    $btnElegirPac.addEventListener('click', async () => {
        const p = await PacienteSelector.elegir({ titulo: 'Elegir paciente para el pedido' });
        if (p) aplicarPaciente(p);
    });
    $btnLimpiarPac.addEventListener('click', limpiarPaciente);
    $btnBusquedaPac.addEventListener('click', () => buscarPacienteRapido());
    $busquedaPac.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            buscarPacienteRapido();
        }
    });

    await precargarPacienteDesdeUrl();
}

async function precargarPacienteDesdeUrl() {
    const id = new URLSearchParams(window.location.search).get('paciente_id');
    if (!id || !/^\d+$/.test(id)) return;
    try {
        const p = await api.get(`/api/pacientes?accion=obtener&id=${id}`);
        if (p && p.id) aplicarPaciente(p);
    } catch {
        // el usuario puede buscar a mano
    }
}

function aplicarPaciente(p) {
    $pacienteId.value = String(p.id);
    const nombre = [p.apellido, p.nombres].filter(Boolean).join(', ').trim() || p.nombres || p.apellido || '';
    $snapNombre.value = nombre;
    $snapDni.value = p.dni || '';
    $snapSexo.value = ['M', 'F', 'X'].includes(p.sexo) ? p.sexo : 'X';
    const fn = p.fecha_nacimiento && p.fecha_nacimiento !== FECHA_INVALIDA ? p.fecha_nacimiento : '';
    $snapFecha.value = fn;
    $pacienteLabel.textContent = `${p.apellido}, ${p.nombres} (HC ${p.nro_hc}${p.dni ? ' · DNI ' + p.dni : ''})`;
    $btnLimpiarPac.disabled = false;
    $resultadosPac.hidden = true;
    $resultadosPac.innerHTML = '';
}

function limpiarPaciente() {
    $pacienteId.value = '';
    $pacienteLabel.textContent = '— ninguno —';
    $snapNombre.value = '';
    $snapDni.value = '';
    $snapSexo.value = '';
    $snapFecha.value = '';
    $btnLimpiarPac.disabled = true;
    $resultadosPac.hidden = true;
    $resultadosPac.innerHTML = '';
    $busquedaPac.value = '';
}

async function buscarPacienteRapido() {
    const q = ($busquedaPac.value || '').trim();
    if (q === '') {
        showError('Ingresá DNI, HC, apellido o nombre para buscar.');
        return;
    }

    $resultadosPac.hidden = false;
    $resultadosPac.innerHTML = '<p class="muted">Buscando…</p>';

    try {
        const data = await api.get(`/api/pacientes?accion=buscar&q=${encodeURIComponent(q)}&limite=20`);
        const pacientes = data.pacientes ?? [];

        if (pacientes.length === 0) {
            $resultadosPac.innerHTML = '<p class="muted">No se encontraron pacientes. Probá el buscador completo.</p>';
            return;
        }

        if (pacientes.length === 1) {
            aplicarPaciente(pacientes[0]);
            return;
        }

        const ul = document.createElement('ul');
        ul.className = 'lista busqueda-paciente-lista';
        for (const p of pacientes) {
            const li = document.createElement('li');
            li.innerHTML = `<strong>HC ${esc(p.nro_hc)}</strong> · ${esc(p.apellido)}, ${esc(p.nombres)}`
                + (p.dni ? ` · DNI ${esc(p.dni)}` : '');
            li.addEventListener('click', () => aplicarPaciente(p));
            ul.appendChild(li);
        }
        $resultadosPac.innerHTML = '<p class="hint">Varios resultados — elegí uno:</p>';
        $resultadosPac.appendChild(ul);
    } catch (e) {
        $resultadosPac.innerHTML = `<p class="mensaje error">${esc(e.message || 'Error al buscar')}</p>`;
    }
}

function renderListas() {
    renderDet();
    renderPerf();
    renderSeleccionados();
}

function renderDet() {
    const q = ($searchDet.value || '').toLowerCase().trim();
    $listaDet.innerHTML = '';
    const filtradas = state.determinaciones.filter((d) =>
        !q || d.nombre.toLowerCase().includes(q) || d.codigo.toLowerCase().includes(q)
    );
    if (filtradas.length === 0) {
        $listaDet.appendChild(placeholder('Sin resultados'));
        return;
    }
    for (const d of filtradas) {
        const li = document.createElement('li');
        const key = `det-${d.id}`;
        li.innerHTML = `
            <span><strong>${esc(d.codigo)}</strong> &middot; ${esc(d.nombre)}</span>
            <span class="tag">${esc(d.area_codigo)}</span>
        `;
        if (state.seleccionados.has(key)) {
            li.setAttribute('aria-disabled', 'true');
        } else {
            li.addEventListener('click', () => agregar('det', d));
        }
        $listaDet.appendChild(li);
    }
}

function renderPerf() {
    const q = ($searchPerfil.value || '').toLowerCase().trim();
    $listaPerfil.innerHTML = '';
    const filtrados = state.perfiles.filter((p) =>
        !q || p.nombre.toLowerCase().includes(q) || p.codigo.toLowerCase().includes(q)
    );
    if (filtrados.length === 0) {
        $listaPerfil.appendChild(placeholder('Sin resultados'));
        return;
    }
    for (const p of filtrados) {
        const li = document.createElement('li');
        li.classList.add('perfil-item');
        const key = `per-${p.id}`;
        const dets = p.determinaciones || [];
        const cnt = dets.length;
        const detalle = cnt > 0
            ? dets.map((d) => esc(d.codigo || d.nombre)).join(' &middot; ')
            : '(sin determinaciones)';
        li.innerHTML = `
            <div class="perfil-item-main">
                <span class="perfil-item-titulo"><strong>${esc(p.codigo)}</strong> &middot; ${esc(p.nombre)}</span>
                <small class="perfil-detalle">${detalle}</small>
            </div>
            <span class="tag">${cnt} det.</span>
        `;
        if (state.seleccionados.has(key)) {
            li.setAttribute('aria-disabled', 'true');
        } else {
            li.addEventListener('click', () => agregar('per', p));
        }
        $listaPerfil.appendChild(li);
    }
}

function agregar(tipo, item) {
    const key = `${tipo}-${item.id}`;
    if (state.seleccionados.has(key)) return;
    state.seleccionados.set(key, {
        tipo,
        id: item.id,
        codigo: item.codigo,
        nombre: item.nombre,
    });
    renderListas();
}

function quitar(key) {
    state.seleccionados.delete(key);
    renderListas();
}

function renderSeleccionados() {
    $listaSel.innerHTML = '';
    $contador.textContent = String(state.seleccionados.size);

    if (state.seleccionados.size === 0) {
        $listaSel.appendChild(placeholder('(sin items seleccionados)'));
        return;
    }

    for (const [key, item] of state.seleccionados.entries()) {
        const li = document.createElement('li');
        const tipoLbl = item.tipo === 'det' ? 'Det.' : 'Perfil';
        const left = document.createElement('span');
        left.innerHTML = `<span class="tag">${tipoLbl}</span> <strong>${esc(item.codigo)}</strong> &middot; ${esc(item.nombre)}`;
        li.appendChild(left);

        const btn = document.createElement('button');
        btn.type = 'button';
        btn.textContent = 'Quitar';
        btn.setAttribute('aria-label', `Quitar ${item.nombre}`);
        btn.addEventListener('click', () => quitar(key));
        li.appendChild(btn);

        $listaSel.appendChild(li);
    }
}

function placeholder(text) {
    const li = document.createElement('li');
    li.className = 'placeholder';
    li.textContent = text;
    return li;
}

async function onSubmit(e) {
    e.preventDefault();
    hideMsg();

    if (state.seleccionados.size === 0) {
        showError('Tenes que seleccionar al menos una determinacion o perfil');
        return;
    }

    const fd = new FormData($form);
    const pacienteIdRaw = String(fd.get('paciente_id') || '').trim();
    const payload = {
        paciente_id: pacienteIdRaw === '' ? null : parseInt(pacienteIdRaw, 10),
        snapshot_paciente: {
            nombre: String(fd.get('snap_nombre') || '').trim(),
            dni: String(fd.get('snap_dni') || '').trim() || null,
            sexo: fd.get('snap_sexo'),
            fecha_nac: (String(fd.get('snap_fecha_nac') || '').trim() || null),
        },
        medico_externo: String(fd.get('medico_externo') || '').trim() || null,
        diagnostico: String(fd.get('diagnostico') || '').trim() || null,
        prioridad: fd.get('prioridad') || 'rutina',
        observaciones: String(fd.get('observaciones') || '').trim() || null,
        items: Array.from(state.seleccionados.values()).map((s) =>
            s.tipo === 'det' ? { determinacion_id: s.id } : { perfil_id: s.id }
        ),
    };

    $btn.disabled = true;
    $btn.textContent = 'Creando...';

    try {
        const r = await api.post('/api/pedidos', payload);
        showSuccess(`Pedido <strong>${esc(r.numero)}</strong> creado correctamente (id ${r.id}, ${r.items_count} items).`);
        $form.reset();
        state.seleccionados.clear();
        limpiarPaciente();
        renderListas();
    } catch (e) {
        showFieldErrors(e);
    } finally {
        $btn.disabled = false;
        $btn.textContent = 'Crear pedido';
    }
}

function showFieldErrors(err) {
    const fields = err.fields || {};
    const msgEl = document.createElement('div');
    msgEl.innerHTML = `<strong>Error:</strong> ${esc(err.message)}`;

    const keys = Object.keys(fields);
    if (keys.length > 0) {
        const ul = document.createElement('ul');
        for (const k of keys) {
            const li = document.createElement('li');
            li.textContent = `${k}: ${fields[k]}`;
            ul.appendChild(li);
        }
        msgEl.appendChild(ul);
    }

    $msg.hidden = false;
    $msg.className = 'mensaje error';
    $msg.innerHTML = '';
    $msg.appendChild(msgEl);
}

function showError(msg) {
    $msg.hidden = false;
    $msg.className = 'mensaje error';
    $msg.innerHTML = `<strong>Error:</strong> ${esc(msg)}`;
}

function showSuccess(html) {
    $msg.hidden = false;
    $msg.className = 'mensaje exito';
    $msg.innerHTML = `<strong>OK:</strong> ${html}`;
}

function hideMsg() {
    $msg.hidden = true;
    $msg.innerHTML = '';
}

function esc(s) {
    return String(s ?? '').replace(/[<>&"']/g, (c) => ({
        '<': '&lt;',
        '>': '&gt;',
        '&': '&amp;',
        '"': '&quot;',
        "'": '&#39;',
    }[c]));
}

init();
