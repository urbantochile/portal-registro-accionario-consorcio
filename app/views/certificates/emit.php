<?php $isRes = $c['regimen'] === 'RES'; ?>
<div class="page-head"><div><h1>Emitir certificado de accionistas</h1><p class="muted"><?= e($c['razon_social']) ?> · <?= $isRes ? badge('RES · control interno', 'res') : badge('Tradicional', 'trad') ?></p></div><?= help_button('certificados') ?></div>
<?php if ($errors): ?><div class="alert error"><strong>No se emitió:</strong><ul><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<div class="grid2">
<div class="card"><h3>Datos que tomará el certificado</h3>
  <dl class="dl">
    <dt>Titular</dt><dd><strong><?= e($D['titular']['nombre']) ?></strong> · <?= e(rut_format($D['titular']['rut'])) ?></dd>
    <dt>Serie</dt><dd><?= e($D['serie']['nombre']) ?> · <?= e(rtrim(rtrim(number_format($D['serie']['votos'], 4, ',', '.'), '0'), ',')) ?> voto(s) por acción</dd>
    <dt>Acciones</dt><dd><strong><?= fnum($D['cantidad']) ?></strong> (<?= e(numero_letras_fem($D['cantidad'])) ?>)</dd>
    <dt>Gravámenes</dt><dd><?php if ($isRes): ?><span class="muted">No se certifican en RES (leyenda obligatoria).</span><?php elseif ($D['gravamenes']): ?><?php foreach ($D['gravamenes'] as $g): ?><div><?= e($g['tipo']) ?> · <?= fnum($g['cantidad']) ?> acc. · a favor de <?= e($g['a_favor_de']) ?> · <?= e(fdate($g['fecha_anotacion'])) ?></div><?php endforeach; ?><?php else: ?>Libres de gravámenes informados<?php endif; ?></dd>
    <dt><?= $isRes ? 'CVE del RES' : 'Folio certificado de venta' ?></dt><dd class="mono"><?= e(implode(', ', $isRes ? $D['origen']['cves'] : $D['origen']['folios'])) ?: '<span class="t-error">— sin registrar —</span>' ?></dd>
    <dt>Inscripción</dt><dd>Folio (N° transferencia) <?= e($D['inscripcion']['folio']) ?> · <?= e(fdate($D['inscripcion']['fecha'])) ?></dd>
    <dt>Firma</dt><dd><?= e($D['representante']['nombre']) ?> · <?= e(rut_format($D['representante']['rut'])) ?> · <?= e($D['representante']['cargo']) ?></dd>
  </dl>
  <p class="muted small">Estos datos se toman del registro al momento de emitir. Si algo no corresponde, corríjalo primero en el registro de accionistas, gravámenes o en la ficha de la sociedad.</p>
</div>
<form method="post" class="card"><?= csrf_field() ?><input type="hidden" name="c" value="<?= $c['id'] ?>"><input type="hidden" name="m" value="<?= $mid ?>"><input type="hidden" name="s" value="<?= $sid ?>">
  <h3>I · Estado de pago</h3>
  <label class="check"><input type="radio" name="estado_pago" value="INTEGRAS"<?= chk(($f['estado_pago'] ?? '') === 'INTEGRAS') ?>> Íntegramente pagadas</label>
  <label class="check"><input type="radio" name="estado_pago" value="PARCIAL"<?= chk(($f['estado_pago'] ?? '') === 'PARCIAL') ?>> Parcialmente pagadas</label>
  <div class="grid3"><label>Pagado $<input name="monto_pagado" data-num value="<?= e(fnum($f['monto_pagado'] ?? '')) ?>"></label><label>de $ (total)<input name="monto_total" data-num value="<?= e(fnum($f['monto_total'] ?? '')) ?>"></label><label>Plazo de entero<input type="date" name="plazo_entero" value="<?= e($f['plazo_entero'] ?? '') ?>"></label></div>
  <label class="check"><input type="radio" name="estado_pago" value="NO_PAGADAS"<?= chk(($f['estado_pago'] ?? '') === 'NO_PAGADAS') ?>> No pagadas (indique el plazo de entero)</label>
  <?php if ($isRes): ?>
  <h3 class="mt">Registro RES</h3>
  <label class="check"><input type="checkbox" name="res_pendiente"<?= chk($f['res_pendiente'] ?? false) ?><?= $D['res_pendiente_auto'] ? ' checked disabled' : '' ?>> Registro RES pendiente (el accionista aún no inscribe su compra en el portal del RES)</label>
  <?php if ($D['res_pendiente_auto']): ?><p class="muted small">Se marca automáticamente: hay compras de esta serie registradas con «RES pendiente».</p><?php endif; ?>
  <?php endif; ?>
  <h3 class="mt">Lugar y fecha</h3>
  <div class="grid2"><label>Lugar de emisión<input name="lugar" required value="<?= e($f['lugar'] ?? '') ?>"></label><label>Fecha de emisión<input type="date" name="fecha_emision" required max="<?= e(today()) ?>" value="<?= e($f['fecha_emision'] ?? today()) ?>"></label></div>
  <div class="actions"><a class="btn" href="<?= e(url('certificados', ['c' => $c['id']])) ?>">Cancelar</a><button class="btn primary">Generar certificado</button></div>
  <p class="muted small">Si existe un certificado vigente de esta misma serie para el accionista, quedará «Reemplazado».</p>
</form>
</div>
