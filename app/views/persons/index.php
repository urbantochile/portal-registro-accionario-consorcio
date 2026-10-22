<div class="page-head"><h1>Personas y debida diligencia</h1><div><?= help_button('personas') ?> <a class="btn" href="<?= e(url('personas.excel')) ?>">Descargar Excel</a> <?php if (can('socios', 'editar')): ?><a class="btn primary" href="<?= e(url('personas.form')) ?>">+ Nueva persona</a><?php endif; ?></div></div>
<p class="muted">Paso previo al registro de socios: toda persona (natural o jurídica) se registra una sola vez aquí, se le aplica la debida diligencia y, una vez aprobada, puede incorporarse al registro de socios de una o varias sociedades.</p>
<form class="filters card" method="get"><input type="hidden" name="r" value="personas">
  <input name="s" placeholder="Buscar por nombre o RUT" value="<?= e(getp('s')) ?>">
  <select name="tipo"><option value="">Todas</option><option value="NATURAL"<?= sel('NATURAL', getp('tipo')) ?>>Personas naturales</option><option value="JURIDICA"<?= sel('JURIDICA', getp('tipo')) ?>>Personas jurídicas</option></select>
  <select name="dd"><option value="">Toda diligencia</option><?php foreach (['PENDIENTE' => 'Pendiente', 'APROBADA' => 'Aprobada', 'RECHAZADA' => 'Rechazada'] as $k => $l): ?><option value="<?= $k ?>"<?= sel($k, getp('dd')) ?>><?= $l ?></option><?php endforeach; ?></select>
  <button class="btn">Filtrar</button>
</form>
<div class="card">
<table class="tbl"><thead><tr><th>Nombre / Razón social</th><th>RUT</th><th>Tipo</th><th>Contacto</th><th>Diligencia</th><th>Socio en</th></tr></thead><tbody>
<?php foreach ($rows as $p): ?>
<tr><td><a href="<?= e(url('personas.ver', ['id' => $p['id']])) ?>"><strong><?= e(person_name($p)) ?></strong></a></td>
<td class="nowrap"><?= e(rut_format($p['rut'])) ?></td><td><?= $p['tipo'] === 'NATURAL' ? 'Natural' : 'Jurídica' ?></td>
<td class="small"><?= e($p['email']) ?><br><?= e($p['telefono']) ?></td>
<td><?= ['PENDIENTE' => badge('Pendiente', 'warn'), 'APROBADA' => badge('Aprobada', 'ok'), 'RECHAZADA' => badge('Rechazada', 'error')][$p['dd_estado']] ?></td>
<td class="small"><?= e(implode(', ', array_column($p['_soc'], 'razon_social'))) ?: '<span class="muted">—</span>' ?></td></tr>
<?php endforeach; ?>
<?php if (!$rows): ?><tr><td colspan="6" class="muted">Sin resultados.</td></tr><?php endif; ?>
</tbody></table></div>
