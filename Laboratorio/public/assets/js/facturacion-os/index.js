import { initListadoLotes } from './listado-lotes.js';
import { initNuevoLote } from './nuevo-lote.js';

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
  initListadoLotes({ flash: showFlash });
  initNuevoLote({ flash: showFlash });
});
