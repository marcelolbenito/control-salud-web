import { api, labPath } from '../api.js?v=14';
import { PacienteSelector } from '../pacientes/paciente-selector.js?v=14';

// Historial unificado: UNA sola lista con los informes emitidos y los pedidos
// que todavia no tienen informe, con un buscador comun (paciente, DNI o numero).
// Los informes salen de /api/informes?accion=recientes y los pedidos sin
// informar de ?accion=pendientes; se mezclan y ordenan por fecha.

const $ = (id) => document.getElementById(id);
const $msg = $('mensaje');
const $lista = $('lista');
const $infoTotal = $('info-total');
const $dossierWrap = $('dossier-wrap');
const $dossier = $('dossier');
const $buscar = $('buscar');
const $pacRes = $('pac-resultados');

const state = {
    paciente: null, // { id, dni, nombre, telefono } si se eligio uno del dropdown
};

const esc = (s) => String(s ?? '').replace(/[<>&"']/g, (c) => ({
    '<': '&lt;', '>': '&gt;', '&': '&amp;', '"': '&quot;', "'": '&#39;',
}[c]));

const badge = (estado) => `<span class="badge badge-${esc(estado)}">${esc(String(estado).replace('_', ' '))}</span>`;

function showMsg(text, tipo = 'exito') {
    $msg.className = `mensaje ${tipo}`;
    $msg.textContent = text;
    $msg.hidden = false;
}

// Fecha corta en una sola linea: dd/mm/aa hh:mm
function fmtFecha(s) {
    if (!s) return '';
    const d = new Date(String(s).replace(' ', 'T'));
    if (Number.isNaN(d.getTime())) return String(s);
    return d.toLocaleString('es-AR', { day: '2-digit', month: '2-digit', year: '2-digit', hour: '2-digit', minute: '2-digit' });
}

// Solo la parte de fecha, para comparar contra los filtros desde/hasta.
const soloFecha = (s) => String(s ?? '').slice(0, 10);

// El snapshot del pedido puede traer el string "null" (JSON_EXTRACT sobre una
// clave vacia); en pantalla eso tiene que verse como un guion.
const oGuion = (v) => {
    const s = String(v ?? '').trim();
    return (s === '' || s === 'null') ? '—' : s;
};

function waHref(tel, nombre) {
    const digits = String(tel ?? '').replace(/\D/g, '');
    const msg = `Hola${nombre ? ' ' + nombre : ''}! Le avisamos del laboratorio que ya están listos los resultados de sus análisis. Puede pasar a retirarlos. ¡Gracias!`;
    return digits
        ? `https://wa.me/${digits}?text=${encodeURIComponent(msg)}`
        : `https://wa.me/?text=${encodeURIComponent(msg)}`;
}

// Acciones compactas de un informe: solo iconos con tooltip.
function accionesInforme(id, pedidoId, tel, nombre) {
    return `
        <a class="btn btn-primary btn-sm" href="${labPath(`/api/informes?id=${id}&download=1`)}" target="_blank" title="Ver / imprimir el PDF"><i class="bi bi-printer"></i></a>
        <button type="button" class="btn btn-ghost btn-sm" data-accion="subir-red" data-id="${id}" title="Subir a la red"><i class="bi bi-cloud-arrow-up"></i></button>
        <a class="btn btn-ghost btn-sm" href="${waHref(tel, nombre)}" target="_blank" title="Avisar por WhatsApp que están los resultados"><i class="bi bi-whatsapp"></i></a>
        ${pedidoId ? `<a class="btn btn-ghost btn-sm" href="${labPath(`/resultados/cargar?pedido_id=${esc(String(pedidoId))}`)}" title="Editar los resultados del pedido"><i class="bi bi-pencil-square"></i></a>` : ''}
    `;
}

// Subir a la red (delegado a nivel documento: sirve para todas las tablas).
document.addEventListener('click', async (e) => {
    const btn = e.target.closest('button[data-accion="subir-red"]');
    if (!btn) return;
    btn.disabled = true;
    try {
        const r = await api.post(`/api/informes?accion=subir-red&id=${btn.dataset.id}`, {});
        showMsg(`Informe subido a la red: ${r.destino}`, 'exito');
    } catch (err) {
        showMsg(`No se pudo subir a la red: ${err.message}`, 'error');
    } finally {
        btn.disabled = false;
    }
});

/* ================= Buscador de paciente (sugerencias) ================= */

let pacDebounce = null;
let pacBusqueda = 0;

function ocultarPacDropdown() { $pacRes.hidden = true; $pacRes.innerHTML = ''; }

