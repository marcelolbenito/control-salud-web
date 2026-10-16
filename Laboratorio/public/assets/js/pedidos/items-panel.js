import { api, labPath } from '../api.js?v=13';

// Layout del hemograma (espejo de src/Helpers/HemogramaLayout.php). Si se
// cambia aca, actualizar tambien el helper PHP.
const HEMOGRAMA = {
    serieRojaHeader: 'GR',
    serieRojaIndices: ['HTO', 'HB', 'VCM', 'HCM', 'CHCM', 'RDW'],
    serieBlancaHeader: 'GB',
    formula: ['NEU', 'EOS', 'BAS', 'LIN', 'MON'],
    labels: {
        GR: 'Globulos rojos recuento',
        HTO: 'Hematocrito',
        HB: 'Hemoglobina dosaje',
        VCM: 'Volumen Corpuscular Medio',
        HCM: 'Hemoglobina Corpuscular Media',
        CHCM: 'Conc.Hemoglobina Corpuscular Media',
        RDW: 'Rdw (Amplitud de distribucion)',
        GB: 'Globulos blancos recuento',
        NEU: 'Neutrofilos Segmentados',
        EOS: 'Eosinofilos',
        BAS: 'Basofilos',
        LIN: 'Linfocitos',
        MON: 'Monocitos',
    },
    codigos() {
        return [this.serieRojaHeader, ...this.serieRojaIndices, this.serieBlancaHeader, ...this.formula];
    },
};

/**
 * Panel de trabajo de un pedido: tabla editable de items que permite cargar
 * resultados, agregar y quitar analisis, y ver TODO el historico anterior del
 * paciente por determinacion. Es el nucleo compartido entre "Nuevo pedido"
 * (al crear, en la misma pantalla) y "Cargar resultados" (pedido existente).
 *
 * Contrato de DOM (los elementos los provee cada pagina):
 *   tbody       <tbody> donde se renderizan las filas de items
 *   infoEl      elemento para la cabecera del pedido (opcional)
 *   addInput    <input> de busqueda para agregar analisis
 *   addList     <ul> dropdown de sugerencias para agregar
 *   guardarBtn  boton "Guardar resultados"
 *   msg(text, tipo)   funcion para mostrar mensajes
 *   onChange()        callback tras cualquier cambio (refrescar listados externos)
 */
