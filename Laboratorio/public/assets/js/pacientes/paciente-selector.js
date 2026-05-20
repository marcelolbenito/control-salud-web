/**
 * PacienteSelector — modal reutilizable para elegir un paciente.
 *
 * Uso:
 *   import { PacienteSelector } from './pacientes/paciente-selector.js';
 *   const paciente = await PacienteSelector.elegir({ titulo: 'Elegir paciente' });
 *   if (paciente) { ... }
 */
import { api } from '../api.js?v=6';

const ENDPOINT = '/api/pacientes';

let obrasSocialesCache = null;

const SEXOS = [
    { value: '',  label: '<Todos>' },
    { value: 'M', label: 'Masculino' },
    { value: 'F', label: 'Femenino' },
    { value: 'X', label: 'No binario' },
];

const COLUMNAS_ORDENABLES = {
    nro_hc:   { asc: 'nro_hc_asc',   desc: 'nro_hc_desc' },
    paciente: { asc: 'apellido_asc', desc: 'apellido_desc' },
    dni:      { asc: 'dni_asc',      desc: 'dni_desc' },
};

export class PacienteSelector {
    /**
     * Abre el modal y devuelve una Promise que resuelve con el paciente elegido o null si cancela.
     * @param {{ titulo?: string }} [opts]
     * @returns {Promise<object|null>}
     */
    static elegir(opts = {}) {
        return new Promise((resolve) => {
            const inst = new PacienteSelector(opts, resolve);
            inst.abrir();
        });
    }

    constructor(opts, resolve) {
        this.opts = opts;
        this.resolve = resolve;
        this.overlay = null;
        this.modal = null;
        this.previousActiveElement = null;
        this.seleccionado = null;
        this.ordenActual = 'apellido_asc';
        this.pacientesActuales = []; // Indexado por data-ps-idx en los <tr>.
        this.escuchadorEsc = (e) => { if (e.key === 'Escape') this.cancelar(); };
    }

    abrir() {
        this.previousActiveElement = document.activeElement;
        this.overlay = document.createElement('div');
        this.overlay.className = 'ps-overlay';
        this.overlay.setAttribute('role', 'dialog');
        this.overlay.setAttribute('aria-modal', 'true');
        this.overlay.innerHTML = this.htmlInicial();

        this.modal = this.overlay.querySelector('.ps-modal');
        document.body.appendChild(this.overlay);
        document.addEventListener('keydown', this.escuchadorEsc);

        this.cablearEventosBasicos();
        this.cablearEventosBusqueda();
        this.cablearEventosTabla();
        this.cablearEventosOrden();
        this.poblarObrasSociales().catch((e) => this.mostrarError(e.message));
        this.poblarSexos();
        this.enfocarInicial();
        // Listado inicial: mismos pacientes que Control Salud (hasta 100, sin filtros).
        this.ejecutarBusqueda().catch((e) => this.mostrarError(e.message || 'Error al buscar'));
    }

    cerrar(resultado) {
        document.removeEventListener('keydown', this.escuchadorEsc);
        this.overlay?.remove();
        this.overlay = null;
        this.modal = null;
        if (this.previousActiveElement && typeof this.previousActiveElement.focus === 'function') {
            this.previousActiveElement.focus();
        }
        this.resolve(resultado);
    }

    cancelar() { this.cerrar(null); }