async function sugerirPacientes(q) {
    const nro = ++pacBusqueda;
    const filtro = /^\d+$/.test(q) ? `dni=${encodeURIComponent(q)}` : `apellido=${encodeURIComponent(q)}`;
    try {
        const r = await api.get(`/api/pacientes?accion=buscar&${filtro}&limite=8`);
        if (nro !== pacBusqueda) return;
        const pacientes = r?.pacientes ?? [];
        if (pacientes.length === 0) { ocultarPacDropdown(); return; }
        $pacRes.innerHTML = pacientes.map((p) => `
            <li data-pac='${esc(JSON.stringify({ id: p.id, dni: p.dni, nombre: `${p.apellido ?? ''}, ${p.nombres ?? ''}`, telefono: p.telefono ?? '' }))}'>
                <span><strong>${esc(p.apellido)}, ${esc(p.nombres)}</strong> · DNI ${esc(p.dni ?? '—')}</span>
                <span class="muted">HC ${esc(p.nro_hc ?? '—')}</span>
            </li>`).join('');
        $pacRes.hidden = false;
    } catch {
        if (nro !== pacBusqueda) return;
        ocultarPacDropdown();
    }
}

$buscar.addEventListener('input', () => {
    const q = $buscar.value.trim();
    state.paciente = null;
    clearTimeout(pacDebounce);
    if (q.length < 2) { ocultarPacDropdown(); cargar(); return; }
    pacDebounce = setTimeout(() => { sugerirPacientes(q); cargar(); }, 300);
});

$buscar.addEventListener('keydown', (e) => {
    if (e.key !== 'Enter') return;
    e.preventDefault();
    ocultarPacDropdown();
    cargar();
});

$pacRes.addEventListener('click', (e) => {
    const li = e.target.closest('li[data-pac]');
    if (!li) return;
    ocultarPacDropdown();
    aplicarPaciente(JSON.parse(li.dataset.pac));
});

document.addEventListener('click', (e) => {
    if (!$pacRes.contains(e.target) && e.target !== $buscar) ocultarPacDropdown();
});

$('btn-modal-paciente').addEventListener('click', async () => {
    const p = await PacienteSelector.elegir({ titulo: 'Elegir paciente' });
    if (p) {
        aplicarPaciente({
            id: p.id,
            dni: p.dni,
            nombre: `${p.apellido ?? ''}, ${p.nombres ?? ''}`,
            telefono: p.telefono ?? '',
        });
    }
});

// Al elegir un paciente del dropdown se filtra por su DNI (asi entran tambien
// los pedidos cargados a mano, que no tienen paciente_id pero si el dni).
function aplicarPaciente(p) {
    state.paciente = p;
    $buscar.value = p.dni || p.nombre;
    cargar();
}

$('btn-limpiar').addEventListener('click', () => {
    state.paciente = null;
    $buscar.value = '';
    $('f-estado').value = '';
    $('f-desde').value = '';
    $('f-hasta').value = '';
    $dossierWrap.hidden = true;
    cargar();
});

$('f-estado').addEventListener('change', cargar);
$('f-desde').addEventListener('change', cargar);
$('f-hasta').addEventListener('change', cargar);

/* ================= Carga unificada ================= */

function leerFiltros() {
    return {
        texto: (state.paciente?.dni || $buscar.value || '').trim(),
        estado: $('f-estado').value,
        desde: $('f-desde').value,
        hasta: $('f-hasta').value,
    };
}

// El endpoint de informes filtra por columna, asi que hay que decidir en cual
// buscar: digitos largos = DNI, con letras o guiones = numero de informe o
// nombre. El de pendientes acepta un unico "q" que busca en las tres.
function filtrosInformes(f) {
    const p = {};
    if (f.texto !== '') {
        if (/^\d{6,12}$/.test(f.texto)) p.dni = f.texto;
        else if (/^[\d-]+$/i.test(f.texto) || /^i-/i.test(f.texto)) p.numero = f.texto;
        else p.paciente = f.texto;
    }
    if (f.desde !== '') p.desde = f.desde;
    if (f.hasta !== '') p.hasta = f.hasta;
    if (f.estado === '0' || f.estado === '1') p.entregado = f.estado;
    return p;
}

async function traerInformes(f) {
    const qs = new URLSearchParams({ accion: 'recientes', ...filtrosInformes(f) });
    const r = await api.get(`/api/informes?${qs.toString()}`);
    return (r.informes || []).map((i) => ({
        tipo: 'informe',
        fecha: i.fecha_emision,
        pedidoId: i.pedido_id,
        pedidoNumero: i.pedido_numero,
        paciente: i.paciente_nombre,
        dni: i.paciente_dni,
        telefono: i.paciente_telefono,
        informeId: i.id,
        informeNumero: i.numero,
        esParcial: parseInt(i.es_parcial, 10) === 1,
        entregado: parseInt(i.entregado, 10) === 1,
    }));
}

