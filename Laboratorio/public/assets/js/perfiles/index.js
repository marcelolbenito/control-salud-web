import { api } from '../api.js?v=14';

const $ = (id) => document.getElementById(id);
const $msg = $('mensaje');
const $editor = $('editor');
const $tbody = $('perfiles-tbody');
const $listaDet = $('lista-det');
const $listaSel = $('lista-sel');
const $buscarDet = $('buscar-det');

const state = {
    determinaciones: [],   // catalogo completo
    perfiles: [],
    seleccionadas: new Map(), // id -> {id, codigo, nombre}
};

const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
}[c]));

function showMsg(text, tipo = 'exito') {
    $msg.className = `mensaje ${tipo}`;
    $msg.textContent = text;
    $msg.hidden = false;
    setTimeout(() => { $msg.hidden = true; }, 3500);
}

async function init() {
    try {
        const [det, perf] = await Promise.all([
            api.get('/api/determinaciones'),
            api.get('/api/perfiles'),
        ]);
        state.determinaciones = det?.determinaciones ?? [];
        state.perfiles = perf?.perfiles ?? [];
        renderPerfiles();
    } catch (e) {
        $tbody.innerHTML = `<tr><td colspan="5" class="muted">Error al cargar: ${esc(e.message)}</td></tr>`;
    }

    $('btn-nuevo').addEventListener('click', abrirNuevo);
    $('btn-cancelar').addEventListener('click', cerrarEditor);
    $('btn-guardar').addEventListener('click', guardar);
    $buscarDet.addEventListener('input', renderDisponibles);

    // El campo NBU solo aplica cuando el perfil se factura todo junto.
    $('f-fact').addEventListener('change', () => {
        $('f-nbu-wrap').hidden = $('f-fact').value !== 'junto';
    });
}

function renderPerfiles() {
    if (state.perfiles.length === 0) {
        $tbody.innerHTML = `<tr><td colspan="5" class="muted" style="text-align:center;">Sin perfiles. Creá el primero.</td></tr>`;
        return;
    }
    $tbody.innerHTML = state.perfiles.map((p) => {
        const dets = (p.determinaciones || []).map((d) => esc(d.codigo || d.nombre)).join(' · ');
        const fact = p.nbu_unidades != null
            ? `<span class="tag" title="Una sola línea en el lote con NBU ${esc(String(parseFloat(p.nbu_unidades)))}">Junto (${esc(String(parseFloat(p.nbu_unidades)))} NBU)</span>`
            : '<span class="muted">Separado</span>';
        return `
        <tr>
            <td><strong>${esc(p.codigo)}</strong></td>
            <td>${esc(p.nombre)}</td>
            <td class="muted" style="font-size:.82rem;">${dets || '(sin determinaciones)'}</td>
            <td>${fact}</td>
            <td><button type="button" class="btn btn-ghost btn-sm" data-accion="editar" data-id="${p.id}"><i class="bi bi-pencil"></i> Editar</button></td>
        </tr>`;
    }).join('');

    $tbody.querySelectorAll('button[data-accion="editar"]').forEach((b) => {
        b.addEventListener('click', () => abrirEditar(parseInt(b.dataset.id, 10)));
    });
}

function abrirNuevo() {
    $('editor-titulo').textContent = 'Nuevo perfil';
    $('f-id').value = '';
    $('f-codigo').value = '';
    $('f-nombre').value = '';
    $('f-desc').value = '';
    $('f-fact').value = 'separado';
    $('f-nbu').value = '';
    $('f-nbu-wrap').hidden = true;
    state.seleccionadas.clear();
    $buscarDet.value = '';
    renderDisponibles();
    renderSeleccionadas();
    $editor.hidden = false;
    $editor.scrollIntoView({ behavior: 'smooth', block: 'start' });
    $('f-codigo').focus();
}

