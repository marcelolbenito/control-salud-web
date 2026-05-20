import { api } from '../api.js';

const fmtMoney = v => `$ ${Number(v).toLocaleString('es-AR', { minimumFractionDigits: 2 })}`;
const escape = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));

export function initNuevoLote({ flash }) {
  const btnBuscar = document.getElementById('btn-buscar-elegibles');
  const btnGenerar = document.getElementById('btn-generar-lote');
  const tbody = document.getElementById('elegibles-tbody');
  const resumen = document.getElementById('elegibles-resumen');
  const seleccionResumen = document.getElementById('seleccion-resumen');
  const resultado = document.getElementById('elegibles-resultado');
  const checkTodos = document.getElementById('check-todos');

  function actualizarSeleccion() {
    const checks = tbody.querySelectorAll('input[type="checkbox"]:checked');
    let total = 0;
    checks.forEach(c => total += Number(c.dataset.monto));
    seleccionResumen.textContent = `Seleccionados: ${checks.length} — ${fmtMoney(total)}`;
  }

  btnBuscar.addEventListener('click', async () => {
    const os = document.getElementById('nuevo-os').value;
    const desde = document.getElementById('nuevo-desde').value;
    const hasta = document.getElementById('nuevo-hasta').value;
    if (!os || !desde || !hasta) {
      flash('Completar OS, desde y hasta', 'error');
      return;
    }
    const params = new URLSearchParams({
      accion: 'elegibles', obra_social_id: os, fecha_desde: desde, fecha_hasta: hasta,
    });
    try {
      const data = await api.get(`/api/facturacion-os?${params}`);
      const pedidos = data?.pedidos ?? [];
      resumen.textContent = `${pedidos.length} pedidos elegibles — Total ${fmtMoney(data.monto_total)}`;
      tbody.innerHTML = pedidos.length === 0
        ? `<tr><td colspan="6">Sin pedidos elegibles para esos filtros</td></tr>`
        : pedidos.map(p => `
            <tr>
              <td><input type="checkbox" data-id="${p.id}" data-monto="${p.monto_seguro}" checked></td>
              <td>${escape(p.numero)}</td>
              <td>${escape((p.fecha_solicitud || '').slice(0,10))}</td>
              <td>${escape(p.paciente_nro_hc)}</td>
              <td>${escape(p.paciente_nombre)}</td>
              <td>${fmtMoney(p.monto_seguro)}</td>
            </tr>`).join('');
      resultado.hidden = false;
      actualizarSeleccion();
    } catch (err) {
      flash(`Error: ${err.message}`, 'error');
    }
  });

  checkTodos.addEventListener('change', () => {
    tbody.querySelectorAll('input[type="checkbox"]').forEach(c => c.checked = checkTodos.checked);
    actualizarSeleccion();
  });
  tbody.addEventListener('change', e => {
    if (e.target.matches('input[type="checkbox"]')) actualizarSeleccion();
  });

  btnGenerar.addEventListener('click', async () => {
    const ids = [...tbody.querySelectorAll('input[type="checkbox"]:checked')].map(c => Number(c.dataset.id));
    if (ids.length === 0) { flash('Seleccionar al menos un pedido', 'error'); return; }
    const body = {
      obra_social_id: Number(document.getElementById('nuevo-os').value),
      fecha_desde: document.getElementById('nuevo-desde').value,
      fecha_hasta: document.getElementById('nuevo-hasta').value,
      pedido_ids: ids,
      observaciones: document.getElementById('nuevo-obs').value || null,
    };
    try {
      const data = await api.post('/api/facturacion-os?accion=generar', body);
      window.location.href = `/facturacion-os/ver?id=${data.id}`;
    } catch (err) {
      flash(`Error al generar lote: ${err.message}`, 'error');
    }
  });
}