    htmlInicial() {
        const titulo = this.opts.titulo ?? 'Elección del Paciente';
        return `
        <div class="ps-modal">
          <div class="ps-modal__header">
            <h2 class="ps-modal__title">${escapeHtml(titulo)}</h2>
            <button type="button" class="ps-modal__close" aria-label="Cerrar" data-ps-close>×</button>
          </div>
          <div class="ps-modal__body">
            <form class="ps-form" data-ps-form>
              <div class="ps-field"><label for="ps-nro-hc">Nº HC:</label><input id="ps-nro-hc" name="nro_hc" type="text"></div>
              <div class="ps-field"><label for="ps-dni">C.I./DNI:</label><input id="ps-dni" name="dni" type="text"></div>
              <div class="ps-field"><label for="ps-apellido">Apellido:</label><input id="ps-apellido" name="apellido" type="text"></div>
              <div class="ps-field"><label for="ps-seguro">Seguro:</label><select id="ps-seguro" name="obra_social_id"><option value="">&lt;Todos los Seguros&gt;</option></select></div>
              <div class="ps-field"><label for="ps-nombres">Nombres:</label><input id="ps-nombres" name="nombres" type="text"></div>
              <div class="ps-field"><label for="ps-afiliado">Nº Afiliado:</label><input id="ps-afiliado" name="nro_afiliado" type="text"></div>
              <div class="ps-field"><label for="ps-telefono">Teléfono:</label><input id="ps-telefono" name="telefono" type="text"></div>
              <div class="ps-field"><label for="ps-fecha">Fecha Nac.:</label><input id="ps-fecha" name="fecha_nacimiento" type="date"></div>
              <div class="ps-field"><label for="ps-sexo">Sexo:</label><select id="ps-sexo" name="sexo"></select></div>
            </form>
            <div class="ps-actions">
              <button type="button" class="ps-button ps-button--primary" data-ps-buscar>Buscar</button>
              <button type="button" class="ps-button" data-ps-limpiar>Limpiar</button>
            </div>
            <div class="ps-selected" data-ps-selected><strong>Paciente Seleccionado:</strong> Ninguno</div>
            <div class="ps-meta">
              <label><input type="checkbox" data-ps-orden-hc> Ordenar por Nº HC</label>
              <span data-ps-banner-slot></span>
              <span aria-live="polite">Pacientes encontrados: <strong data-ps-count>0</strong></span>
            </div>
            <div class="ps-table-wrap">
              <table class="ps-table">
                <thead>
                  <tr>
                    <th data-ps-sort="nro_hc">Nº HC</th>
                    <th data-ps-sort="paciente">Paciente</th>
                    <th data-ps-sort="dni">DNI</th>
                    <th>Seguro</th>
                    <th>Nº Afiliado</th>
                  </tr>
                </thead>
                <tbody data-ps-tbody>
                  <tr><td colspan="5" class="ps-empty">Aplicá filtros y presioná Buscar.</td></tr>
                </tbody>
              </table>
            </div>
          </div>
          <div class="ps-modal__footer">
            <button type="button" class="ps-button ps-button--primary" data-ps-elegir disabled>Elegir Paciente</button>
            <button type="button" class="ps-button" disabled title="Los pacientes se gestionan desde el sistema principal">Nuevo Paciente *</button>
            <button type="button" class="ps-button" disabled title="Los pacientes se gestionan desde el sistema principal">Eliminar Paciente *</button>
            <button type="button" class="ps-button" data-ps-cancelar>Cancelar</button>
            <small style="margin-left:auto; color:#666">* Gestionados desde el sistema principal</small>
          </div>
        </div>`;
    }

    cablearEventosBasicos() {
        this.overlay.querySelector('[data-ps-close]').addEventListener('click', () => this.cancelar());
        this.overlay.querySelector('[data-ps-cancelar]').addEventListener('click', () => this.cancelar());
        this.overlay.addEventListener('click', (e) => {
            if (e.target === this.overlay) this.cancelar();
        });
        // Focus trap basico.
        this.overlay.addEventListener('keydown', (e) => this.atraparFoco(e));
    }

    atraparFoco(e) {
        if (e.key !== 'Tab') return;
        const focusables = this.modal.querySelectorAll(
            'button:not(:disabled), [href], input:not(:disabled), select:not(:disabled), textarea:not(:disabled), [tabindex]:not([tabindex="-1"])'
        );
        if (focusables.length === 0) return;
        const first = focusables[0];
        const last = focusables[focusables.length - 1];
        if (e.shiftKey && document.activeElement === first) {
            e.preventDefault();
            last.focus();
        } else if (!e.shiftKey && document.activeElement === last) {
            e.preventDefault();
            first.focus();
        }
    }

