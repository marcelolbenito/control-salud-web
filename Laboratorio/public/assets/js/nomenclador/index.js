import { api } from '../api.js?v=14';

const $ = (id) => document.getElementById(id);
const $msg = $('mensaje');
const $overlay = $('editor-overlay');
const $rgOverlay = $('rangos-overlay');
const $tbody = $('det-tbody');
const $buscar = $('buscar');

const state = { determinaciones: [], areas: [], rangoDetId: null };

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
        const [det, areas] = await Promise.all([
            api.get('/api/determinaciones?accion=abm'),
            api.get('/api/determinaciones?accion=areas'),
        ]);
        state.determinaciones = det?.determinaciones ?? [];
        state.areas = areas?.areas ?? [];
        render();
    } catch (e) {
        $tbody.innerHTML = `<tr><td colspan="6" class="muted">Error al cargar: ${esc(e.message)}</td></tr>`;
    }

    $('btn-nuevo').addEventListener('click', abrirNuevo);
    $('btn-cancelar').addEventListener('click', cerrarEditor);
    $('btn-cerrar').addEventListener('click', cerrarEditor);
    $('btn-guardar').addEventListener('click', guardar);
    $buscar.addEventListener('input', render);

    // Cerrar al hacer clic en el fondo oscuro o con la tecla Escape
    $overlay.addEventListener('click', (e) => { if (e.target === $overlay) cerrarEditor(); });

    // Modal de rangos (valores de referencia)
    $('rg-cerrar').addEventListener('click', cerrarRangos);
    $('rg-nuevo').addEventListener('click', rgNuevo);
    $('rg-guardar').addEventListener('click', rgGuardar);
    $('rg-cancelar').addEventListener('click', () => { $('rg-form').hidden = true; });
    $rgOverlay.addEventListener('click', (e) => { if (e.target === $rgOverlay) cerrarRangos(); });

    document.addEventListener('keydown', (e) => {
        if (e.key !== 'Escape') return;
        if (!$rgOverlay.hidden) cerrarRangos();
        else if (!$overlay.hidden) cerrarEditor();
    });
}

function abrirEditor() {
    $overlay.hidden = false;
}

function cerrarEditor() {
    $overlay.hidden = true;
}

// El area ya no se elige en el formulario: se mantiene la existente al editar
// y para determinaciones nuevas se usa un area por defecto (la primera).
function areaDefault() {
    return state.areas.length > 0 ? state.areas[0].id : 0;
}

function fmtNbu(v) {
    if (v === null || v === undefined || v === '') return '<span class="muted">—</span>';
    return String(parseFloat(v)).replace('.', ',');
}

function render() {
    const q = ($buscar.value || '').toLowerCase().trim();
    const filtradas = state.determinaciones.filter((d) =>
        !q || (d.nombre || '').toLowerCase().includes(q) || (d.codigo || '').toLowerCase().includes(q)
    );
    if (filtradas.length === 0) {
        $tbody.innerHTML = '<tr><td colspan="6" class="muted" style="text-align:center;">Sin resultados.</td></tr>';
        return;
    }
    $tbody.innerHTML = filtradas.map((d) => `
        <tr>
            <td><strong>${esc(d.codigo)}</strong></td>
            <td>${esc(d.nombre)}</td>
            <td class="muted">${esc(d.area_codigo || '')}</td>
            <td>${esc(d.unidad || '') || '<span class="muted">—</span>'}</td>
            <td>${fmtNbu(d.nbu_unidades)}</td>
            <td style="white-space:nowrap;">
                <button type="button" class="btn btn-ghost btn-sm" data-edit="${d.id}"><i class="bi bi-pencil"></i> Editar</button>
                <button type="button" class="btn btn-ghost btn-sm" data-del="${d.id}" data-nombre="${esc(d.nombre)}" title="Eliminar esta determinación del nomenclador"><i class="bi bi-trash"></i> Eliminar</button>
            </td>
        </tr>`).join('');

    $tbody.querySelectorAll('button[data-edit]').forEach((b) => {
        b.addEventListener('click', () => abrirEditar(parseInt(b.dataset.edit, 10)));
    });
    $tbody.querySelectorAll('button[data-del]').forEach((b) => {
        b.addEventListener('click', () => eliminarDet(parseInt(b.dataset.del, 10), b.dataset.nombre));
    });
}