async function traerPendientes(f) {
    const params = new URLSearchParams({ accion: 'pendientes' });
    if (f.texto !== '') params.set('q', f.texto);
    // El endpoint solo filtra por un dia puntual; el rango se recorta despues.
    if (f.desde !== '' && f.desde === f.hasta) params.set('dia', f.desde);
    const r = await api.get(`/api/informes?${params.toString()}`);
    return (r.pedidos || []).map((p) => ({
        tipo: 'pendiente',
        fecha: p.fecha_solicitud,
        pedidoId: p.id,
        pedidoNumero: p.numero,
        paciente: p.paciente_nombre,
        dni: p.paciente_dni,
        telefono: null,
        estadoPedido: p.estado,
        tieneParcial: parseInt(p.tiene_parcial, 10) === 1,
        cargados: parseInt(p.resultados_cargados, 10) || 0,
        clinicos: parseInt(p.items_clinicos, 10) || 0,
    }));
}

async function cargar() {
    const f = leerFiltros();
    const pideInformes = f.estado !== 'sin_informe';
    const pidePendientes = f.estado === '' || f.estado === 'sin_informe';

    $lista.innerHTML = '<tr><td colspan="7" class="muted" style="text-align:center;">Buscando…</td></tr>';
    try {
        const [informes, pendientes] = await Promise.all([
            pideInformes ? traerInformes(f) : Promise.resolve([]),
            pidePendientes ? traerPendientes(f) : Promise.resolve([]),
        ]);

        let filas = [...informes, ...pendientes];
        // Rango de fechas sobre los pendientes (el endpoint solo hace un dia).
        if (f.desde !== '' || f.hasta !== '') {
            filas = filas.filter((x) => {
                if (x.tipo !== 'pendiente') return true;
                const d = soloFecha(x.fecha);
                if (f.desde !== '' && d < f.desde) return false;
                if (f.hasta !== '' && d > f.hasta) return false;
                return true;
            });
        }
        filas.sort((a, b) => String(b.fecha ?? '').localeCompare(String(a.fecha ?? '')));

        render(filas);
        const sinInf = filas.filter((x) => x.tipo === 'pendiente').length;
        const conInf = filas.length - sinInf;
        $infoTotal.textContent = filas.length === 0
            ? 'Sin resultados.'
            : `${filas.length} resultado(s): ${conInf} informe(s) emitido(s) y ${sinInf} pedido(s) sin informar.`;
    } catch (e) {
        $lista.innerHTML = `<tr><td colspan="7" class="muted">Error: ${esc(e.message)}</td></tr>`;
        $infoTotal.textContent = '';
    }
}

function render(filas) {
    if (filas.length === 0) {
        $lista.innerHTML = '<tr><td colspan="7" class="muted" style="text-align:center;">Sin resultados</td></tr>';
        return;
    }
    $lista.innerHTML = filas.map((x) => x.tipo === 'informe' ? filaInforme(x) : filaPendiente(x)).join('');
}

function filaInforme(x) {
    const tipo = x.esParcial
        ? `<strong>${esc(x.informeNumero)}</strong> <span class="badge badge-rectificado">PARCIAL</span>`
        : `<strong>${esc(x.informeNumero)}</strong> <span class="badge badge-completo">COMPLETO</span>`;
    return `
        <tr>
            <td>${esc(fmtFecha(x.fecha))}</td>
            <td>${esc(x.pedidoNumero ?? '')}</td>
            <td>${esc(oGuion(x.paciente))}</td>
            <td>${esc(oGuion(x.dni))}</td>
            <td>${tipo}</td>
            <td>${x.entregado ? badge('entregado') : badge('pendiente')}</td>
            <td style="white-space:nowrap;">
                <button class="btn btn-ghost btn-sm" data-accion="dossier" data-id="${esc(String(x.pedidoId))}" title="Ver el detalle del pedido"><i class="bi bi-eye"></i></button>
                ${accionesInforme(x.informeId, x.pedidoId, x.telefono, x.paciente)}
            </td>
        </tr>`;
}

