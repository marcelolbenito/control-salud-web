(function () {
  function normalize(s) {
    return String(s || '').toLowerCase().replace(/\s+/g, ' ').trim();
  }

  function findMatch(raw, options, strict, soloCodigo) {
    var q = normalize(raw);
    if (!q) {
      return null;
    }

    var i;
    for (i = 0; i < options.length; i++) {
      if (normalize(options[i].value) === q) {
        return options[i];
      }
    }

    for (i = 0; i < options.length; i++) {
      if (normalize(options[i].getAttribute('data-codigo') || '') === q) {
        return options[i];
      }
    }

    var codigoNombre = q.match(/^(.+?)\s*-\s*/);
    if (codigoNombre) {
      for (i = 0; i < options.length; i++) {
        if (normalize(options[i].getAttribute('data-codigo') || '') === codigoNombre[1]) {
          return options[i];
        }
        if (!soloCodigo && String(options[i].getAttribute('data-id') || '') === codigoNombre[1]) {
          return options[i];
        }
      }
    }

    if (/^\d+$/.test(q)) {
      for (i = 0; i < options.length; i++) {
        if (normalize(options[i].getAttribute('data-codigo') || '') === q) {
          return options[i];
        }
        if (!soloCodigo && String(options[i].getAttribute('data-id') || '') === q) {
          return options[i];
        }
      }
      if (!strict) {
        return null;
      }
    }

    if (strict) {
      return null;
    }

    var partials = options.filter(function (o) {
      return normalize(o.value).indexOf(q) !== -1;
    });
    return partials.length === 1 ? partials[0] : null;
  }

  function bind(root) {
    var input = root.querySelector('[data-catalogo-input]');
    var hidden = root.querySelector('[data-catalogo-hidden]');
    var listId = root.getAttribute('data-catalogo-list');
    var list = listId ? document.getElementById(listId) : null;
    if (!input || !hidden || !list) {
      return;
    }

    var soloCodigo = root.hasAttribute('data-catalogo-solo-codigo');
    var options = Array.from(list.querySelectorAll('option'));
    var autoSubmitId = root.getAttribute('data-catalogo-auto-submit');
    var autoForm = autoSubmitId ? document.getElementById(autoSubmitId) : null;
    var userEdited = false;
    var submitTimer = null;

    function isFullSelection(match) {
      return match && normalize(input.value) === normalize(match.value);
    }

    function sync(strict, triggerSubmit) {
      var match = findMatch(input.value, options, strict, soloCodigo);
      var next = match ? String(match.getAttribute('data-id') || '') : '';
      if (match && !strict && input.value !== match.value) {
        input.value = match.value;
      }
      var changed = hidden.value !== next;
      hidden.value = next;
      if (changed) {
        hidden.dispatchEvent(new Event('change', { bubbles: true }));
      }
      if (triggerSubmit && userEdited && autoForm && next !== '' && match && isFullSelection(match)) {
        autoForm.requestSubmit();
      }
    }

    function scheduleAutoSubmit() {
      if (!autoForm) {
        return;
      }
      window.clearTimeout(submitTimer);
      submitTimer = window.setTimeout(function () {
        sync(true, true);
      }, 450);
    }

    input.addEventListener('input', function () {
      userEdited = true;
      sync(true, false);
      if (autoForm) {
        scheduleAutoSubmit();
      }
    });
    input.addEventListener('change', function () {
      sync(false, true);
    });
    input.addEventListener('blur', function () {
      window.clearTimeout(submitTimer);
      sync(false, false);
    });

    var required = root.hasAttribute('data-catalogo-required');
    var form = root.closest('form');
    if (form) {
      form.addEventListener('submit', function (ev) {
        window.clearTimeout(submitTimer);
        sync(false, false);
        if (required && !hidden.value) {
          ev.preventDefault();
          input.setCustomValidity('Elegí una opción de la lista (código y nombre).');
          input.reportValidity();
        } else {
          input.setCustomValidity('');
        }
      });
    }
  }

  document.querySelectorAll('[data-catalogo-buscar]').forEach(bind);
})();
