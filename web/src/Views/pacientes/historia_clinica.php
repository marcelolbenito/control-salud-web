<?php

declare(strict_types=1);
?>
<div class="container container-wide">
    <div class="page-head">
        <h1>Historia clínica</h1>
        <p class="muted">
            Paciente: <strong><?= h($nombre) ?></strong>
            · Nro HC: <strong><?= (int) ($p['NroHC'] ?? 0) ?></strong>
        </p>
        <p class="muted"><a href="/pacientes.php">← Volver a Pacientes</a></p>
    </div>

    <?php if ($error !== ''): ?>
        <p class="alert alert-error"><?= h($error) ?></p>
    <?php endif; ?>

    <form method="post" class="form-paciente">
        <input type="hidden" name="id" value="<?= (int) $id ?>">

        <section class="form-section">
            <h2 class="form-section-title">Evolución / HC</h2>
            <div class="form-grid-ext">
                <label class="span-2">
                    <textarea name="hc_texto" rows="14" placeholder="Texto libre de historia clínica"><?= h($hcBase) ?></textarea>
                </label>
            </div>
        </section>

        <section class="form-section">
            <h2 class="form-section-title">Antecedentes</h2>
            <div class="form-grid-ext">
                <label class="span-2">
                    <textarea name="antecedentes_hc" rows="8" placeholder="Antecedentes clínicos relevantes"><?= h($antecedentes) ?></textarea>
                </label>
            </div>
        </section>

        <div class="form-actions form-section">
            <button type="submit" class="btn btn-primary">Guardar historia clínica</button>
            <a class="btn btn-ghost" href="/paciente_form.php?id=<?= (int) $id ?>">Editar datos del paciente</a>
        </div>
    </form>
</div>