// Eliminar (soft delete): sale del nomenclador y de los buscadores; los
// pedidos ya cargados con este analisis no se modifican.
async function eliminarDet(id, nombre) {
    if (!confirm(`¿Eliminar "${nombre}" del nomenclador?\nLos pedidos ya cargados con este análisis no se modifican.`)) return;
    try {
        await api.post(`/api/determinaciones?accion=eliminar&id=${id}`, {});
        showMsg(`Determinación "${nombre}" eliminada.`, 'exito');
        const det = await api.get('/api/determinaciones?accion=abm');
        state.determinaciones = det?.determinaciones ?? [];
        render();
    } catch (e) {
        showMsg(`Error: ${e.message}`, 'error');
    }
}

function setForm(d) {
    $('f-id').value = d.id ?? '';
    $('f-area').value = d.area_id != null ? String(d.area_id) : String(areaDefault());
    $('f-codigo').value = d.codigo ?? '';
    $('f-nombre').value = d.nombre ?? '';
    $('f-corto').value = d.nombre_corto ?? '';
    $('f-unidad').value = d.unidad ?? '';
    $('f-metodo').value = d.metodo ?? '';
    $('f-tipo').value = d.tipo_resultado ?? 'numerico';
    $('f-decimales').value = d.decimales ?? 2;
    $('f-precio').value = d.precio ?? '';
    $('f-nbu').value = d.nbu_unidades ?? '';
    $('f-solo-facturacion').checked = Number(d.solo_facturacion) === 1;
    $('f-valref').value = '';
    $('f-valref-rango-id').value = '';
}

function abrirNuevo() {
    $('editor-titulo').textContent = 'Nueva determinación';
    setForm({ decimales: 2, tipo_resultado: 'numerico' });
    abrirEditor();
    $('f-codigo').focus();
}

// Rango "general" de una determinacion: sin sexo especifico, sin limites de
// edad y sin min/max numericos. Es el que refleja el campo de texto del editor.
function esRangoGeneral(r) {
    return (r.sexo === 'ambos' || !r.sexo)
        && r.edad_min_dias == null && r.edad_max_dias == null
        && r.valor_min == null && r.valor_max == null;
}

async function abrirEditar(id) {
    try {
        const d = await api.get(`/api/determinaciones?accion=obtener&id=${id}`);
        $('editor-titulo').textContent = `Editar · ${d.codigo}`;
        setForm(d);
        // Precargar el texto de referencia general (si existe).
        try {
            const data = await api.get(`/api/valores-referencia?accion=listar&determinacion_id=${id}`);
            const general = (data?.rangos ?? []).find(esRangoGeneral);
            if (general) {
                $('f-valref').value = general.texto_referencia ?? '';
                $('f-valref-rango-id').value = String(general.id);
            }
        } catch {
            // sin rangos: el campo queda vacio
        }
        abrirEditor();
    } catch (e) {
        showMsg(`Error: ${e.message}`, 'error');
    }
}

// Sincroniza el texto del editor con el rango general de valores de referencia.
async function guardarValRef(detId) {
    const texto = ($('f-valref').value || '').trim();
    const rangoId = ($('f-valref-rango-id').value || '').trim();
    if (texto === '' && rangoId === '') return;
    if (texto === '' && rangoId !== '') {
        await api.post(`/api/valores-referencia?accion=eliminar&id=${rangoId}`, {});
        return;
    }
    const payload = {
        determinacion_id: detId,
        sexo: 'ambos',
        edad_min_dias: null,
        edad_max_dias: null,
        valor_min: '',
        valor_max: '',
        texto_referencia: texto,
        observaciones: '',
    };
    if (rangoId !== '') {
        payload.id = parseInt(rangoId, 10);
        await api.post('/api/valores-referencia?accion=actualizar', payload);
    } else {
        await api.post('/api/valores-referencia?accion=crear', payload);
    }
}

