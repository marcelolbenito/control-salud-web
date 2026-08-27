<?php

declare(strict_types=1);

function portal_paciente_url(int $idClinica): string
{
    return url('/agenda_web.php?clinica=' . max(1, $idClinica));
}

function portal_paciente_url_completa(int $idClinica): string
{
    $path = portal_paciente_url($idClinica);
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');
    $host = trim((string) ($_SERVER['HTTP_HOST'] ?? ''));
    if ($host === '') {
        return $path;
    }

    return ($https ? 'https' : 'http') . '://' . $host . $path;
}

function portal_paciente_render_aviso(int $idClinica): string
{
    $urlCompleta = portal_paciente_url_completa($idClinica);
    $urlAbrir = portal_paciente_url($idClinica);
    $uid = 'portal-url-' . max(1, $idClinica);

    ob_start();
    ?>
    <aside class="portal-paciente-aviso" aria-label="Portal del paciente">
        <div class="portal-paciente-aviso-icon" aria-hidden="true"><i class="bi bi-globe2"></i></div>
        <div class="portal-paciente-aviso-body">
            <h2 class="portal-paciente-aviso-title">Portal del paciente</h2>
            <p class="portal-paciente-aviso-text">
                Compartí este enlace para que saquen turno online con su <strong>DNI</strong> (debe estar cargado en la ficha).
                Los turnos aparecen en <strong>Agenda diaria</strong>.
            </p>
            <div class="portal-paciente-aviso-actions">
                <code class="portal-paciente-aviso-url" id="<?= h($uid) ?>"><?= h($urlCompleta) ?></code>
                <button type="button" class="btn btn-ghost btn-sm portal-paciente-copy" data-copy-from="<?= h($uid) ?>">
                    <i class="bi bi-clipboard" aria-hidden="true"></i> Copiar enlace
                </button>
                <a class="btn btn-primary btn-sm" href="<?= h($urlAbrir) ?>" target="_blank" rel="noopener">
                    <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i> Abrir portal
                </a>
                <a class="btn btn-ghost btn-sm" href="<?= h(url('/ayuda.php?mod=agenda_web')) ?>">
                    <i class="bi bi-question-circle" aria-hidden="true"></i> Cómo funciona
                </a>
            </div>
        </div>
    </aside>
    <script>
    (function () {
        document.querySelectorAll('.portal-paciente-copy').forEach(function (btn) {
            if (btn.dataset.bound) return;
            btn.dataset.bound = '1';
            btn.addEventListener('click', function () {
                var id = btn.getAttribute('data-copy-from');
                var el = id ? document.getElementById(id) : null;
                var text = el ? (el.textContent || '').trim() : '';
                if (!text) return;
                var done = function () {
                    var prev = btn.innerHTML;
                    btn.innerHTML = '<i class="bi bi-check2" aria-hidden="true"></i> Copiado';
                    setTimeout(function () { btn.innerHTML = prev; }, 2000);
                };
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(text).then(done).catch(function () {
                        window.prompt('Copiá el enlace:', text);
                    });
                } else {
                    window.prompt('Copiá el enlace:', text);
                }
            });
        });
    })();
    </script>
    <?php

    return (string) ob_get_clean();
}