function filaPendiente(x) {
    const parcial = x.tieneParcial ? ' <span class="badge badge-rectificado">parcial emitido</span>' : '';
    const res = x.clinicos > 0 ? `${x.cargados}/${x.clinicos}` : String(x.cargados);
    // El informe se emite desde la pantalla de carga (boton Emitir / Imprimir).
    const accion = x.cargados === 0
        ? `<a class="btn btn-ghost btn-sm" href="${labPath(`/resultados/cargar?pedido_id=${esc(String(x.pedidoId))}`)}" title="Todavía no tiene resultados cargados"><i class="bi bi-input-cursor-text"></i> Cargar</a>`
        : `<a class="btn btn-primary btn-sm" href="${labPath(`/resultados/cargar?pedido_id=${esc(String(x.pedidoId))}`)}" title="Abrir el pedido para emitir / imprimir el informe"><i class="bi bi-printer"></i> Emitir</a>`;
    return `
        <tr class="sin-informe">
            <td>${esc(fmtFecha(x.fecha))}</td>
            <td>${esc(x.pedidoNumero ?? '')}</td>
            <td>${esc(oGuion(x.paciente))}</td>
            <td>${esc(oGuion(x.dni))}</td>
            <td class="muted">sin informe${parcial}</td>
            <td>${badge(x.estadoPedido)} <span class="muted">${esc(res)}</span></td>
            <td style="white-space:nowrap;">
                <button class="btn btn-ghost btn-sm" data-accion="dossier" data-id="${esc(String(x.pedidoId))}" title="Ver el detalle del pedido"><i class="bi bi-eye"></i></button>
                ${accion}
            </td>
        </tr>`;
}

/* ================= Dossier ================= */

$lista.addEventListener('click', (e) => {
    const btn = e.target.closest('button[data-accion="dossier"]');
    if (btn) abrirDossier(parseInt(btn.dataset.id, 10));
});

async function abrirDossier(pedidoId) {
    try {
        const d = await api.get(`/api/historial?pedido_id=${pedidoId}`);
        renderDossier(d);
        $dossierWrap.hidden = false;
        $dossier.scrollIntoView({ behavior: 'smooth', block: 'start' });
    } catch (e) {
        showMsg(`Error: ${e.message}`, 'error');
    }
}

function renderDossier(d) {
    const p = d.pedido;
    const snap = p.snapshot_paciente || {};
    const nombre = typeof snap === 'object' ? (snap.nombre || '-') : '-';

    const resultados = (d.resultados || []).map((r) => {
        const valor = r.valor_numerico ?? r.valor_texto ?? '-';
        const cls = r.es_critico ? 'valor-critico' : (r.es_anormal ? 'valor-anormal' : '');
        return `
        <tr>
            <td>${esc(r.determinacion_nombre || '-')}</td>
            <td><span class="${cls}">${esc(valor)}</span> ${esc(r.unidad || '')}</td>
            <td>${badge(r.estado)}</td>
        </tr>`;
    }).join('');

    const informes = (d.informes || []).map((i) => `
        <tr>
            <td><strong>${esc(i.numero)}</strong></td>
            <td>${esc(fmtFecha(i.fecha_emision))}</td>
            <td>${parseInt(i.es_parcial, 10) ? '<span class="badge badge-rectificado">PARCIAL</span>' : '<span class="badge badge-completo">COMPLETO</span>'}</td>
            <td>${parseInt(i.entregado, 10) ? badge('entregado') : badge('pendiente')}</td>
            <td>${accionesInforme(i.id, p.id, state.paciente?.telefono, nombre)}</td>
        </tr>`).join('');

    $dossier.innerHTML = `
        <div class="grid" style="margin-bottom: 1rem;">
            <div><strong>Numero:</strong> ${esc(p.numero)}</div>
            <div><strong>Estado:</strong> ${badge(p.estado)} ${parseInt(p.es_critico, 10) ? '<span class="badge badge-critico">CRITICO</span>' : ''}</div>
            <div><strong>Paciente:</strong> ${esc(nombre)}</div>
            <div><strong>Realizado:</strong> ${esc(fmtFecha(p.fecha_extraccion || p.fecha_solicitud))}</div>
        </div>

        <h3>Resultados</h3>
        <div class="table-wrap" style="margin-bottom: 1rem;">
            <table class="table tabla-fina"><thead><tr><th>Determinacion</th><th>Valor</th><th>Estado</th></tr></thead><tbody>${resultados || '<tr><td colspan="3" class="muted">Sin resultados</td></tr>'}</tbody></table>
        </div>

        <h3>Informes</h3>
        <div class="table-wrap">
            <table class="table tabla-fina"><thead><tr><th>Numero</th><th>Emision</th><th>Tipo</th><th>Estado</th><th>Acciones</th></tr></thead><tbody>${informes || '<tr><td colspan="5" class="muted">Sin informes emitidos</td></tr>'}</tbody></table>
        </div>
    `;
}

// Carga inicial
cargar();
