<div class="page-head"><h1>Panel de control</h1><?= help_button('panel') ?></div>
<div class="kpis">
  <div class="kpi"><span>Sociedades</span><strong><?= count($companies) ?></strong><small><?= $stats['res'] ?> RES · <?= $stats['trad'] ?> tradicional</small></div>
  <div class="kpi"><span>Socios registrados</span><strong><?= fnum($stats['socios']) ?></strong><small>en todos los registros de socios</small></div>
  <div class="kpi"><span>Accionistas vigentes</span><strong><?= fnum($stats['accionistas']) ?></strong><small>con saldo mayor a cero</small></div>
  <div class="kpi"><span>Diligencias pendientes</span><strong><?= fnum($ddPend) ?></strong><small>personas por aprobar</small></div>
</div>
<?php if ($alerts): ?>
<div class="card"><h3>Alertas</h3>
  <ul class="alert-list"><?php foreach ($alerts as [$t, $m, $u]): ?><li class="<?= $t ?>"><a href="<?= e($u) ?>"><?= e($m) ?></a></li><?php endforeach; ?></ul>
</div>
<?php endif; ?>
<div class="grid2 dash-grid">
  <div class="card"><h3>Sociedades</h3>
    <?php if (!$cards): ?><p class="muted">Aún no hay sociedades<?= can('sociedades', 'editar') ? '. <a href="' . e(url('sociedades.form')) . '">Registrar la primera</a>' : '' ?>.</p><?php endif; ?>
    <table class="tbl"><thead><tr><th>Sociedad</th><th>Régimen</th><th class="r">Socios</th><th class="r">Accionistas</th><th class="r">Acciones</th><th></th></tr></thead><tbody>
    <?php foreach ($cards as $k): $c = $k['c']; ?>
      <tr><td><a href="<?= e(url('accionistas', ['c' => $c['id']])) ?>"><?= e($c['razon_social']) ?></a><br><small class="muted"><?= e(rut_format($c['rut'])) ?></small></td>
      <td><?= $c['regimen'] === 'RES' ? badge('RES', 'res') : badge('Tradicional', 'trad') ?></td>
      <td class="r"><?= fnum($k['socios']) ?></td><td class="r"><?= fnum($k['accionistas']) ?></td><td class="r"><?= fnum($k['acciones']) ?></td>
      <td><?= $k['ok'] ? badge('Al día', 'ok') : badge('Pendientes', 'warn') ?></td></tr>
    <?php endforeach; ?></tbody></table>
  </div>
  <div class="card">
    <?php if (is_master()): ?>
      <h3>Respaldo</h3>
      <p><?= $lastBackup ? 'Último respaldo descargado: <strong>' . e(fdatetime($lastBackup['creado_en'])) . '</strong>' : '<strong>Nunca se ha descargado un respaldo.</strong>' ?></p>
      <p class="muted small">Debe descargarse un respaldo todos los días hábiles (se excluyen sábados, domingos y feriados).</p>
      <a class="btn" href="<?= e(url('respaldos')) ?>">Ir a respaldos</a>
    <?php endif; ?>
    <?php if ($recent): ?>
      <h3 style="margin-top:18px">Actividad reciente</h3>
      <ul class="feed"><?php foreach ($recent as $a): ?><li><span class="muted small"><?= e(fdatetime($a['creado_en'])) ?></span> <strong><?= e($a['user_email'] ?? '—') ?></strong> · <?= e(str_replace('_', ' ', $a['accion'])) ?></li><?php endforeach; ?></ul>
    <?php endif; ?>
  </div>
</div>