async function guardar() {
    const id = ($('f-id').value || '').trim();
    const payload = {
        area_id: parseInt($('f-area').value, 10) || 0,
        codigo: ($('f-codigo').value || '').trim(),
        nombre: ($('f-nombre').value || '').trim(),
        nombre_corto: ($('f-corto').value || '').trim(),
        unidad: ($('f-unidad').value || '').trim(),
        metodo: ($('f-metodo').value || '').trim(),
        tipo_resultado: $('f-tipo').value,
        decimales: parseInt($('f-decimales').value, 10),
        precio: ($('f-precio').value || '').trim(),
        nbu_unidades: ($('f-nbu').value || '').trim(),
        solo_facturacion: $('f-solo-facturacion').checked,
    };
    const accion = id ? 'actualizar' : 'crear';
    if (accion === 'actualizar') payload.id = parseInt(id, 10);

    try {
        const r = await api.post(`/api/determinaciones?accion=${accion}`, payload);
        try {
            await guardarValRef(r.id);
        } catch (e) {
            showMsg(`Determinación guardada, pero fallo el valor de referencia: ${e.message}`, 'error');
        }
        showMsg(`Determinación ${esc(r.codigo)} ${accion === 'crear' ? 'creada' : 'actualizada'}.`, 'exito');
        cerrarEditor();
        const det = await api.get('/api/determinaciones?accion=abm');
        state.determinaciones = det?.determinaciones ?? [];
        render();
    } catch (e) {
        const det = e.fields ? ' ' + Object.values(e.fields).join(' · ') : '';
        showMsg(`Error: ${e.message}${det}`, 'error');
    }
}

/* ---------- Valores de referencia (rangos) ---------- */

const ANIO_DIAS = 365;
const MES_DIAS = 30;

function diasToAniosMeses(dias) {
    if (dias === null || dias === undefined || dias === '') return { anios: '', meses: '' };
    const d = parseInt(dias, 10);
    return { anios: Math.floor(d / ANIO_DIAS), meses: Math.floor((d % ANIO_DIAS) / MES_DIAS) };
}

function aniosMesesToDias(aniosVal, mesesVal) {
    const a = aniosVal === '' ? null : parseInt(aniosVal, 10);
    const m = mesesVal === '' ? null : parseInt(mesesVal, 10);
    if (a === null && m === null) return null;
    return (a || 0) * ANIO_DIAS + (m || 0) * MES_DIAS;
}

function fmtEdadCelda(min, max) {
    const f = (dias) => {
        const { anios, meses } = diasToAniosMeses(dias);
        const partes = [];
        if (anios) partes.push(`${anios}a`);
        if (meses) partes.push(`${meses}m`);
        return partes.length ? partes.join(' ') : '0';
    };
    if (min === null && max === null) return '<span class="muted">cualquiera</span>';
    if (min !== null && max !== null) return `${f(min)} – ${f(max)}`;
    if (min !== null) return `desde ${f(min)}`;
    return `hasta ${f(max)}`;
}

function fmtRangoCelda(r) {
    const texto = (r.texto_referencia || '').trim();
    const min = r.valor_min, max = r.valor_max;
    const num = (v) => String(parseFloat(v)).replace('.', ',');
    let rango = '';
    if (min != null && max != null) rango = `${num(min)} a ${num(max)}`;
    else if (min != null) rango = `≥ ${num(min)}`;
    else if (max != null) rango = `≤ ${num(max)}`;
    if (rango && texto) return `${esc(rango)} · ${esc(texto)}`;
    return esc(rango || texto) || '<span class="muted">—</span>';
}

const sexoLabel = (s) => ({ M: 'Masculino', F: 'Femenino', ambos: 'Ambos' }[s] || s);

async function abrirRangos(detId, nombre) {
    state.rangoDetId = detId;
    $('rg-titulo').textContent = `Valores de referencia · ${nombre}`;
    $('rg-form').hidden = true;
    $rgOverlay.hidden = false;
    await cargarRangos();
}

function cerrarRangos() {
    $rgOverlay.hidden = true;
    state.rangoDetId = null;
}

