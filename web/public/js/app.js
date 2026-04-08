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

  ['#tbl-pacientes', '#tbl-doctores', '#tbl-agenda'].forEach(function (sel) {
    var el = document.querySelector(sel);
    if (el) {
      new DataTable(el, opts);
    }
  });
})();
