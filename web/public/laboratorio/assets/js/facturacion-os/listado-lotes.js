import { api } from '../api.js';

const fmtMoney = v => `$ ${Number(v).toLocaleString('es-AR', { minimumFractionDigits: 2 })}`;
const escape = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));

export function initListadoLotes({ flash }) {
  const btn = document.getElementById('btn-buscar-lotes');
  const tbody = document.getElementById('lotes-tbody');

  async function buscar() {
    const params = new URLSearchParams({ accion: 'listar' });
    const os = document.getElementById('filtro-os').value;
    const estado = document.getElementById('filtro-estado').value;
    const desde = document.getElementById('filtro-desde').value;
    const hasta = document.getElementById('filtro-hasta').value;
    if (os) params.set('obra_social_id', os);
    if (estado) params.set('estado', estado);
    if (desde) params.set('fecha_desde', desde);
    if (hasta) params.set('fecha_hasta', hasta);

    try {
      const data = await api.get(`/api/facturacion-os?${params}`);
      const lotes = data?.lotes ?? [];
      if (lotes.length === 0) {
        tbody.innerHTML = `<tr><td colspan="7">Sin lotes</td></tr>`;
        return;
      }
      tbody.innerHTML = lotes.map(l => `
        <tr>
          <td>${escape(l.numero)}</td>
          <td>${escape(l.obra_social_nombre ?? `OS #${l.obra_social_id}`)}</td>
          <td>${escape(l.fecha_desde)} a ${escape(l.fecha_hasta)}</td>
          <td>${l.cantidad_pedidos}</td>
          <td>${fmtMoney(l.monto_total)}</td>
          <td><span class="estado estado-${escape(l.estado)}">${escape(l.estado.toUpperCase())}</span></td>
          <td><a class="btn btn-sm" href="/facturacion-os/ver?id=${l.id}">Ver</a></td>
        </tr>
      `).join('');
    } catch (err) {
      flash(`Error al listar lotes: ${err.message}`, 'error');
    }
  }

  btn.addEventListener('click', buscar);
  buscar();
}