export function crearItemsPanel(opts) {
    const { tbody, infoEl = null, addInput, addList, guardarBtn, msg, onChange } = opts;

    // Modo compacto: filas bajas, letra e inputs chicos. Menos scroll y menos
    // chance de cargar un valor en la fila equivocada.
    if (!document.getElementById('items-panel-css')) {
        const st = document.createElement('style');
        st.id = 'items-panel-css';
        st.textContent = `
            .tabla-items-compacta th, .tabla-items-compacta td { padding: 4px 8px; font-size: .85rem; line-height: 1.3; }
            .tabla-items-compacta td:nth-child(4) { font-size: .76rem; line-height: 1.25; max-width: 260px; }
            .tabla-items-compacta input[name="valor"] { width: 100px; padding: 4px 8px; font-size: .9rem; }
            .tabla-items-compacta .badge { font-size: .62rem; padding: 2px 6px; }
            .tabla-items-compacta .btn-sm { padding: 2px 8px; font-size: .74rem; }
            .tabla-items-compacta tr[data-item-id]:hover td { background: #f8fafc; }
        `;
        document.head.appendChild(st);
    }
    tbody.closest('table')?.classList.add('tabla-items-compacta');

    const state = {
        pedidoId: null,
        estado: null,
        pedido: null,
        items: [],
        resultados: {},
        catalogo: null, // { determinaciones, perfiles } — lazy
    };

    // ---------- helpers de formato ----------
    const esc = (s) => String(s ?? '').replace(/[<>&"']/g, (c) => ({
        '<': '&lt;', '>': '&gt;', '&': '&amp;', '"': '&quot;', "'": '&#39;',
    }[c]));

    const badge = (estado) =>
        `<span class="badge badge-${esc(estado)}">${esc(String(estado).replace('_', ' '))}</span>`;

    const fmtNumRef = (v) => {
        const n = parseFloat(v);
        return Number.isNaN(n) ? String(v) : String(n).replace('.', ',');
    };

    function fmtRango(r) {
        if (!r) return '';
        if (r.texto_referencia) return r.texto_referencia;
        const min = r.valor_referencia_min;
        const max = r.valor_referencia_max;
        const u = r.unidad ? ` ${r.unidad}` : '';
        if (min != null && max != null) return `${fmtNumRef(min)} a ${fmtNumRef(max)}${u}`;
        if (min != null) return `> ${fmtNumRef(min)}${u}`;
        if (max != null) return `< ${fmtNumRef(max)}${u}`;
        return '';
    }

    function fmtFechaCorta(s) {
        if (!s) return '';
        const d = new Date(String(s).replace(' ', 'T'));
        return Number.isNaN(d.getTime()) ? String(s) : d.toLocaleDateString('es-AR');
    }

    // Cuadro chico con los resultados anteriores del paciente en ese analisis.
    // Oculto por defecto para no confundir valores viejos con los que se estan
    // cargando: se abre/cierra con doble clic en el casillero del valor.
    function fmtAnteriores(it) {
        const arr = it.anteriores || [];
        let contenido;
        if (arr.length === 0) {
            contenido = '<span class="muted">(sin resultados anteriores)</span>';
        } else {
            contenido = arr.map((a) => {
                const v = (a.valor_numerico !== null && a.valor_numerico !== undefined && a.valor_numerico !== '')
                    ? String(parseFloat(a.valor_numerico)).replace('.', ',')
                    : (a.valor_texto || '');
                const color = a.es_anormal ? ' style="color:#c0392b;font-weight:600;"' : '';
                return `<span${color}>${esc(v)}</span> <span class="muted">(${esc(fmtFechaCorta(a.fecha))})</span>`;
            }).join(' · ');
        }
        // OJO: sin display en el style inline — un display inline le gana al
        // atributo hidden y el cuadro quedaba siempre visible.
        return `<div class="anteriores-box" data-anteriores hidden title="Resultados anteriores del paciente"`
            + ` style="font-size:.72rem;margin-top:3px;padding:2px 6px;border:1px solid #d8dee6;border-radius:4px;background:#f6f8fa;max-width:340px;">`
            + `<i class="bi bi-graph-up"></i> ${contenido}</div>`;
    }

    function valorParaInput(r) {
        if (!r) return '';
        const n = r.valor_numerico;
        if (n !== null && n !== undefined && n !== '') {
            const f = parseFloat(n);
            return Number.isNaN(f) ? String(n) : String(f);
        }
        return r.valor_texto ?? '';
    }

    function indexResultados(resultados) {
        const map = {};
        for (const r of resultados || []) map[r.pedido_item_id] = r;
        return map;
    }

    const editable = () => !['entregado', 'anulado'].includes(state.estado);

    // ---------- carga / render ----------
    async function load(pedidoId) {
        const data = await api.get(`/api/historial?pedido_id=${pedidoId}`);
        state.pedidoId = pedidoId;
        state.pedido = data.pedido ?? null;
        state.estado = data.pedido?.estado ?? null;
        state.items = data.items || [];
        state.resultados = indexResultados(data.resultados);
        renderInfo(data.pedido);
        renderItems();
        crearInformeBar();
        refrescarInformes();
    }

    async function reload() {
        if (!state.pedidoId) return;
        // Preservar lo tipeado y no guardado: recargar re-renderiza desde el
        // servidor y sin esto se pierden los valores pendientes (ej. al sacar
        // o agregar un analisis se borraba todo lo que se venia cargando).
        const tipeados = {};
        tbody.querySelectorAll('tr[data-item-id] input[name="valor"]').forEach((inp) => {
            const tr = inp.closest('tr[data-item-id]');
            if (tr && !inp.disabled && inp.value.trim() !== '') {
                tipeados[tr.dataset.itemId] = inp.value;
            }
        });
        await load(state.pedidoId);
        for (const [itemId, valor] of Object.entries(tipeados)) {
            const inp = tbody.querySelector(`tr[data-item-id="${itemId}"] input[name="valor"]`);
            if (inp && !inp.disabled) inp.value = valor;
        }
        recomputeAbsolutos();
    }

    function renderInfo(p) {
        if (!infoEl || !p) return;
        const snap = p.snapshot_paciente || {};
        const nombre = typeof snap === 'object' ? (snap.nombre || '-') : '-';
        infoEl.innerHTML = `
            <strong>${esc(p.numero)}</strong> · Paciente: ${esc(nombre)} · Estado: ${badge(p.estado)}
            ${p.es_critico ? ' · <span class="badge badge-critico">CRITICO</span>' : ''}
        `;
    }

    function renderItems() {
        const puedeEditar = editable();
        if (guardarBtn) guardarBtn.disabled = !puedeEditar;
        if (addInput) addInput.disabled = !puedeEditar;

        if (state.items.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="muted" style="text-align:center;">Sin items</td></tr>';
            return;
        }

        // El hemograma se muestra con su layout (serie roja/blanca + formula
        // leucocitaria con absoluto en vivo); el resto, fila plana normal.
        const codigosHemo = HEMOGRAMA.codigos();
        const porCod = {};
        const otros = [];
        for (const it of state.items) {
            const c = String(it.determinacion_codigo || '').toUpperCase();
            if (codigosHemo.includes(c)) porCod[c] = it;
            else otros.push(it);
        }

        // Agrupar los items restantes en bloques: los de un mismo perfil van
        // juntos (sin separadores internos); cada analisis suelto es un bloque.
        const bloques = [];
        for (const it of otros) {
            const key = it.perfil_id ? `per-${it.perfil_id}` : null;
            const ult = bloques[bloques.length - 1];
            if (key !== null && ult && ult.key === key) ult.items.push(it);
            else bloques.push({ key, perfilNombre: it.perfil_nombre || null, items: [it] });
        }

        let html = '';
        if (Object.keys(porCod).length > 0) html += renderHemograma(porCod, puedeEditar);
        for (const b of bloques) {
            html += filaSeparadora();
            if (b.key !== null) {
                html += `<tr><td colspan="7" style="background:#e8eef5;font-weight:600;padding:4px 8px;">${esc(String(b.perfilNombre || 'Perfil').toUpperCase())}</td></tr>`;
            }
            for (const it of b.items) html += filaNormal(it, puedeEditar, b.key === null);
        }
        tbody.innerHTML = html || '<tr><td colspan="7" class="muted" style="text-align:center;">Sin items</td></tr>';

        recomputeAbsolutos();
    }

    // Linea de otro color entre bloques para separar un analisis del siguiente.
    function filaSeparadora() {
        return '<tr aria-hidden="true"><td colspan="7" style="padding:0;border-top:2px solid #b9c7d6;"></td></tr>';
    }

    // Fila estandar (no hemograma): nombre + valor + unidad + ref + sacar.
    // conFondo: fondo suave en el titulo de los analisis sueltos (los de un
    // perfil van bajo el titulo del perfil, sin fondo propio).
    function filaNormal(it, puedeEditar, conFondo = false) {
        const r = state.resultados[it.id];
        const disabled = puedeEditar ? '' : 'disabled';
        let marca = '';
        if (r) {
            if (r.es_critico) marca = '<span class="badge badge-critico">CRITICO</span>';
            else if (r.es_anormal) marca = '<span class="badge badge-anormal">ANORMAL</span>';
        }
        const quitar = puedeEditar
            ? `<button type="button" class="btn btn-ghost btn-sm" data-accion="quitar-item" data-item-id="${it.id}" title="Sacar este analisis del pedido"><i class="bi bi-trash"></i> Sacar</button>`
            : '';
        const fondo = conFondo ? 'background:#f1f5f9;' : '';
        return `
        <tr data-item-id="${it.id}">
            <td style="cursor:pointer;${fondo}" title="Tocá el análisis para ver sus resultados anteriores">
                <strong>${esc(it.determinacion_nombre || '-')}</strong>
                ${it.determinacion_codigo ? `<div class="muted" style="font-size:.78rem;">${esc(it.determinacion_codigo)}</div>` : ''}
                ${fmtAnteriores(it)}
            </td>
            <td><input type="text" name="valor" value="${esc(valorParaInput(r))}" ${disabled} placeholder="valor" inputmode="decimal"></td>
            <td>${esc(it.unidad || '')}</td>
            <td class="muted" style="white-space: pre-line;">${esc(fmtRango(r) || fmtRango(it))}</td>
            <td>${badge(it.estado)}</td>
            <td>${marca}</td>
            <td>${quitar}</td>
        </tr>`;
    }

    function marcaDe(it) {
        const r = state.resultados[it.id];
        if (!r) return '';
        if (r.es_critico) return '<span class="badge badge-critico">CRITICO</span>';
        if (r.es_anormal) return '<span class="badge badge-anormal">ANORMAL</span>';
        return '';
    }

    // Boton para sacar un analisis del pedido (tambien en las filas del hemograma).
    function btnSacar(it, puedeEditar) {
        return puedeEditar
            ? `<button type="button" class="btn btn-ghost btn-sm" data-accion="quitar-item" data-item-id="${it.id}" title="Sacar este analisis del pedido"><i class="bi bi-trash"></i> Sacar</button>`
            : '';
    }

    // Fila de serie roja/blanca: igual que la normal pero con etiqueta de
    // presentacion, negrita en los "header" (GR/GB) e indentacion en indices.
    function filaSerieHemo(it, label, { indent, bold, cod }, puedeEditar) {
        const r = state.resultados[it.id];
        const disabled = puedeEditar ? '' : 'disabled';
        const estilo = `${indent ? 'padding-left:16px;' : ''}${bold ? 'font-weight:600;' : ''}`;
        return `
        <tr data-item-id="${it.id}">
            <td style="cursor:pointer;" title="Tocá el análisis para ver sus resultados anteriores">
                <span style="${estilo}">${esc(label)}</span>
                ${fmtAnteriores(it)}
            </td>
            <td><input type="text" name="valor" data-cod="${esc(cod)}" value="${esc(valorParaInput(r))}" ${disabled} placeholder="valor" inputmode="decimal"></td>
            <td>${esc(it.unidad || '')}</td>
            <td class="muted" style="white-space: pre-line;">${esc(fmtRango(r) || fmtRango(it))}</td>
            <td>${badge(it.estado)}</td>
            <td>${marcaDe(it)}</td>
            <td>${btnSacar(it, puedeEditar)}</td>
        </tr>`;
    }

    // Fila de la formula leucocitaria: el input es el RELATIVO (%); el absoluto
    // se calcula en vivo (globulos blancos x % / 100) y se muestra al lado.
    function filaFormulaHemo(it, label, cod, puedeEditar) {
        const r = state.resultados[it.id];
        const disabled = puedeEditar ? '' : 'disabled';
        return `
        <tr data-item-id="${it.id}">
            <td style="cursor:pointer;" title="Tocá el análisis para ver sus resultados anteriores">
                <span style="padding-left:16px;">${esc(label)}</span>
                ${fmtAnteriores(it)}
            </td>
            <td><input type="text" name="valor" data-cod="${esc(cod)}" value="${esc(valorParaInput(r))}" ${disabled} placeholder="%" inputmode="decimal"></td>
            <td class="muted">% &nbsp;<span style="white-space:nowrap;">abs: <strong class="hemo-abs" data-cod="${esc(cod)}">—</strong> /mm³</span></td>
            <td class="muted" style="white-space: pre-line;">${esc(fmtRango(r) || fmtRango(it))}</td>
            <td>${badge(it.estado)}</td>
            <td>${marcaDe(it)}</td>
            <td>${btnSacar(it, puedeEditar)}</td>
        </tr>`;
    }

    function renderHemograma(porCod, puedeEditar) {
        const sec = (txt) => `<tr><td colspan="7" style="background:#e8eef5;font-weight:600;padding:4px 8px;"><strong>${txt}</strong></td></tr>`;
        let h = sec('HEMOGRAMA');

        if (porCod[HEMOGRAMA.serieRojaHeader]) {
            h += filaSerieHemo(porCod.GR, HEMOGRAMA.labels.GR, { bold: true, cod: 'GR' }, puedeEditar);
        }
        for (const c of HEMOGRAMA.serieRojaIndices) {
            if (porCod[c]) h += filaSerieHemo(porCod[c], HEMOGRAMA.labels[c], { indent: true, cod: c }, puedeEditar);
        }
        if (porCod[HEMOGRAMA.serieBlancaHeader]) {
            h += filaSerieHemo(porCod.GB, HEMOGRAMA.labels.GB, { bold: true, cod: 'GB' }, puedeEditar);
        }

        const tieneFormula = HEMOGRAMA.formula.some((c) => porCod[c]);
        if (tieneFormula) {
            h += `<tr><td colspan="7" style="padding-top:6px;"><strong>FÓRMULA LEUCOCITARIA</strong> <span class="muted" style="font-size:.78rem;">(cargás el Relativo %; el Absoluto /mm³ se calcula solo. La suma debe dar 100%)</span> <span class="hemo-suma" style="font-size:.78rem;font-weight:600;"></span></td></tr>`;
            for (const c of HEMOGRAMA.formula) {
                if (porCod[c]) h += filaFormulaHemo(porCod[c], HEMOGRAMA.labels[c], c, puedeEditar);
            }
        }
        return h;
    }

    // Recalcula los absolutos de la formula a partir del valor (en vivo) de GB.
    function recomputeAbsolutos() {
        const gbInput = tbody.querySelector('input[name="valor"][data-cod="GB"]');
        const gb = gbInput ? parseFloat(String(gbInput.value || '').replace(',', '.')) : NaN;
        tbody.querySelectorAll('.hemo-abs').forEach((span) => {
            const cod = span.dataset.cod;
            const relInput = tbody.querySelector(`input[name="valor"][data-cod="${cod}"]`);
            const rel = relInput ? parseFloat(String(relInput.value || '').replace(',', '.')) : NaN;
            span.textContent = (!Number.isNaN(gb) && !Number.isNaN(rel))
                ? Math.round(gb * rel / 100).toLocaleString('es-AR')
                : '—';
        });
        actualizarSumaFormula();
    }

    // Suma de los relativos (%) cargados en la formula leucocitaria.
    // Devuelve null si no hay ninguno cargado.
    function sumaFormula() {
        let suma = 0;
        let alguno = false;
        for (const c of HEMOGRAMA.formula) {
            const inp = tbody.querySelector(`input[name="valor"][data-cod="${c}"]`);
            if (!inp) continue;
            const raw = String(inp.value || '').trim();
            if (raw === '') continue;
            const n = parseFloat(raw.replace(',', '.'));
            if (Number.isNaN(n)) continue;
            alguno = true;
            suma += n;
        }
        return alguno ? suma : null;
    }

    // Indicador en vivo de la suma de la formula (verde si da 100, rojo si no).
    function actualizarSumaFormula() {
        const span = tbody.querySelector('.hemo-suma');
        if (!span) return;
        const suma = sumaFormula();
        if (suma === null) {
            span.textContent = '';
            return;
        }
        const ok = Math.abs(suma - 100) < 0.01;
        span.textContent = `Σ = ${String(Math.round(suma * 100) / 100).replace('.', ',')}%`;
        span.style.color = ok ? '#1e7e34' : '#c0392b';
    }

    // ---------- guardar resultados ----------
    function leerValorFila(tr) {
        const input = tr.querySelector('input[name="valor"]');
        if (!input || input.disabled) return null;
        const raw = (input.value || '').trim();
        if (raw === '') return null;
        const itemId = parseInt(tr.dataset.itemId, 10);
        const numerico = raw.replace(',', '.');
        const payload = { pedido_item_id: itemId };
        if (/^-?\d+(\.\d+)?$/.test(numerico)) payload.valor_numerico = parseFloat(numerico);
        else payload.valor_texto = raw;
        return payload;
    }

    async function guardarTodos() {
        if (!state.pedidoId) return;
        const aGuardar = Array.from(tbody.querySelectorAll('tr[data-item-id]'))
            .map(leerValorFila)
            .filter((p) => p !== null);

        if (aGuardar.length === 0) {
            msg('No hay valores cargados para guardar', 'error');
            return;
        }

        // La formula leucocitaria es porcentual: si hay valores cargados,
        // la suma tiene que dar exactamente 100 para poder guardar.
        const suma = sumaFormula();
        if (suma !== null && Math.abs(suma - 100) >= 0.01) {
            msg(`La fórmula leucocitaria debe sumar 100% y suma ${String(Math.round(suma * 100) / 100).replace('.', ',')}%. Corregí los valores antes de guardar.`, 'error');
            return;
        }

        if (guardarBtn) guardarBtn.disabled = true;
        let ok = 0;
        const errores = [];
        for (const payload of aGuardar) {
            try {
                await api.post('/api/resultados', payload);
                ok++;
            } catch (err) {
                errores.push(`item ${payload.pedido_item_id}: ${err.message}`);
            }
        }
        if (guardarBtn) guardarBtn.disabled = false;

        await reload();
        onChange?.();

        if (errores.length === 0) msg(`${ok} resultado(s) guardado(s)`, 'exito');
        else msg(`Guardados ${ok}, con error ${errores.length}: ${errores.join(' · ')}`, 'error');
    }

    // ---------- informes (emitir / ver-imprimir / subir a la red) ----------
    let informeBar = null;

    function crearInformeBar() {
        if (!guardarBtn || informeBar) return;
        informeBar = document.createElement('span');
        informeBar.style.cssText = 'display:inline-flex;gap:.5rem;flex-wrap:wrap;align-items:center;';
        informeBar.innerHTML = `
            <button type="button" class="btn" data-informe="emitir" title="Emite el informe y abre el PDF para verlo / imprimirlo"><i class="bi bi-printer"></i> Emitir / Imprimir informe</button>
            <button type="button" class="btn" data-informe="red" hidden><i class="bi bi-cloud-arrow-up"></i> Subir a la red</button>
            <button type="button" class="btn" data-informe="wa" title="Abre WhatsApp con un mensaje para avisarle al paciente que estan los resultados"><i class="bi bi-whatsapp"></i> Avisar por WhatsApp</button>`;
        guardarBtn.parentElement?.appendChild(informeBar);
        informeBar.querySelector('[data-informe="emitir"]').addEventListener('click', emitirInforme);
        informeBar.querySelector('[data-informe="red"]').addEventListener('click', subirALaRed);
        informeBar.querySelector('[data-informe="wa"]').addEventListener('click', avisarWhatsapp);
    }

    // Abre WhatsApp con el mensaje de "resultados listos". Usa el telefono del
    // paciente vinculado; si no hay, lo pide (o deja elegir el contacto).
    async function avisarWhatsapp() {
        const snap = state.pedido?.snapshot_paciente;
        const nombre = (snap && typeof snap === 'object' && snap.nombre) ? String(snap.nombre) : '';
        let tel = '';
        const pacId = state.pedido?.paciente_id;
        if (pacId) {
            try {
                const pac = await api.get(`/api/pacientes?accion=obtener&id=${pacId}`);
                tel = String(pac?.telefono ?? '');
            } catch {
                // sin telefono: se pide a mano
            }
        }
        if (!tel.replace(/\D/g, '')) {
            const ingresado = prompt('Número de WhatsApp del paciente (con código de país y área, ej: 5493511234567):', '');
            if (ingresado === null) return;
            tel = ingresado;
        }
        const digits = tel.replace(/\D/g, '');
        const msg = `Hola${nombre ? ' ' + nombre : ''}! Le avisamos del laboratorio que ya están listos los resultados de sus análisis. Puede pasar a retirarlos. ¡Gracias!`;
        const url = digits
            ? `https://wa.me/${digits}?text=${encodeURIComponent(msg)}`
            : `https://wa.me/?text=${encodeURIComponent(msg)}`;
        window.open(url, '_blank');
    }

    // Muestra/oculta Subir a la red segun si hay informe emitido.
    async function refrescarInformes() {
        if (!informeBar || !state.pedidoId) return;
        let ultimo = null;
        try {
            const r = await api.get(`/api/informes?pedido_id=${state.pedidoId}`);
            const informes = r?.informes ?? [];
            ultimo = informes.length > 0 ? informes[0] : null; // vienen DESC
        } catch {
            // sin informes o error: se oculta el boton dependiente
        }
        const redBtn = informeBar.querySelector('[data-informe="red"]');
        if (ultimo) {
            redBtn.hidden = false;
            redBtn.dataset.informeId = String(ultimo.id);
        } else {
            redBtn.hidden = true;
        }
    }

    // Hay valores tipeados que difieren de lo guardado en el servidor.
    function hayValoresSinGuardar() {
        return Array.from(tbody.querySelectorAll('tr[data-item-id] input[name="valor"]:not([disabled])')).some((inp) => {
            const tr = inp.closest('tr[data-item-id]');
            const guardado = String(valorParaInput(state.resultados[tr.dataset.itemId]) ?? '');
            const actual = (inp.value || '').trim();
            return actual !== '' && actual !== guardado;
        });
    }

    async function emitirInforme() {
        if (!state.pedidoId || !informeBar) return;
        if (hayValoresSinGuardar()
            && !confirm('Tenés valores cargados SIN GUARDAR que no van a salir en el informe. ¿Emitir igual?')) {
            return;
        }
        const btn = informeBar.querySelector('[data-informe="emitir"]');
        btn.disabled = true;
        try {
            let r;
            try {
                r = await api.post('/api/informes', { pedido_id: state.pedidoId });
            } catch (err) {
                // Si faltan resultados el backend rechaza el informe completo:
                // se ofrece emitir un parcial con lo que este cargado.
                if (err.status === 409 && /sin resultado/i.test(err.message)) {
                    if (!confirm('Hay análisis sin resultado. ¿Emitir un informe PARCIAL con lo cargado?')) return;
                    r = await api.post('/api/informes', { pedido_id: state.pedidoId, es_parcial: true });
                } else {
                    throw err;
                }
            }
            msg(`Informe ${r.numero} emitido${r.es_parcial ? ' (parcial)' : ''}`, 'exito');
            await refrescarInformes();
            onChange?.();
            window.open(labPath(`/api/informes?id=${r.id}&download=1`), '_blank');
        } catch (err) {
            msg(`No se pudo emitir el informe: ${err.message}`, 'error');
        } finally {
            btn.disabled = false;
        }
    }

    async function subirALaRed() {
        if (!informeBar) return;
        const btn = informeBar.querySelector('[data-informe="red"]');
        const id = parseInt(btn.dataset.informeId || '', 10);
        if (!(id > 0)) return;
        btn.disabled = true;
        try {
            const r = await api.post(`/api/informes?accion=subir-red&id=${id}`, {});
            msg(`Informe subido a la red: ${r.destino}`, 'exito');
        } catch (err) {
            msg(`No se pudo subir a la red: ${err.message}`, 'error');
        } finally {
            btn.disabled = false;
        }
    }

    // ---------- quitar item ----------
    async function quitarItem(itemId) {
        if (!(itemId > 0) || !state.pedidoId) return;
        const tieneResultado = !!state.resultados[itemId];
        const aviso = tieneResultado
            ? '¿Sacar este analisis del pedido? Tiene un resultado cargado que tambien se borrara.'
            : '¿Sacar este analisis del pedido?';
        if (!confirm(aviso)) return;
        try {
            await api.post(`/api/pedidos?accion=quitar_item&id=${state.pedidoId}`, { pedido_item_id: itemId });
            await reload();
            onChange?.();
            msg('Analisis quitado del pedido', 'exito');
        } catch (err) {
            msg(`No se pudo quitar: ${err.message}`, 'error');
        }
    }

    // ---------- agregar analisis ----------
    async function getCatalogo() {
        if (state.catalogo) return state.catalogo;
        const [det, perf] = await Promise.all([
            api.get('/api/determinaciones'),
            api.get('/api/perfiles'),
        ]);
        state.catalogo = {
            determinaciones: det?.determinaciones ?? [],
            perfiles: perf?.perfiles ?? [],
        };
        return state.catalogo;
    }

    function ocultarAdd() {
        if (!addList) return;
        addList.hidden = true;
        addList.innerHTML = '';
    }

    function matches(cat, q) {
        const t = q.toLowerCase().trim();
        if (!t) return [];
        const dets = cat.determinaciones
            .filter((d) => d.nombre.toLowerCase().includes(t) || String(d.codigo).toLowerCase().includes(t))
            .map((d) => ({ tipo: 'det', id: d.id, codigo: d.codigo, nombre: d.nombre }));
        const perfs = cat.perfiles
            .filter((p) => p.nombre.toLowerCase().includes(t) || String(p.codigo).toLowerCase().includes(t))
            .map((p) => ({ tipo: 'per', id: p.id, codigo: p.codigo, nombre: p.nombre }));
        return [...dets, ...perfs];
    }

    async function renderAddSugerencias(q) {
        if (!addList) return [];
        const cat = await getCatalogo();
        const res = matches(cat, q).slice(0, 12);
        if (res.length === 0) {
            addList.innerHTML = '<li class="placeholder">Sin resultados</li>';
            addList.hidden = false;
            return [];
        }
        addList.innerHTML = res.map((x) => `
            <li data-tipo="${x.tipo}" data-id="${x.id}">
                <span><span class="tag">${x.tipo === 'det' ? 'Det.' : 'Perfil'}</span> <strong>${esc(x.codigo)}</strong> · ${esc(x.nombre)}</span>
            </li>`).join('');
        addList.hidden = false;
        return res;
    }

    async function agregar(tipo, id) {
        if (!state.pedidoId) return;
        const item = tipo === 'det' ? { determinacion_id: id } : { perfil_id: id };
        try {
            const r = await api.post(`/api/pedidos?accion=agregar_items&id=${state.pedidoId}`, { items: [item] });
            if (addInput) addInput.value = '';
            ocultarAdd();
            await reload();
            onChange?.();
            msg(r.agregados > 0 ? 'Analisis agregado' : 'Ya estaba en el pedido', r.agregados > 0 ? 'exito' : 'error');
        } catch (err) {
            msg(`No se pudo agregar: ${err.message}`, 'error');
        }
    }

    // ---------- wiring de eventos ----------
    // Click delegado en el tbody: quitar item, o tocar el nombre del analisis
    // para abrir/cerrar el cuadro con sus resultados anteriores.
    tbody.addEventListener('click', (e) => {
        const btn = e.target.closest('button[data-accion="quitar-item"]');
        if (btn) {
            quitarItem(parseInt(btn.dataset.itemId, 10));
            return;
        }
        if (e.target.closest('[data-anteriores]')) return; // click dentro del cuadro
        const td = e.target.closest('td');
        const tr = e.target.closest('tr[data-item-id]');
        if (!td || !tr || td.cellIndex !== 0) return;
        const box = tr.querySelector('[data-anteriores]');
        if (!box) return;
        const abrir = box.hidden;
        tbody.querySelectorAll('[data-anteriores]').forEach((b) => { b.hidden = true; });
        box.hidden = !abrir;
    });

    // Recalcular absolutos de la formula leucocitaria al tipear (GB o %).
    // Los casilleros de la formula solo aceptan numeros (%, con coma o punto).
    tbody.addEventListener('input', (e) => {
        if (!e.target || e.target.name !== 'valor') return;
        if (HEMOGRAMA.formula.includes(e.target.dataset.cod)) {
            const limpio = e.target.value.replace(/[^0-9.,]/g, '');
            if (limpio !== e.target.value) e.target.value = limpio;
        }
        recomputeAbsolutos();
    });

    // Enter en un valor: salta al siguiente input (carga rapida sin mouse).
    tbody.addEventListener('keydown', (e) => {
        if (e.key !== 'Enter' || e.target.name !== 'valor') return;
        e.preventDefault();
        const inputs = Array.from(tbody.querySelectorAll('input[name="valor"]:not([disabled])'));
        const idx = inputs.indexOf(e.target);
        if (idx >= 0 && idx < inputs.length - 1) {
            inputs[idx + 1].focus();
            inputs[idx + 1].select();
        } else {
            e.target.blur();
        }
    });

    guardarBtn?.addEventListener('click', guardarTodos);

    // Buscador de "agregar analisis".
    let addTimer = null;
    addInput?.addEventListener('input', () => {
        const q = addInput.value.trim();
        clearTimeout(addTimer);
        if (q.length < 2) { ocultarAdd(); return; }
        addTimer = setTimeout(() => renderAddSugerencias(q), 250);
    });

    // Enter en el buscador de agregar: agrega el primer match directamente.
    addInput?.addEventListener('keydown', async (e) => {
        if (e.key !== 'Enter') return;
        e.preventDefault();
        const q = addInput.value.trim();
        if (q.length < 2) return;
        const res = await renderAddSugerencias(q);
        if (res.length > 0) agregar(res[0].tipo, res[0].id);
    });

    addList?.addEventListener('click', (e) => {
        const li = e.target.closest('li[data-id]');
        if (!li) return;
        agregar(li.dataset.tipo, parseInt(li.dataset.id, 10));
    });

    document.addEventListener('click', (e) => {
        if (addList && !addList.contains(e.target) && e.target !== addInput) ocultarAdd();
    });

    return { open: load, reload, getEstado: () => state.estado };
}
