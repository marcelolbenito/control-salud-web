import { api } from '/assets/js/api.js';

const $ = (id) => document.getElementById(id);

const fmtMoney = (n) => '$ ' + Number(n || 0).toLocaleString('es-AR', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
});

const NOMBRES_MES = [
    'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
    'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre',
];

function nombreMes(yyyymm) {
    if (!yyyymm) return '';
    const [y, m] = yyyymm.split('-');
    return `${NOMBRES_MES[parseInt(m, 10) - 1]} ${y}`;
}

function mesActualYYYYMM() {
    const d = new Date();
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`;
}

async function cargar(mes) {
    try {
        const params = new URLSearchParams();
        if (mes) params.set('mes', mes);
        params.set('meses', '12');
        const data = await api.get(`/api/reportes${params.toString() ? '?' + params : ''}`);

        renderMes(data.mes_actual);
        renderSerie(data.serie ?? []);
    } catch (e) {
        renderError(e.message || 'Error al cargar reportes');
    }
}

function renderMes(m) {
    $('rep-titulo-mes').textContent = `Recaudacion de ${nombreMes(m.mes)}`;
    $('rep-total').textContent       = fmtMoney(m.total);
    $('rep-particular').textContent  = fmtMoney(m.particular);
    $('rep-obra-social').textContent = fmtMoney(m.obra_social);
    $('rep-cantidad').textContent    = String(m.cantidad_pagos);
}

function renderSerie(serie) {
    const tbody = $('rep-tbody-serie');
    if (!serie.length) {
        tbody.innerHTML = '<tr><td colspan="5" class="muted" style="text-align:center; padding: 1.5rem;">Sin pagos registrados.</td></tr>';
        return;
    }
    tbody.innerHTML = serie.map((r) => `
        <tr>
            <td><strong>${nombreMes(r.mes)}</strong></td>
            <td style="text-align: right;">${fmtMoney(r.particular)}</td>
            <td style="text-align: right;">${fmtMoney(r.obra_social)}</td>
            <td style="text-align: right;"><strong>${fmtMoney(r.total)}</strong></td>
            <td style="text-align: right;">${r.cantidad_pagos}</td>
        </tr>
    `).join('');
}

function renderError(msg) {
    $('rep-tbody-serie').innerHTML = `
        <tr><td colspan="5" style="text-align:center; padding: 1rem;">
            <span class="alert alert-error" style="display:inline-block; padding: 0.4rem 0.8rem;">${msg}</span>
        </td></tr>`;
}

const inicial = mesActualYYYYMM();
$('rep-mes').value = inicial;
cargar(inicial);

$('rep-form').addEventListener('submit', (e) => {
    e.preventDefault();
    cargar($('rep-mes').value || mesActualYYYYMM());
});

$('rep-btn-actual').addEventListener('click', () => {
    const mes = mesActualYYYYMM();
    $('rep-mes').value = mes;
    cargar(mes);
});
