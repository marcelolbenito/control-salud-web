import { api } from '../api.js';

const fmtMoney = v => `$ ${Number(v ?? 0).toLocaleString('es-AR', { minimumFractionDigits: 2 })}`;
const escape = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));

function flash(msg, tipo = 'info') {
  const el = document.getElementById('mensaje');
  el.textContent = msg;
  el.className = `mensaje mensaje-${tipo}`;
  el.hidden = false;
  setTimeout(() => { el.hidden = true; }, 4000);
}

export async function initVerLote(loteId) {
  const titulo = document.getElementById('lote-titulo');
  const meta = document.getElementById('lote-meta');
  const acciones = document.getElementById('lote-acciones');
  const tbody = document.getElementById('pedidos-tbody');

  let loteAbierto = false;

  async function recargar() {
    try {
      const data = await api.get(`/api/facturacion-os?accion=ver&id=${loteId}`);
      const lote = data.lote;
      loteAbierto = lote.estado === 'abierto';
      titulo.textContent = `Lote ${lote.numero} — ${lote.estado.toUpperCase()}`;
      meta.textContent =
        `OS #${lote.obra_social_id} — Periodo ${lote.fecha_desde} a ${lote.fecha_hasta} — ` +
        `${lote.cantidad_pedidos} pedidos — ${fmtMoney(lote.monto_total)}`;

      const baseAcciones = `
        <a class="btn" target="_blank" href="/api/facturacion-os?accion=pdf&id=${loteId}">Descargar PDF</a>
        <a class="btn" href="/api/facturacion-os?accion=csv&id=${loteId}">Descargar CSV</a>
      `;
      if (loteAbierto) {
        acciones.innerHTML = baseAcciones + `
          <button class="btn btn-danger" id="btn-anular">Anular lote</button>
          <button class="btn btn-primary" id="btn-cobrar">Registrar cobro</button>`;
        document.getElementById('btn-cobrar').onclick = () => {
          document.getElementById('modal-cobrar').hidden = false;
        };
        document.getElementById('btn-anular').onclick = () => {
          document.getElementById('modal-anular').hidden = false;
        };
      } else {
        acciones.innerHTML = baseAcciones;
      }

      tbody.innerHTML = data.pedidos.map(p => `
        <tr class="pedido-row" data-pedido-id="${p.pedido_id}">
          <td>
            <button type="button" class="btn-toggle" data-toggle="${p.pedido_id}" aria-expanded="false">▶</button>
            ${escape(p.pedido_numero)}
          </td>
          <td>${escape((p.fecha_solicitud || '').slice(0,10))}</td>
          <td>${escape(p.paciente_nro_hc)}</td>
          <td>${escape(`${p.paciente_apellido ?? ''}, ${p.paciente_nombres ?? ''}`)}</td>
          <td class="num" id="monto-${p.pedido_id}">${fmtMoney(p.monto_seguro_snapshot)}</td>
          <td>${loteAbierto
            ? `<button class="btn btn-sm btn-danger" data-quitar="${p.pedido_id}">Quitar</button>`
            : ''}</td>
        </tr>
        <tr class="items-row" id="items-${p.pedido_id}" hidden>
          <td colspan="6"><div class="items-cont muted">Cargando items…</div></td>
        </tr>
      `).join('');
    } catch (err) {
      flash(`Error: ${err.message}`, 'error');
    }
  }

  async function cargarItemsPedido(pedidoId) {
    const cont = document.querySelector(`#items-${pedidoId} .items-cont`);
    try {
      const data = await api.get(`/api/facturacion-os?accion=items-pedido&id=${loteId}&pedido_id=${pedidoId}`);
      const items = data.items || [];
      if (items.length === 0) {
        cont.innerHTML = `<p class="muted">Sin items.</p>`;
        return;
      }
      cont.innerHTML = `
        <table class="table table-sm">
          <thead>
            <tr>
              <th style="width:32px">${loteAbierto ? 'OS' : ''}</th>
              <th>Determinaci&oacute;n</th>
              <th style="width:90px">Unidades NBU</th>
              <th style="width:120px">$ / UB</th>
              <th style="width:120px" class="num">Monto</th>
            </tr>
          </thead>
          <tbody>
            ${items.map(it => `
              <tr class="${it.excluido == 1 ? 'item-excluido' : ''}">
                <td>
                  ${loteAbierto
                    ? `<input type="checkbox" class="chk-cubre" data-item-id="${it.item_id}" ${it.excluido == 0 ? 'checked' : ''} title="Tildado = lo cubre la OS">`
                    : (it.excluido == 1 ? '✗' : '✓')}
                </td>
                <td>${escape(it.determinacion_codigo)} — ${escape(it.determinacion_nombre)}</td>
                <td>${escape(it.unidades_nbu)}</td>
                <td>${fmtMoney(it.valor_unitario)}</td>
                <td class="num ${it.excluido == 1 ? 'tachado' : ''}">${fmtMoney(it.monto)}</td>
              </tr>
            `).join('')}
          </tbody>
        </table>
        <p class="hint">Destildar = la OS no cubre. El monto pasa a paciente y se descuenta del lote.</p>
      `;

      cont.querySelectorAll('.chk-cubre').forEach(chk => {
        chk.addEventListener('change', async () => {
          const itemId = chk.dataset.itemId;
          const cubre = chk.checked;
          chk.disabled = true;
          try {
            const accion = cubre ? 'incluir-item' : 'excluir-item';
            const resp = await api.post(`/api/facturacion-os?accion=${accion}&id=${loteId}&item_id=${itemId}`, {});
            const monto = document.getElementById(`monto-${pedidoId}`);
            if (monto) monto.textContent = fmtMoney(resp.monto_seguro);
            cargarItemsPedido(pedidoId);
            // tambien recargo total del lote
            recargarMetaSoloTotales();
            flash(cubre ? 'Item incluido' : 'Item excluido (pasa a paciente)', 'success');
          } catch (err) {
            chk.checked = !cubre;
            flash(`Error: ${err.message}`, 'error');
          } finally {
            chk.disabled = false;
          }
        });
      });
    } catch (err) {
      cont.innerHTML = `<p class="muted">Error: ${escape(err.message)}</p>`;
    }
  }

  async function recargarMetaSoloTotales() {
    try {
      const data = await api.get(`/api/facturacion-os?accion=ver&id=${loteId}`);
      const lote = data.lote;
      meta.textContent =
        `OS #${lote.obra_social_id} — Periodo ${lote.fecha_desde} a ${lote.fecha_hasta} — ` +
        `${lote.cantidad_pedidos} pedidos — ${fmtMoney(lote.monto_total)}`;
    } catch {}
  }

  tbody.addEventListener('click', async e => {
    const togglBtn = e.target.closest('[data-toggle]');
    if (togglBtn) {
      const pedidoId = togglBtn.dataset.toggle;
      const row = document.getElementById(`items-${pedidoId}`);
      const expanded = row.hidden === false;
      row.hidden = expanded;
      togglBtn.textContent = expanded ? '▶' : '▼';
      togglBtn.setAttribute('aria-expanded', expanded ? 'false' : 'true');
      if (!expanded) cargarItemsPedido(pedidoId);
      return;
    }

    const btn = e.target.closest('[data-quitar]');
    if (!btn) return;
    const pedidoId = btn.dataset.quitar;
    if (!confirm(`Quitar el pedido del lote?`)) return;
    try {
      await api.post(`/api/facturacion-os?accion=quitar-pedido&id=${loteId}&pedido_id=${pedidoId}`, {});
      flash('Pedido quitado', 'success');
      recargar();
    } catch (err) {
      flash(`Error: ${err.message}`, 'error');
    }
  });

  document.querySelectorAll('[data-cerrar]').forEach(b => {
    b.onclick = () => { b.closest('.modal').hidden = true; };
  });

  document.getElementById('form-cobrar').addEventListener('submit', async e => {
    e.preventDefault();
    const fd = new FormData(e.target);
    const body = Object.fromEntries(fd.entries());
    if (body.referencia === '') delete body.referencia;
    if (body.observaciones === '') delete body.observaciones;
    try {
      await api.post(`/api/facturacion-os?accion=cobrar&id=${loteId}`, body);
      document.getElementById('modal-cobrar').hidden = true;
      flash('Lote cobrado', 'success');
      recargar();
    } catch (err) {
      flash(`Error: ${err.message}`, 'error');
    }
  });

  document.getElementById('form-anular').addEventListener('submit', async e => {
    e.preventDefault();
    const fd = new FormData(e.target);
    try {
      await api.post(`/api/facturacion-os?accion=anular&id=${loteId}`, { motivo: fd.get('motivo') });
      document.getElementById('modal-anular').hidden = true;
      flash('Lote anulado', 'success');
      recargar();
    } catch (err) {
      flash(`Error: ${err.message}`, 'error');
    }
  });

  recargar();
}
