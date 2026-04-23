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

  function textFromCell(cell) {
    return (cell.textContent || '').replace(/\s+/g, ' ').trim();
  }

  function safeName(raw) {
    return (raw || 'tabla')
      .toLowerCase()
      .replace(/[^a-z0-9_-]+/g, '_')
      .replace(/^_+|_+$/g, '');
  }

  function ymdCompact() {
    var d = new Date();
    var y = String(d.getFullYear());
    var m = String(d.getMonth() + 1).padStart(2, '0');
    var day = String(d.getDate()).padStart(2, '0');
    return y + m + day;
  }

  function captureTableData(table) {
    var head = Array.from(table.querySelectorAll('thead th')).map(textFromCell);
    if (head.length === 0) {
      head = Array.from(table.querySelectorAll('tbody tr:first-child td')).map(function (_, i) {
        return 'Columna ' + (i + 1);
      });
    }
    var rows = Array.from(table.querySelectorAll('tbody tr')).map(function (tr) {
      return Array.from(tr.querySelectorAll('td')).map(textFromCell);
    });
    return { head: head, rows: rows };
  }

  function exportTableExcel(filenameBase, data) {
    var html = '<table border="1"><thead><tr>';
    data.head.forEach(function (h) {
      html += '<th>' + String(h).replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</th>';
    });
    html += '</tr></thead><tbody>';
    data.rows.forEach(function (row) {
      html += '<tr>';
      row.forEach(function (cell) {
        html += '<td>' + String(cell).replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</td>';
      });
      html += '</tr>';
    });
    html += '</tbody></table>';

    var doc = '<html><head><meta charset="utf-8"></head><body>' + html + '</body></html>';
    var blob = new Blob([doc], { type: 'application/vnd.ms-excel;charset=utf-8;' });
    var url = URL.createObjectURL(blob);
    var a = document.createElement('a');
    a.href = url;
    a.download = filenameBase + '.xls';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
  }

  function printTable(title, data) {
    var w = window.open('', '_blank');
    if (!w) {
      return;
    }
    var html = '<!doctype html><html><head><meta charset="utf-8"><title>' + title + '</title>' +
      '<style>body{font-family:Arial,Helvetica,sans-serif;padding:16px}h1{font-size:18px;margin:0 0 12px}' +
      'table{border-collapse:collapse;width:100%;font-size:12px}th,td{border:1px solid #999;padding:6px;text-align:left;vertical-align:top}' +
      'thead th{background:#f3f4f6}</style></head><body><h1>' + title + '</h1><table><thead><tr>';
    data.head.forEach(function (h) {
      html += '<th>' + String(h).replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</th>';
    });
    html += '</tr></thead><tbody>';
    data.rows.forEach(function (row) {
      html += '<tr>';
      row.forEach(function (cell) {
        html += '<td>' + String(cell).replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</td>';
      });
      html += '</tr>';
    });
    html += '</tbody></table></body></html>';
    w.document.open();
    w.document.write(html);
    w.document.close();
    w.focus();
    w.print();
  }

  function addTableToolbar(table, snapshot) {
    var wrap = table.closest('.table-wrap-datatable');
    if (!wrap || wrap.previousElementSibling && wrap.previousElementSibling.classList.contains('dt-export-toolbar')) {
      return;
    }
    var baseName = safeName(table.id || 'tabla');
    var fullName = baseName + '_' + ymdCompact();

    var bar = document.createElement('div');
    bar.className = 'dt-export-toolbar';
    bar.style.display = 'flex';
    bar.style.gap = '0.5rem';
    bar.style.justifyContent = 'flex-end';
    bar.style.margin = '0.25rem 0 0.5rem';

    var btnExcel = document.createElement('button');
    btnExcel.type = 'button';
    btnExcel.className = 'btn btn-ghost btn-sm';
    btnExcel.textContent = 'Exportar Excel';
    btnExcel.addEventListener('click', function () {
      exportTableExcel(fullName, snapshot);
    });

    var btnPrint = document.createElement('button');
    btnPrint.type = 'button';
    btnPrint.className = 'btn btn-ghost btn-sm';
    btnPrint.textContent = 'Imprimir';
    btnPrint.addEventListener('click', function () {
      var t = document.title ? document.title + ' · ' + (table.id || 'Tabla') : (table.id || 'Tabla');
      printTable(t, snapshot);
    });

    bar.appendChild(btnExcel);
    bar.appendChild(btnPrint);
    wrap.parentNode.insertBefore(bar, wrap);
  }

  document.querySelectorAll('.table-wrap-datatable table').forEach(function (el) {
    var snapshot = captureTableData(el);
    if (el.id) {
      new DataTable(el, opts);
    }
    addTableToolbar(el, snapshot);
  });
})();

(function () {
  var agendaNavTimer = null;

  function shouldIgnoreRowNavigation(target) {
    if (target.closest('button, input, select, textarea, label, form')) {
      return true;
    }
    var a = target.closest('a');
    if (a && !a.classList.contains('agenda-paciente-link')) {
      return true;
    }
    return false;
  }

  function clearAgendaNavTimer() {
    if (agendaNavTimer !== null) {
      clearTimeout(agendaNavTimer);
      agendaNavTimer = null;
    }
  }

  document.addEventListener('click', function (event) {
    var row = event.target.closest('tr[data-turno-link]');
    if (!row || shouldIgnoreRowNavigation(event.target)) {
      return;
    }

    if (event.target.closest('a.agenda-paciente-link')) {
      event.preventDefault();
    }

    var href = row.getAttribute('data-turno-link');
    if (!href) {
      return;
    }

    if (event.detail >= 2) {
      clearAgendaNavTimer();
      var llegoInput = row.querySelector('input[name="accion"][value="llego"]');
      var form = llegoInput && llegoInput.closest('form');
      if (form) {
        form.submit();
      } else {
        window.location.href = href;
      }
      return;
    }

    clearAgendaNavTimer();
    agendaNavTimer = setTimeout(function () {
      agendaNavTimer = null;
      window.location.href = href;
    }, 420);
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
    clearAgendaNavTimer();
    var href = row.getAttribute('data-turno-link');
    if (href) {
      window.location.href = href;
    }
  });
})();