async function cargarRangos() {
    const $b = $('rg-tbody');
    try {
        const data = await api.get(`/api/valores-referencia?accion=listar&determinacion_id=${state.rangoDetId}`);
        const rangos = data?.rangos ?? [];
        if (rangos.length === 0) {
            $b.innerHTML = '<tr><td colspan="4" class="muted" style="text-align:center;">Sin rangos cargados.</td></tr>';
            return;
        }
        $b.innerHTML = rangos.map((r) => `
            <tr>
                <td>${esc(sexoLabel(r.sexo))}</td>
                <td>${fmtEdadCelda(r.edad_min_dias, r.edad_max_dias)}</td>
                <td>${fmtRangoCelda(r)}</td>
                <td style="white-space:nowrap;">
                    <button type="button" class="btn btn-ghost btn-sm" data-rg-edit='${esc(JSON.stringify(r))}'><i class="bi bi-pencil"></i></button>
                    <button type="button" class="btn btn-ghost btn-sm" data-rg-del="${r.id}"><i class="bi bi-trash"></i></button>
                </td>
            </tr>`).join('');
        $b.querySelectorAll('button[data-rg-edit]').forEach((btn) => {
            btn.addEventListener('click', () => rgEditar(JSON.parse(btn.dataset.rgEdit)));
        });
        $b.querySelectorAll('button[data-rg-del]').forEach((btn) => {
            btn.addEventListener('click', () => rgEliminar(parseInt(btn.dataset.rgDel, 10)));
        });
    } catch (e) {
        $b.innerHTML = `<tr><td colspan="4" class="muted">Error: ${esc(e.message)}</td></tr>`;
    }
}

function rgSetForm(r) {
    $('rg-id').value = r.id ?? '';
    $('rg-sexo').value = r.sexo ?? 'ambos';
    const min = diasToAniosMeses(r.edad_min_dias ?? '');
    const max = diasToAniosMeses(r.edad_max_dias ?? '');
    $('rg-edad-min-anios').value = min.anios;
    $('rg-edad-min-meses').value = min.meses;
    $('rg-edad-max-anios').value = max.anios;
    $('rg-edad-max-meses').value = max.meses;
    $('rg-valor-min').value = r.valor_min ?? '';
    $('rg-valor-max').value = r.valor_max ?? '';
    $('rg-texto').value = r.texto_referencia ?? '';
    $('rg-obs').value = r.observaciones ?? '';
}

function rgNuevo() {
    $('rg-form-titulo').textContent = 'Nuevo rango';
    rgSetForm({ sexo: 'ambos' });
    $('rg-form').hidden = false;
}

function rgEditar(r) {
    $('rg-form-titulo').textContent = 'Editar rango';
    rgSetForm(r);
    $('rg-form').hidden = false;
}

async function rgGuardar() {
    const id = ($('rg-id').value || '').trim();
    const payload = {
        determinacion_id: state.rangoDetId,
        sexo: $('rg-sexo').value,
        edad_min_dias: aniosMesesToDias($('rg-edad-min-anios').value, $('rg-edad-min-meses').value),
        edad_max_dias: aniosMesesToDias($('rg-edad-max-anios').value, $('rg-edad-max-meses').value),
        valor_min: ($('rg-valor-min').value || '').trim(),
        valor_max: ($('rg-valor-max').value || '').trim(),
        texto_referencia: ($('rg-texto').value || '').trim(),
        observaciones: ($('rg-obs').value || '').trim(),
    };
    const accion = id ? 'actualizar' : 'crear';
    if (accion === 'actualizar') payload.id = parseInt(id, 10);

    try {
        await api.post(`/api/valores-referencia?accion=${accion}`, payload);
        showMsg(`Rango ${accion === 'crear' ? 'creado' : 'actualizado'}.`, 'exito');
        $('rg-form').hidden = true;
        await cargarRangos();
    } catch (e) {
        const campos = e.fields ? ' ' + Object.values(e.fields).join(' · ') : '';
        showMsg(`Error: ${e.message}${campos}`, 'error');
    }
}

async function rgEliminar(id) {
    if (!confirm('¿Eliminar este rango de referencia?')) return;
    try {
        await api.post(`/api/valores-referencia?accion=eliminar&id=${id}`, {});
        showMsg('Rango eliminado.', 'exito');
        await cargarRangos();
    } catch (e) {
        showMsg(`Error: ${e.message}`, 'error');
    }
}

init();
