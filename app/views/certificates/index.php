<?php $isRes = $c['regimen'] === 'RES'; $porEmitir = array_filter($pos, fn($x) => !$x['cert']); $sinFirma = array_filter($pos, fn($x) => $x['cert'] && $x['cert']['estado'] === 'SIN_FIRMA'); ?>
<div class="page-head">
  <div><h1>Certificados de accionistas</h1><p class="muted"><?= e($c['razon_social']) ?> · <?= $isRes ? badge('RES · control interno', 'res') : badge('Tradicional', 'trad') ?></p></div>
  <div class="btns"><?= help_button('certificados') ?></div>
</div>
<form method="get" class="company-switch"><input type="hidden" name="r" value="certificados"><label>Sociedad <select name="c" onchange="this.form.submit()"><?php foreach ($companies as $co): ?><option value="<?= $co['id'] ?>"<?= sel($co['id'], $c['id']) ?>><?= e($co['razon_social']) ?> (<?= $co['regimen'] === 'RES' ? 'RES' : 'Trad.' ?>)</option><?php endforeach; ?></select></label></form>
<div class="alert info small">Flujo: <strong>1</strong> emitir el certificado → <strong>2</strong> descargarlo y hacerlo firmar por el representante legal (<?= e($c['rep_nombre']) ?>) en FirmaYa → <strong>3</strong> subir aquí el PDF firmado. Solo el certificado firmado queda disponible para el accionista en su portal; sin firma no tiene validez.</div>
<div class="kpis">
  <div class="kpi"><span>Posiciones con acciones</span><strong><?= count($pos) ?></strong><small>accionista × serie</small></div>
  <div class="kpi"><span>Por emitir</span><strong><?= count($porEmitir) ?></strong><small>sin certificado vigente</small></div>
  <div class="kpi"><span>Pendientes de firma</span><strong><?= count($sinFirma) ?></strong><small>subir PDF firmado</small></div>
</div>
<div class="tabs">
  <a href="<?= e(url('certificados', ['c' => $c['id'], 'tab' => 'posiciones'])) ?>" class="<?= $tab === 'posiciones' ? 'on' : '' ?>">Accionistas y series</a>
  <a href="<?= e(url('certificados', ['c' => $c['id'], 'tab' => 'emitidos'])) ?>" class="<?= $tab === 'emitidos' ? 'on' : '' ?>">Certificados emitidos (<?= count($certs) ?>)</a>
</div>
<?php if ($tab === 'posiciones'): ?>
<div class="card">
<table class="tbl"><thead><tr><th>N°</th><th>Accionista</th><th>Serie</th><th class="r">Acciones</th><th>Gravámenes</th><th>Certificado vigente</th><th></th></tr></thead><tbody>
<?php foreach ($pos as $x): $m = $x['member']; $ce = $x['cert']; ?>
<tr><td class="mono"><?= str_pad((string)$m['socio_numero'], 2, '0', STR_PAD_LEFT) ?></td><td><strong><?= e(person_name($m)) ?></strong><br><small class="muted"><?= e(rut_format($m['rut'])) ?></small></td>
<td><?= e($x['serie']['nombre']) ?></td><td class="r"><strong><?= fnum($x['cantidad']) ?></strong></td>
<td><?= $x['gravamenes'] ? badge($x['gravamenes'] . ' vigente(s)', 'warn') : '<span class="muted small">—</span>' ?><?= $isRes && $x['res_pendientes'] ? '<br>' . badge('RES pendiente', 'error') : '' ?></td>
<td><?php if ($ce): ?><a href="<?= e(url('certificados.ver', ['id' => $ce['id']])) ?>">N° <?= e($ce['numero']) ?></a> <?= badge(CERT_ESTADOS[$ce['estado']][0], CERT_ESTADOS[$ce['estado']][1]) ?><?php else: ?><?= badge('Por emitir', 'error') ?><?php endif; ?></td>
<td><?php if (can('certificados', 'editar')): ?><a class="btn sm <?= $ce ? '' : 'primary' ?>" href="<?= e(url('certificados.emitir', ['c' => $c['id'], 'm' => $m['member_id'], 's' => $x['serie']['id']])) ?>"><?= $ce ? 'Reemitir' : 'Emitir' ?></a><?php endif; ?></td></tr>
<?php endforeach; ?>
<?php if (!$pos): ?><tr><td colspan="7" class="muted">No hay accionistas con acciones en esta sociedad.</td></tr><?php endif; ?>
</tbody></table></div>
<?php else: ?>
<form class="filters card" method="get"><input type="hidden" name="r" value="certificados"><input type="hidden" name="c" value="<?= $c['id'] ?>"><input type="hidden" name="tab" value="emitidos">
  <select name="estado" onchange="this.form.submit()"><option value="">Todos los estados</option><?php foreach (CERT_ESTADOS as $k => [$l]): ?><option value="<?= $k ?>"<?= sel($k, $estado) ?>><?= e($l) ?></option><?php endforeach; ?></select></form>
<div class="card"><table class="tbl"><thead><tr><th>N°</th><th>Accionista</th><th>Serie</th><th class="r">Acciones</th><th>Emisión</th><th>Código</th><th>Estado</th></tr></thead><tbody>
<?php foreach ($certs as $ce): ?>
<tr><td><a href="<?= e(url('certificados.ver', ['id' => $ce['id']])) ?>"><strong><?= e($ce['numero']) ?></strong></a></td><td><?= e(person_name($ce)) ?><br><small class="muted"><?= e(rut_format($ce['rut'])) ?></small></td><td><?= e($ce['serie']) ?></td>
<td class="r"><?= fnum($ce['cantidad']) ?></td><td class="nowrap"><?= e(fdate($ce['fecha_emision'])) ?></td><td class="mono small"><?= e($ce['codigo']) ?></td><td><?= badge(CERT_ESTADOS[$ce['estado']][0], CERT_ESTADOS[$ce['estado']][1]) ?></td></tr>
<?php endforeach; ?>
<?php if (!$certs): ?><tr><td colspan="7" class="muted">Sin certificados.</td></tr><?php endif; ?>
</tbody></table></div>
<?php endif; ?>
