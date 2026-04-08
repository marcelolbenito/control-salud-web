(function () {
  var dataEl = document.getElementById('dashboard-data');
  var chartLine = document.getElementById('chart-turnos-dia');
  var chartDoughnut = document.getElementById('chart-estado-hoy');
  var chartBar = document.getElementById('chart-doctores');

  if (!dataEl || typeof Chart === 'undefined') {
    return;
  }

  var data;
  try {
    data = JSON.parse(dataEl.textContent);
  } catch (e) {
    return;
  }

  Chart.defaults.font.family = '"DM Sans", "Segoe UI", system-ui, sans-serif';
  Chart.defaults.color = '#64748b';

  if (chartLine && data.labels14 && data.turnos14) {
    new Chart(chartLine, {
      type: 'line',
      data: {
        labels: data.labels14,
        datasets: [
          {
            label: 'Turnos',
            data: data.turnos14,
            borderColor: '#0d9488',
            backgroundColor: 'rgba(13, 148, 136, 0.12)',
            fill: true,
            tension: 0.35,
            borderWidth: 2,
            pointRadius: 3,
            pointHoverRadius: 5,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { intersect: false, mode: 'index' },
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              title: function (items) {
                return items.length ? String(items[0].label) : '';
              },
            },
          },
        },
        scales: {
          x: { grid: { display: false } },
          y: {
            beginAtZero: true,
            ticks: { precision: 0 },
          },
        },
      },
    });
  }

  if (chartDoughnut && data.estadoTotal > 0 && data.estadoLabels && data.estadoCounts) {
    var estadoColors = {
      pendiente: '#f59e0b',
      atendido: '#10b981',
      cancelado: '#94a3b8',
      no_asistio: '#ef4444',
    };
    var bg = data.estadoLabels.map(function (lbl) {
      var k = String(lbl)
        .toLowerCase()
        .replace(/\s+/g, '_');
      return estadoColors[k] || '#0ea5e9';
    });

    new Chart(chartDoughnut, {
      type: 'doughnut',
      data: {
        labels: data.estadoLabels,
        datasets: [
          {
            data: data.estadoCounts,
            backgroundColor: bg,
            borderWidth: 2,
            borderColor: '#fff',
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'bottom',
            labels: { boxWidth: 12, padding: 12 },
          },
        },
      },
    });
  } else if (chartDoughnut) {
    var wrap = chartDoughnut.parentElement;
    if (wrap) {
      wrap.classList.add('dash-chart-empty');
      wrap.innerHTML =
        '<p class="dash-empty-msg"><i class="bi bi-calendar-x" aria-hidden="true"></i> No hay turnos para hoy. Revisá la <a href="/agenda.php">agenda</a>.</p>';
    }
  }

  if (chartBar && data.doctorLabels && data.doctorCounts && data.doctorLabels.length > 0) {
    new Chart(chartBar, {
      type: 'bar',
      data: {
        labels: data.doctorLabels,
        datasets: [
          {
            label: 'Turnos (30 días)',
            data: data.doctorCounts,
            backgroundColor: 'rgba(13, 148, 136, 0.75)',
            borderColor: '#0f766e',
            borderWidth: 1,
            borderRadius: 6,
          },
        ],
      },
      options: {
        indexAxis: 'y',
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
        },
        scales: {
          x: {
            beginAtZero: true,
            ticks: { precision: 0 },
          },
          y: {
            grid: { display: false },
            ticks: { autoSkip: false },
          },
        },
      },
    });
  } else if (chartBar) {
    var barWrap = chartBar.parentElement;
    if (barWrap) {
      barWrap.classList.add('dash-chart-empty');
      barWrap.innerHTML =
        '<p class="dash-empty-msg"><i class="bi bi-person-x" aria-hidden="true"></i> Sin turnos en los últimos 30 días para graficar por profesional.</p>';
    }
  }
})();
