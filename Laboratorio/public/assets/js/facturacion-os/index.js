import { api } from '../api.js';
import { initListadoLotes } from './listado-lotes.js';
import { initNuevoLote } from './nuevo-lote.js';

async function poblarObrasSociales() {
  const selFiltro = document.getElementById('filtro-os');
  const selNuevo = document.getElementById('nuevo-os');
  if (!selFiltro && !selNuevo) return;
  try {
    const data = await api.get('/api/pacientes?accion=obras_sociales');
    const obras = (data?.obras_sociales ?? []).slice()
      .sort((a, b) => String(a.nombre).localeCompare(String(b.nombre), 'es'));
    for (const sel of [selFiltro, selNuevo]) {
      if (!sel) continue;
      for (const os of obras) {
        const opt = document.createElement('option');
        opt.value = String(os.id);
        opt.textContent = os.nombre;
        sel.appendChild(opt);
      }
    }
  } catch (e) {
    console.error('No se pudieron cargar las obras sociales', e);
  }
}

function showFlash(msg, tipo = 'info') {
  const el = document.getElementById('mensaje');
  if (!el) return;
  el.textContent = msg;
  el.className = `mensaje mensaje-${tipo}`;
  el.hidden = false;
  setTimeout(() => { el.hidden = true; }, 4000);
}

function setupTabs() {
  const tabs = document.querySelectorAll('.tab');
  const panels = document.querySelectorAll('.tab-panel');
  tabs.forEach(t => {
    t.addEventListener('click', () => {
      tabs.forEach(x => x.classList.remove('is-active'));
      t.classList.add('is-active');
      panels.forEach(p => p.hidden = p.id !== `tab-${t.dataset.tab}`);
    });
  });
}

document.addEventListener('DOMContentLoaded', () => {
  setupTabs();
  poblarObrasSociales();
  initListadoLotes({ flash: showFlash });
  initNuevoLote({ flash: showFlash });
});
