import { api } from '../api.js';

const ENDPOINT = '/api/aranceles?accion=valores-os';
const VIGENCIAS_ENDPOINT = '/api/aranceles?accion=vigencias-os';
const $tbody = document.getElementById('os-tbody');
const $buscar = document.getElementById('os-buscar');
const $modal = document.getElementById('os-modal');
const $modalTitulo = document.getElementById('os-modal-titulo');
const $modalCuerpo = document.getElementById('os-modal-cuerpo');

let registros = [];
let flashRef = null;

function escapeHtml(s) {
  return String(s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
}

function fmtFecha(f) {
  if (!f) return '—';
  return String(f).replace('T', ' ').replace(/:\d{2}$/, '');
}

function fmtPesos(v) {
  if (v === null || v === undefined || v === '') return '—';
  return Number(v).toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function render(filtro = '') {
  const q = filtro.trim().toLowerCase();
  const visibles = q
    ? registros.filter((r) => r.nombre.toLowerCase().includes(q))
    : registros;

  $tbody.innerHTML = visibles.map((r) => `
    <tr data-os-id="${r.obra_social_id}" data-os-nombre="${escapeHtml(r.nombre)}">
      <td>${escapeHtml(r.nombre)}</td>
      <td class="num">$ ${fmtPesos(r.valor_unitario)}</td>
      <td>${escapeHtml(r.fecha_desde ?? '—')}</td>
      <td class="muted">${escapeHtml(fmtFecha(r.updated_at))}</td>
      <td class="actions">
        <button type="button" class="ord-btn ord-btn--ghost btn-nueva-vigencia">＋ Nueva vigencia</button>
        <button type="button" class="ord-btn ord-btn--ghost btn-historial">Historial</button>
      </td>
    </tr>
  `).join('');
}

async function cargar() {
  try {
    const data = await api.get(ENDPOINT);
    registros = data ?? [];
    render($buscar.value);
  } catch (err) {
    $tbody.innerHTML = `<tr><td colspan="5" class="muted">Error: ${escapeHtml(err.message ?? err)}</td></tr>`;
  }
}

function abrirModal(titulo, htmlCuerpo) {
  $modalTitulo.textContent = titulo;
  $modalCuerpo.innerHTML = htmlCuerpo;
  $modal.hidden = false;
}

function cerrarModal() {
  $modal.hidden = true;
  $modalCuerpo.innerHTML = '';
}

function abrirNuevaVigencia(osId, osNombre) {
  const hoy = new Date().toISOString().slice(0, 10);
  abrirModal(`Nueva vigencia · ${osNombre}`, `
    <form id="form-nueva-vigencia">
      <label>Valor por unidad NBU ($)
        <input type="number" name="valor_unitario" min="0" step="0.01" required autofocus>
      </label>
      <label>Vigente desde
        <input type="date" name="fecha_desde" value="${hoy}" required>
      </label>
      <p class="hint">Se cerrará automáticamente la vigencia anterior con fecha_hasta = (esta fecha − 1 día).</p>
      <div class="form-actions">
        <button type="button" class="ord-btn" data-cerrar>Cancelar</button>
        <button type="submit" class="ord-btn ord-btn--primary">Guardar</button>
      </div>
    </form>
  `);

  document.getElementById('form-nueva-vigencia').addEventListener('submit', async (e) => {
    e.preventDefault();
    const fd = new FormData(e.target);
    const body = {
      obra_social_id: osId,
      valor_unitario: parseFloat(fd.get('valor_unitario')),
      fecha_desde: fd.get('fecha_desde'),
    };
    try {
      await api.post(ENDPOINT, body);
      flashRef('Vigencia creada', 'ok');
      cerrarModal();
      cargar();
    } catch (err) {
      flashRef(err.message ?? 'Error al guardar', 'err');
    }
  });
}

async function abrirHistorial(osId, osNombre) {
  abrirModal(`Historial · ${osNombre}`, `<p class="muted">Cargando…</p>`);
  try {
    const data = await api.get(`${VIGENCIAS_ENDPOINT}&os_id=${osId}`);
    const rows = data ?? [];
    if (rows.length === 0) {
      $modalCuerpo.innerHTML = `<p class="muted">Sin vigencias cargadas.</p>
        <div class="form-actions"><button type="button" class="ord-btn" data-cerrar>Cerrar</button></div>`;
      return;
    }
    $modalCuerpo.innerHTML = `
      <table class="table">
        <thead><tr><th>Desde</th><th>Hasta</th><th>$ / UB</th></tr></thead>
        <tbody>
          ${rows.map((r) => `
            <tr>
              <td>${escapeHtml(r.fecha_desde)}</td>
              <td>${escapeHtml(r.fecha_hasta ?? '(vigente)')}</td>
              <td class="num">$ ${fmtPesos(r.valor_unitario)}</td>
            </tr>`).join('')}
        </tbody>
      </table>
      <div class="form-actions"><button type="button" class="ord-btn" data-cerrar>Cerrar</button></div>
    `;
  } catch (err) {
    $modalCuerpo.innerHTML = `<p class="muted">Error: ${escapeHtml(err.message ?? err)}</p>
      <div class="form-actions"><button type="button" class="ord-btn" data-cerrar>Cerrar</button></div>`;
  }
}

export function initOsTab({ flash }) {
  flashRef = flash;

  $tbody.addEventListener('click', (e) => {
    const tr = e.target.closest('tr');
    if (!tr) return;
    const osId = parseInt(tr.dataset.osId, 10);
    const osNombre = tr.dataset.osNombre;
    if (e.target.classList.contains('btn-nueva-vigencia')) {
      abrirNuevaVigencia(osId, osNombre);
    } else if (e.target.classList.contains('btn-historial')) {
      abrirHistorial(osId, osNombre);
    }
  });

  $modal.addEventListener('click', (e) => {
    if (e.target === $modal || e.target.matches('[data-cerrar]')) {
      cerrarModal();
    }
  });

  $buscar.addEventListener('input', (e) => render(e.target.value));

  cargar();
}