function abrirEditar(id) {
    const p = state.perfiles.find((x) => x.id === id);
    if (!p) return;
    $('editor-titulo').textContent = `Editar perfil · ${p.codigo}`;
    $('f-id').value = String(p.id);
    $('f-codigo').value = p.codigo ?? '';
    $('f-nombre').value = p.nombre ?? '';
    $('f-desc').value = p.descripcion ?? '';
    const junto = p.nbu_unidades != null;
    $('f-fact').value = junto ? 'junto' : 'separado';
    $('f-nbu').value = junto ? String(parseFloat(p.nbu_unidades)) : '';
    $('f-nbu-wrap').hidden = !junto;
    state.seleccionadas.clear();
    for (const d of (p.determinaciones || [])) {
        state.seleccionadas.set(d.id, { id: d.id, codigo: d.codigo, nombre: d.nombre });
    }
    $buscarDet.value = '';
    renderDisponibles();
    renderSeleccionadas();
    $editor.hidden = false;
    $editor.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function cerrarEditor() {
    $editor.hidden = true;
}

function renderDisponibles() {
    const q = ($buscarDet.value || '').toLowerCase().trim();
    const filtradas = state.determinaciones.filter((d) =>
        !q || (d.nombre || '').toLowerCase().includes(q) || (d.codigo || '').toLowerCase().includes(q)
    ).slice(0, 200);
    if (filtradas.length === 0) {
        $listaDet.innerHTML = '<li class="placeholder">Sin resultados</li>';
        return;
    }
    $listaDet.innerHTML = '';
    for (const d of filtradas) {
        const li = document.createElement('li');
        li.innerHTML = `<span><strong>${esc(d.codigo)}</strong> · ${esc(d.nombre)}</span><span class="tag">${esc(d.area_codigo ?? '')}</span>`;
        if (state.seleccionadas.has(d.id)) {
            li.setAttribute('aria-disabled', 'true');
        } else {
            li.addEventListener('click', () => {
                state.seleccionadas.set(d.id, { id: d.id, codigo: d.codigo, nombre: d.nombre });
                renderDisponibles();
                renderSeleccionadas();
            });
        }
        $listaDet.appendChild(li);
    }
}

function renderSeleccionadas() {
    $('sel-count').textContent = String(state.seleccionadas.size);
    if (state.seleccionadas.size === 0) {
        $listaSel.innerHTML = '<li class="placeholder">(sin determinaciones)</li>';
        return;
    }
    $listaSel.innerHTML = '';
    for (const d of state.seleccionadas.values()) {
        const li = document.createElement('li');
        const left = document.createElement('span');
        left.innerHTML = `<strong>${esc(d.codigo)}</strong> · ${esc(d.nombre)}`;
        li.appendChild(left);
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.textContent = 'Quitar';
        btn.addEventListener('click', () => {
            state.seleccionadas.delete(d.id);
            renderDisponibles();
            renderSeleccionadas();
        });
        li.appendChild(btn);
        $listaSel.appendChild(li);
    }
}

async function guardar() {
    const id = ($('f-id').value || '').trim();
    const junto = $('f-fact').value === 'junto';
    const nbu = ($('f-nbu').value || '').trim();
    if (junto && (nbu === '' || !(parseFloat(nbu) > 0))) {
        showMsg('Si el perfil se factura todo junto, cargá las unidades NBU del perfil.', 'error');
        return;
    }
    const payload = {
        codigo: ($('f-codigo').value || '').trim(),
        nombre: ($('f-nombre').value || '').trim(),
        descripcion: ($('f-desc').value || '').trim(),
        nbu_unidades: junto ? nbu : null,
        determinaciones: Array.from(state.seleccionadas.keys()),
    };
    const accion = id ? 'actualizar' : 'crear';
    if (accion === 'actualizar') payload.id = parseInt(id, 10);

    try {
        const r = await api.post(`/api/perfiles?accion=${accion}`, payload);
        showMsg(`Perfil ${esc(r.codigo)} ${accion === 'crear' ? 'creado' : 'actualizado'} (${r.items} determinaciones).`, 'exito');
        cerrarEditor();
        const perf = await api.get('/api/perfiles');
        state.perfiles = perf?.perfiles ?? [];
        renderPerfiles();
    } catch (e) {
        const det = e.fields ? ' ' + Object.values(e.fields).join(' · ') : '';
        showMsg(`Error: ${e.message}${det}`, 'error');
    }
}

init();
