<div class="page-head"><h1>Sociedades</h1><div><?= help_button('sociedades') ?> <?php if (can('sociedades', 'editar')): ?><a class="btn primary" href="<?= e(url('sociedades.form')) ?>">+ Nueva sociedad</a><?php endif; ?></div></div>
<div class="card">
<?php if (!$rows): ?><p class="muted">No hay sociedades registradas o su usuario no tiene sociedades asignadas.</p><?php else: ?>
<table class="tbl"><thead><tr><th>Razón social</th><th>RUT</th><th>Tipo</th><th>Régimen</th><th>Representante legal</th><th>Respaldo</th><th>Cumplimiento</th><th>Estado</th></tr></thead><tbody>
<?php foreach ($rows as $c): ?>
<tr>
  <td><a href="<?= e(url('sociedades.ver', ['id' => $c['id']])) ?>"><strong><?= e($c['razon_social']) ?></strong></a></td>
  <td class="nowrap"><?= e(rut_format($c['rut'])) ?></td>
  <td><?= e(['SPA' => 'SpA', 'SA_CERRADA' => 'S.A. cerrada', 'SA_ABIERTA' => 'S.A. abierta'][$c['tipo_social']] ?? '') ?></td>
  <td><?= $c['regimen'] === 'RES' ? badge('RES', 'res') : badge('Tradicional', 'trad') ?></td>
  <td><?= e($c['rep_nombre']) ?></td>
  <td><?= $c['_miss']['base'] ? badge('Incompleto', 'error') : badge('Completo', 'ok') ?></td>
  <td><?= $c['_miss']['cumplimiento'] ? badge('Pendiente (' . count($c['_miss']['cumplimiento']) . ')', 'warn') : badge('Al día', 'ok') ?></td>
  <td><?= $c['estado'] === 'ACTIVA' ? badge('Activa', 'ok') : badge('Inactiva', 'neutral') ?></td>
</tr>
<?php endforeach; ?></tbody></table>
<?php endif; ?>
</div>
