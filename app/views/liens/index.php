<?php $isRes = $c['regimen'] === 'RES'; ?>
<div class="page-head">
  <div><h1>Registro de gravámenes</h1><p class="muted"><?= e($c['razon_social']) ?> · <?= $isRes ? badge('RES', 'res') : badge('Tradicional', 'trad') ?></p></div>
  <div class="btns"><?= help_button('gravamenes') ?></div>
</div>
<form method="get" class="company-switch"><input type="hidden" name="r" value="gravamenes"><label>Sociedad <select name="c" onchange="this.form.submit()"><?php foreach ($companies as $co): ?><option value="<?= $co['id'] ?>"<?= sel($co['id'], $c['id']) ?>><?= e($co['razon_social']) ?></option><?php endforeach; ?></select></label></form>
<?php if ($isRes): ?><div class="alert info small">Sociedad RES: los gravámenes se anotan solo para control interno. El certificado indicará que no es posible certificar su existencia o inexistencia y que deben verificarse en el portal del Registro de Empresas y Sociedades.</div><?php endif; ?>
<?php if (can('gravamenes', 'editar')): ?>
<details class="card add-box"><summary><strong>+ Anotar gravamen</strong> <span class="muted small">(prenda, embargo, prohibición, usufructo, medida precautoria u otro)</span></summary>
  <?php if (!$opts): ?><p class="muted">No hay accionistas con acciones.</p><?php else: ?>
  <form method="post" action="<?= e(url('gravamenes.anotar')) ?>" enctype="multipart/form-data"><?= csrf_field() ?><input type="hidden" name="c" value="<?= $c['id'] ?>">
    <div class="grid4">
      <label class="span2">Accionista<select name="member_id" id="gMember" required><option value="">Seleccione…</option><?php foreach ($opts as $m): ?><option value="<?= $m['mid'] ?>" data-bal='<?= e(json_encode($m['_bal'])) ?>'><?= str_pad((string)$m['socio_n'], 2, '0', STR_PAD_LEFT) ?> · <?= e(person_name($m)) ?> · <?= e(rut_format($m['rut'])) ?></option><?php endforeach; ?></select></label>
      <label>Serie<select name="series_id" id="gSerie" required><?php foreach ($series as $s): ?><option value="<?= $s['id'] ?>"><?= e($s['nombre']) ?></option><?php endforeach; ?></select><small id="gSaldo" class="muted"></small></label>
      <label>Acciones afectadas<input name="cantidad" data-num required></label>
      <label>Tipo<select name="tipo" required><?php foreach (TIPOS_GRAVAMEN as $k => $l): ?><option value="<?= $k ?>"><?= e($l) ?></option><?php endforeach; ?></select></label>
      <label class="span2">A favor de<input name="a_favor_de" required placeholder="Acreedor, tribunal, beneficiario…"></label>
      <label>Fecha de anotación<input type="date" name="fecha_anotacion" required max="<?= e(today()) ?>" value="<?= e(today()) ?>"></label>
      <label class="span2">Descripción (rol de causa, escritura, monto, plazo…)<input name="descripcion"></label>
      <label class="span2">Documento de respaldo (PDF) *<input type="file" name="documento" accept="application/pdf" required></label>
    </div>
    <button class="btn primary">Anotar gravamen</button>
  </form>
  <?php endif; ?>
</details>
<?php endif; ?>
<div class="card">
<table class="tbl"><thead><tr><th>Anotado</th><th>Accionista</th><th>Serie</th><th class="r">Acciones</th><th>Tipo</th><th>A favor de</th><th>Documentos</th><th>Estado</th></tr></thead><tbody>
<?php foreach ($rows as $g): ?>
<tr class="<?= $g['estado'] === 'ALZADO' ? 'dim' : '' ?>"><td class="nowrap"><?= e(fdate($g['fecha_anotacion'])) ?></td><td><strong><?= e(person_name($g)) ?></strong><br><small class="muted"><?= e(rut_format($g['rut'])) ?></small></td><td><?= e($g['serie']) ?></td>
<td class="r"><?= fnum($g['cantidad']) ?></td><td><?= e(TIPOS_GRAVAMEN[$g['tipo_p']] ?? $g['tipo_p']) ?><?= $g['descripcion'] ? '<br><small class="muted">' . e($g['descripcion']) . '</small>' : '' ?></td><td><?= e($g['a_favor_de']) ?></td>
<td><?php if ($g['doc_id']): ?><a class="file-chip" target="_blank" href="<?= e(url('doc', ['id' => $g['doc_id']])) ?>">Anotación</a><?php endif; ?><?php if ($g['alzado_doc_id']): ?><a class="file-chip alt" target="_blank" href="<?= e(url('doc', ['id' => $g['alzado_doc_id']])) ?>">Alzamiento</a><?php endif; ?></td>
<td><?php if ($g['estado'] === 'VIGENTE'): ?><?= badge('Vigente', 'warn') ?>
  <?php if (can('gravamenes', 'editar')): ?><br><button type="button" class="link small" data-open="alz<?= $g['id'] ?>">Alzar</button>
  <form id="alz<?= $g['id'] ?>" class="hidden subform" method="post" enctype="multipart/form-data" action="<?= e(url('gravamenes.alzar')) ?>"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $g['id'] ?>">
    <input type="date" name="alzado_fecha" required max="<?= e(today()) ?>" value="<?= e(today()) ?>"><input name="alzado_obs" placeholder="Observación"><input type="file" name="documento" accept="application/pdf" required><button class="btn sm primary">Registrar alzamiento</button></form><?php endif; ?>
<?php else: ?><?= badge('Alzado ' . fdate($g['alzado_fecha']), 'neutral') ?><?php endif; ?></td></tr>
<?php endforeach; ?>
<?php if (!$rows): ?><tr><td colspan="8" class="muted">No hay gravámenes anotados en esta sociedad.</td></tr><?php endif; ?>
</tbody></table></div>
