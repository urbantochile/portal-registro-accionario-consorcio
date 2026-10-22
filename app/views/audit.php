<div class="page-head"><h1>Bitácora de actividad</h1><div><?= help_button('bitacora') ?> <a class="btn" href="<?= e(url('bitacora.excel', array_diff_key($_GET, ['r' => 1]))) ?>">Descargar Excel</a></div></div>
<form class="filters card" method="get"><input type="hidden" name="r" value="bitacora">
  <select name="u"><option value="">Todos los usuarios</option><?php foreach ($users as $u): ?><option value="<?= $u['id'] ?>"<?= sel($u['id'], getp('u')) ?>><?= e($u['nombre']) ?></option><?php endforeach; ?></select>
  <select name="a"><option value="">Todas las acciones</option><?php foreach ($acciones as $a): ?><option<?= sel($a, getp('a')) ?>><?= e($a) ?></option><?php endforeach; ?></select>
  <label class="inl">Desde <input type="date" name="desde" value="<?= e(getp('desde')) ?>"></label>
  <label class="inl">Hasta <input type="date" name="hasta" value="<?= e(getp('hasta')) ?>"></label>
  <input name="s" placeholder="Buscar en detalle" value="<?= e(getp('s')) ?>">
  <button class="btn">Filtrar</button>
</form>
<div class="card">
<p class="muted small"><?= fnum($total) ?> registros. La bitácora no se puede editar ni borrar desde la plataforma.</p>
<table class="tbl small"><thead><tr><th>Fecha y hora</th><th>Usuario</th><th>Acción</th><th>Sociedad</th><th>Detalle</th><th>IP</th></tr></thead><tbody>
<?php foreach ($rows as $r): ?>
<tr><td class="nowrap"><?= e(date('d-m-Y H:i:s', strtotime($r['creado_en']))) ?></td><td><?= e($r['user_email']) ?></td><td><span class="tag"><?= e($r['accion']) ?></span><?= $r['entidad'] ? '<br><small class="muted">' . e($r['entidad']) . ' #' . e((string)$r['entidad_id']) . '</small>' : '' ?></td>
<td><?= e($r['razon_social']) ?></td><td class="detail"><?= e(mb_strimwidth((string)$r['detalle'], 0, 220, '…')) ?></td><td class="mono"><?= e($r['ip']) ?></td></tr>
<?php endforeach; ?></tbody></table>
<div class="pager"><?php $pages = (int)ceil($total / 50); for ($i = max(1, $page - 5); $i <= min($pages, $page + 5); $i++): ?><a class="<?= $i === $page ? 'on' : '' ?>" href="<?= e(url('bitacora', array_merge(array_diff_key($_GET, ['r' => 1]), ['p' => $i]))) ?>"><?= $i ?></a><?php endfor; ?></div>
</div>
