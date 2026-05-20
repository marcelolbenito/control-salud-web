import { initNbuTab } from './nbu-determinaciones.js';
import { initOsTab } from './valores-os.js';

const tabs = document.querySelectorAll('.tab[data-tab]');
const panels = {
  nbu: document.getElementById('tab-nbu'),
  os: document.getElementById('tab-os'),
};

function activateTab(name) {
  tabs.forEach((t) => {
    const isActive = t.dataset.tab === name;
    t.classList.toggle('is-active', isActive);
    t.setAttribute('aria-selected', isActive ? 'true' : 'false');
  });
  Object.entries(panels).forEach(([k, p]) => {
    if (p) p.hidden = k !== name;
  });
}

tabs.forEach((t) => {
  t.addEventListener('click', () => activateTab(t.dataset.tab));
});

const $msg = document.getElementById('mensaje');
function flash(text, kind = 'ok') {
  if (!$msg) return;
  $msg.textContent = text;
  $msg.className = `mensaje mensaje-${kind}`;
  $msg.hidden = false;
  setTimeout(() => { $msg.hidden = true; }, 3000);
}

initNbuTab({ flash });
initOsTab({ flash });
activateTab('nbu');
