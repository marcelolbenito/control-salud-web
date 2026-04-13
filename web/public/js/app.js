(function () {
  if (typeof simpleDatatables === 'undefined') {
    return;
  }

  var DataTable = simpleDatatables.DataTable;
  var opts = {
    perPage: 15,
    perPageSelect: [10, 15, 25, 50, 100],
    labels: {
      placeholder: 'Buscar…',
      perPage: 'por página',
      noRows: 'No hay filas para mostrar',
      info: 'Mostrando {start}–{end} de {rows}',
      noResults: 'Sin coincidencias para el filtro',
    },
  };

  ['#tbl-pacientes', '#tbl-doctores', '#tbl-agenda', '#tbl-ordenes'].forEach(function (sel) {
    var el = document.querySelector(sel);
    if (el) {
      new DataTable(el, opts);
    }
  });
})();

(function () {
  function shouldIgnoreRowNavigation(target) {
    return Boolean(
      target.closest('a, button, input, select, textarea, label, form')
    );
  }

  document.addEventListener('click', function (event) {
    var row = event.target.closest('tr[data-turno-link]');
    if (!row || shouldIgnoreRowNavigation(event.target)) {
      return;
    }

    var href = row.getAttribute('data-turno-link');
    if (href) {
      window.location.href = href;
    }
  });

  document.addEventListener('keydown', function (event) {
    var row = event.target.closest('tr[data-turno-link]');
    if (!row) {
      return;
    }

    if (event.key !== 'Enter' && event.key !== ' ') {
      return;
    }

    event.preventDefault();
    var href = row.getAttribute('data-turno-link');
    if (href) {
      window.location.href = href;
    }
  });
})();
