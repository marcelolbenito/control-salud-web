<?php

declare(strict_types=1);

/**
 * Campo de búsqueda de catálogo (datalist), alineado al formulario de órdenes.
 *
 * Variables: $cbLabel, $cbPlaceholder, $cbName, $cbHiddenId, $cbInputId, $cbListId,
 * $cbOpts, $cbSelected, $cbRequired (bool), $cbAutoSubmitFormId (string|null), $cbGrowClass (string|null),
 * $cbSubmitTextName (string|null) nombre del input visible en GET para resolver en servidor
 */
if (!isset($cbValor)) {
    $cbValor = catalogo_valor_datalist($cbOpts, $cbSelected ?? 0);
}
$cbSubmitTextName = isset($cbSubmitTextName) ? trim((string) $cbSubmitTextName) : '';
$cbGrowClass = trim((string) ($cbGrowClass ?? ''));
$cbAutoSubmitFormId = isset($cbAutoSubmitFormId) ? trim((string) $cbAutoSubmitFormId) : '';
?>
<label<?= $cbGrowClass !== '' ? ' class="' . h($cbGrowClass) . '"' : '' ?>>
    <?= h((string) $cbLabel) ?>
    <span
        data-catalogo-buscar
        data-catalogo-list="<?= h((string) $cbListId) ?>"
        <?= $cbAutoSubmitFormId !== '' ? ' data-catalogo-auto-submit="' . h($cbAutoSubmitFormId) . '"' : '' ?>
        <?= !empty($cbRequired) ? ' data-catalogo-required="1"' : '' ?>
    >
        <input
            type="text"
            data-catalogo-input
            id="<?= h((string) $cbInputId) ?>"
            list="<?= h((string) $cbListId) ?>"
            value="<?= h($cbValor) ?>"
            placeholder="<?= h((string) $cbPlaceholder) ?>"
            autocomplete="off"
            <?= $cbSubmitTextName !== '' ? ' name="' . h($cbSubmitTextName) . '"' : '' ?>
            <?= !empty($cbRequired) ? ' required' : '' ?>
        >
        <input
            type="hidden"
            data-catalogo-hidden
            name="<?= h((string) $cbName) ?>"
            id="<?= h((string) $cbHiddenId) ?>"
            value="<?= (int) ($cbSelected ?? 0) > 0 ? (int) $cbSelected : '' ?>"
        >
    </span>
    <span class="hint">Código o nombre (ej. <strong>11</strong> u <strong>OSDE</strong>).</span>
</label>
<?php catalogo_imprimir_datalist($cbOpts, (string) $cbListId); ?>
