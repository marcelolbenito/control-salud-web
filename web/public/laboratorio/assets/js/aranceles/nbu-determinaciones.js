import { api } from '../api.js';

const ENDPOINT = '/api/aranceles?accion=nbu-determinaciones';
const $tbody = document.getElementById('nbu-tbody');
const $buscar = document.getElementById('nbu-buscar');

let registros = [];

function escapeHtml(s) {
  return String(s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
}

function render(filtro = '') {
  const q = filtro.trim().toLowerCase();
  const visibles = q
    ? registros.filter((r) =>
        r.codigo.toLowerCase().includes(q) || r.nombre.toLowerCase().includes(q),
      )
    : registros;

  $tbody.innerHTML = visibles
    .map((r) => `
      <tr data-det-id="${r.determinacion_id}">
        <td>${escapeHtml(r.codigo)}</td>
        <td>${escapeHtml(r.nombre)}</td>
        <td>${escapeHtml(r.area)}</td>
        <td>
          <input type="number" class="cell-input" min="0" step="0.01"
                 value="${r.unidades ?? ''}" data-original="${r.unidades ?? ''}">
        </td>
        <td class="cell-status"></td>
      </tr>
    `).join('');
}

async function cargar() {
  try {
    // api.get() already unwraps body.data — returns the array directly
    const data = await api.get(ENDPOINT);
    registros = data ?? [];
    render($buscar.value);
  } catch (err) {
    $tbody.innerHTML = `<tr><td colspan="5" class="muted">Error: ${escapeHtml(err.message ?? err)}</td></tr>`;
  }
}

async function guardarFila(tr, flash) {
  const detId = parseInt(tr.dataset.detId, 10);
  const $input = tr.querySelector('.cell-input');
  const $status = tr.querySelector('.cell-status');
  const valor = parseFloat($input.value);

  if (Number.isNaN(valor) || valor < 0) {
    $status.textContent = '✗';
    $status.className = 'cell-status cell-error';
    return;
  }
  if ($input.value === $input.dataset.original) return;

  $status.textContent = '…';
  $status.className = 'cell-status cell-saving';
  try {
    await api.post(ENDPOINT, { determinacion_id: detId, unidades: valor });
    $input.dataset.original = $input.value;
    $status.textContent = '✓';
    $status.className = 'cell-status cell-saved';
    flash('Guardado', 'ok');
    setTimeout(() => { $status.textContent = ''; $status.className = 'cell-status'; }, 1500);
  } catch (err) {
    $status.textContent = '✗';
    $status.className = 'cell-status cell-error';
    flash(err.message ?? 'Error al guardar', 'err');
  }
}

export function initNbuTab({ flash }) {
  $tbody.addEventListener('blur', (e) => {
    if (e.target.classList.contains('cell-input')) {
      guardarFila(e.target.closest('tr'), flash);
    }
  }, true);

  $tbody.addEventListener('keydown', (e) => {
    if (e.key === 'Enter' && e.target.classList.contains('cell-input')) {
      e.preventDefault();
      e.target.blur();
    }
  });

  $buscar.addEventListener('input', (e) => render(e.target.value));

  cargar();
}