    enfocarInicial() {
        this.modal.querySelector('#ps-apellido')?.focus();
    }

    async poblarObrasSociales() {
        if (!obrasSocialesCache) {
            const data = await api.get(`${ENDPOINT}?accion=obras_sociales`);
            obrasSocialesCache = data.obras_sociales ?? [];
        }
        const sel = this.modal.querySelector('#ps-seguro');
        for (const os of obrasSocialesCache) {
            const opt = document.createElement('option');
            opt.value = String(os.id);
            opt.textContent = os.nombre;
            sel.appendChild(opt);
        }
    }

    poblarSexos() {
        const sel = this.modal.querySelector('#ps-sexo');
        for (const s of SEXOS) {
            const opt = document.createElement('option');
            opt.value = s.value;
            opt.textContent = s.label;
            sel.appendChild(opt);
        }
    }

    mostrarError(mensaje) {
        const slot = this.modal.querySelector('[data-ps-banner-slot]');
        slot.innerHTML = `<span class="ps-banner ps-banner--error">${escapeHtml(mensaje)}</span>`;
    }

    // === Búsqueda ===

    cablearEventosBusqueda() {
        this.modal.querySelector('[data-ps-buscar]').addEventListener('click', () => this.ejecutarBusqueda());
        this.modal.querySelector('[data-ps-limpiar]').addEventListener('click', () => this.limpiar());
        this.modal.querySelector('[data-ps-form]').addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.ejecutarBusqueda();
            }
        });
    }

    leerFiltros() {
        const form = this.modal.querySelector('[data-ps-form]');
        const fd = new FormData(form);
        const out = { orden: this.ordenActual };
        for (const [k, v] of fd.entries()) {
            const valor = String(v).trim();
            if (valor !== '') out[k] = valor;
        }
        return out;
    }

    async ejecutarBusqueda() {
        this.limpiarErroresDeCampos();
        this.modal.querySelector('[data-ps-banner-slot]').innerHTML = '';

        const filtros = this.leerFiltros();
        const qs = new URLSearchParams({ accion: 'buscar', ...filtros }).toString();

        try {
            const data = await api.get(`${ENDPOINT}?${qs}`);
            this.renderResultados(data);
        } catch (err) {
            if (err.status === 422 && err.fields) {
                this.marcarErroresDeCampos(err.fields);
            } else {
                this.mostrarError(err.message || 'Error al buscar');
            }
        }
    }

    renderResultados(data) {
        const tbody = this.modal.querySelector('[data-ps-tbody]');
        const slot  = this.modal.querySelector('[data-ps-banner-slot]');
        const count = this.modal.querySelector('[data-ps-count]');

        const pacientes = data.pacientes ?? [];
        count.textContent = String(data.total_encontrados ?? pacientes.length);

        if (data.truncado) {
            slot.innerHTML = `<span class="ps-banner ps-banner--info">Se muestran los primeros 100 — refiná tu búsqueda</span>`;
        } else {
            slot.innerHTML = '';
        }

        this.pacientesActuales = pacientes;

        if (pacientes.length === 0) {
            tbody.innerHTML = `<tr><td colspan="5" class="ps-empty">No se encontraron pacientes con esos criterios.</td></tr>`;
            this.actualizarSeleccion(null);
            return;
        }

        // Renderizamos solo el HTML visible y guardamos el objeto paciente en pacientesActuales[i].
        // El TR lleva data-ps-idx con el indice; al click leemos pacientesActuales[idx].
        // Esto evita serializar el objeto en un atributo HTML.
        tbody.innerHTML = pacientes.map((p, i) => `
            <tr data-ps-idx="${i}">
              <td>${escapeHtml(p.nro_hc)}</td>
              <td>${escapeHtml(p.apellido)}, ${escapeHtml(p.nombres)}</td>
              <td>${escapeHtml(p.dni)}</td>
              <td>${escapeHtml(p.obra_social_nombre ?? '')}</td>
              <td>${escapeHtml(p.nro_afiliado ?? '')}</td>
            </tr>`).join('');

        this.actualizarSeleccion(null);
    }

    limpiar() {
        this.modal.querySelector('[data-ps-form]').reset();
        this.modal.querySelector('[data-ps-tbody]').innerHTML =
            `<tr><td colspan="5" class="ps-empty">Aplicá filtros y presioná Buscar.</td></tr>`;
        this.modal.querySelector('[data-ps-count]').textContent = '0';
        this.modal.querySelector('[data-ps-banner-slot]').innerHTML = '';
        this.actualizarSeleccion(null);
        this.limpiarErroresDeCampos();
        this.modal.querySelector('#ps-apellido')?.focus();
    }

    actualizarSeleccion(p) {
        this.seleccionado = p;
        const cell = this.modal.querySelector('[data-ps-selected]');
        const btnElegir = this.modal.querySelector('[data-ps-elegir]');
        if (p) {
            cell.innerHTML = `<strong>Paciente Seleccionado:</strong> ${escapeHtml(p.apellido)}, ${escapeHtml(p.nombres)} (HC ${escapeHtml(p.nro_hc)})`;
            btnElegir.disabled = false;
        } else {
            cell.innerHTML = `<strong>Paciente Seleccionado:</strong> Ninguno`;
            btnElegir.disabled = true;
        }
    }

    limpiarErroresDeCampos() {
        this.modal.querySelectorAll('.ps-error').forEach((el) => el.classList.remove('ps-error'));
        this.modal.querySelectorAll('.ps-field-error').forEach((el) => el.remove());
    }

    marcarErroresDeCampos(fields) {
        for (const [campo, mensaje] of Object.entries(fields)) {
            const input = this.modal.querySelector(`[name="${CSS.escape(campo)}"]`);
            if (!input) continue;
            input.classList.add('ps-error');
            const sm = document.createElement('small');
            sm.className = 'ps-field-error';
            sm.textContent = mensaje;
            input.parentElement.appendChild(sm);
        }
    }

    // === Tabla y orden ===

    cablearEventosTabla() {
        const tbody = this.modal.querySelector('[data-ps-tbody]');

        tbody.addEventListener('click', (e) => {
            const tr = e.target.closest('tr[data-ps-idx]');
            if (!tr) return;
            tbody.querySelectorAll('tr.ps-selected-row').forEach((r) => r.classList.remove('ps-selected-row'));
            tr.classList.add('ps-selected-row');
            const idx = Number(tr.getAttribute('data-ps-idx'));
            this.actualizarSeleccion(this.pacientesActuales[idx] ?? null);
        });

        tbody.addEventListener('dblclick', (e) => {
            const tr = e.target.closest('tr[data-ps-idx]');
            if (!tr) return;
            const idx = Number(tr.getAttribute('data-ps-idx'));
            const p = this.pacientesActuales[idx];
            if (p) this.cerrar(p);
        });

        this.modal.querySelector('[data-ps-elegir]').addEventListener('click', () => {
            if (this.seleccionado) this.cerrar(this.seleccionado);
        });
    }

    cablearEventosOrden() {
        const ths = this.modal.querySelectorAll('th[data-ps-sort]');
        ths.forEach((th) => {
            th.addEventListener('click', () => {
                const col = th.getAttribute('data-ps-sort');
                const map = COLUMNAS_ORDENABLES[col];
                if (!map) return;
                this.ordenActual = (this.ordenActual === map.asc) ? map.desc : map.asc;
                // Si estaba el checkbox de HC tildado, lo destildamos al ordenar por otra columna.
                const chk = this.modal.querySelector('[data-ps-orden-hc]');
                if (col !== 'nro_hc') chk.checked = false;
                else chk.checked = true;
                this.ejecutarBusqueda();
            });
        });

        const chk = this.modal.querySelector('[data-ps-orden-hc]');
        chk.addEventListener('change', () => {
            this.ordenActual = chk.checked ? 'nro_hc_asc' : 'apellido_asc';
            this.ejecutarBusqueda();
        });
    }
}

function escapeHtml(s) {
    return String(s ?? '').replace(/[&<>"']/g, (c) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
    }[c]));
}
