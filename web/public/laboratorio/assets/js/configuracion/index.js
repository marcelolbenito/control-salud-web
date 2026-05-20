/**
 * Configuración del laboratorio.
 */
import { api, labPath } from '../api.js';

const ENDPOINT = '/api/lab-config';

const form = document.getElementById('form-config');
const msg = document.getElementById('config-msg');
const logoActual = document.getElementById('logo-actual');
const firmaActual = document.getElementById('firma-actual');

const CLAVES = [
    'laboratorio_nombre',
    'laboratorio_subtitulo',
    'laboratorio_direccion',
    'laboratorio_telefono',
    'laboratorio_resolucion_colegio',
    'laboratorio_resolucion_vencimiento',
    'laboratorio_registro_sisa_codigo',
    'laboratorio_registro_sisa_razon_social',
    'tecnico_principal_nombre',
    'tecnico_principal_titulo',
    'firmante_apellido',
    'firmante_nombres',
    'firmante_matricula',
    'firmante_titulo',
];

async function cargar() {
    try {
        const datos = await api.get(ENDPOINT);
        for (const clave of CLAVES) {
            const input = form.querySelector(`[name="${clave}"]`);
            if (input && typeof datos[clave] === 'string') {
                input.value = datos[clave];
            }
        }
        if (datos.laboratorio_logo_path) {
            logoActual.textContent = `Logo actual: ${datos.laboratorio_logo_path}`;
        }
        if (datos.firmante_firma_path && firmaActual) {
            firmaActual.textContent = `Firma actual: ${datos.firmante_firma_path}`;
        }
    } catch (err) {
        mostrarMsg('No se pudo cargar la configuración: ' + (err.message || ''), 'error');
    }
}

async function subirArchivo(fieldName, accion, mensajeError) {
    const fileInput = form.querySelector(`[name="${fieldName}"]`);
    if (!fileInput || !fileInput.files || fileInput.files.length === 0) return true;

    const fd = new FormData();
    fd.append(fieldName, fileInput.files[0]);
    const resp = await fetch(labPath(`${ENDPOINT}?accion=${accion}`), { method: 'POST', body: fd });
    const json = await resp.json();
    if (!resp.ok || !json.success) {
        mostrarMsg(`${mensajeError}: ` + (json?.error?.message ?? `HTTP ${resp.status}`), 'error');
        return false;
    }
    return true;
}

form.addEventListener('submit', async (e) => {
    e.preventDefault();
    mostrarMsg('', null);

    try {
        if (!await subirArchivo('logo', 'subir_logo', 'No se pudo subir el logo')) return;
        if (!await subirArchivo('firma', 'subir_firma', 'No se pudo subir la firma')) return;

        const data = {};
        for (const clave of CLAVES) {
            const input = form.querySelector(`[name="${clave}"]`);
            if (input) data[clave] = input.value;
        }

        const resp2 = await fetch(labPath(ENDPOINT), {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data),
        });
        const json2 = await resp2.json();
        if (!resp2.ok || !json2.success) {
            mostrarMsg('Error al guardar: ' + (json2?.error?.message ?? `HTTP ${resp2.status}`), 'error');
            return;
        }
        mostrarMsg('Configuración guardada.', 'ok');
        cargar();
    } catch (err) {
        mostrarMsg(err.message || 'Error de red', 'error');
    }
});

function mostrarMsg(texto, clase) {
    if (!texto) {
        msg.hidden = true;
        msg.className = '';
        msg.textContent = '';
        return;
    }
    msg.textContent = texto;
    msg.className = clase || '';
    msg.hidden = false;
}

cargar();
