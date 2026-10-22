<?php $saldo = array_sum($bySeries); ?>
<div class="page-head"><div><h1>Hoja de accionista</h1><p class="muted"><?= e($c['razon_social']) ?></p></div>
  <div class="btns"><?= help_button('accionistas') ?><a class="btn" href="<?= e(url('accionistas', ['c' => $c['id']])) ?>">Volver</a></div></div>
<div class="card book">
  <div class="book-title">REGISTRO DE ACCIONISTAS <span>Socio N° <?= str_pad((string)$m['socio_n'], 2, '0', STR_PAD_LEFT) ?></span></div>
  <div class="sheet-head">
    <div><span>Accionista</span><strong><?= e(person_name($m)) ?></strong></div>
    <div><span>Dirección</span><?= e(trim(($m['calle'] ?? '') . ' ' . ($m['numero'] ?? '') . ($m['depto'] ? ', ' . $m['depto'] : ''))) ?>, <?= e($m['comuna']) ?></div>
    <div><span>Nacionalidad</span><?= e($m['nacionalidad']) ?></div>
    <div><span>R.U.T.</span><strong class="mono"><?= e(rut_format($m['rut'])) ?></strong></div>
    <div><span>Fono</span><?= e($m['telefono']) ?></div>
    <div><span>Ciudad</span><?= e($m['ciudad']) ?></div>
    <div><span>Firma accionista</span><?= $firma ? '<a class="file-chip" target="_blank" href="' . e(url('doc', ['id' => $firma['id']])) . '">Ver firma registrada</a>' : '<span class="muted">No registrada</span>' ?></div>
    <div><span>Saldo actual</span><strong class="big"><?= fnum($saldo) ?></strong> acciones <?php foreach ($bySeries as $sid => $q): if ($q): ?><small class="muted">· <?= e($series[$sid] ?? '') ?>: <?= fnum($q) ?></small><?php endif; endforeach; ?></div>
  </div>
  <div class="scroll-x">
  <table class="tbl ledger"><thead><tr><th>Fecha de transferencia</th><th>N° de la transferencia</th><th>Serie</th><th>N° del título inutilizado</th><th>Comprado a</th><th>Vendido a</th><th>N° título nuevo del comprador</th><th>N° título nuevo del vendedor</th><th class="r">Compras</th><th class="r">Ventas</th><th class="r">Saldos</th><th>Observaciones</th><th>N° del título emitido</th></tr></thead><tbody>
  <?php foreach ($rows as $r): ?>
    <tr><td class="nowrap"><?= e(fdate($r['fecha'])) ?></td><td class="mono"><?= (int)$r['numero'] ?></td><td><?= e($r['serie']) ?></td><td><?= e($r['titulo_inutilizado']) ?></td><td><?= e($r['comprado_a']) ?></td><td><?= e($r['vendido_a']) ?></td>
    <td><?= e($r['titulo_nuevo_comprador']) ?></td><td><?= e($r['titulo_nuevo_vendedor']) ?></td><td class="r"><?= $r['compras'] ? fnum($r['compras']) : '' ?></td><td class="r"><?= $r['ventas'] ? fnum($r['ventas']) : '' ?></td><td class="r"><strong><?= fnum($r['saldo']) ?></strong></td><td class="small"><?= e($r['observaciones']) ?></td><td><?= e($r['titulo_emitido']) ?></td></tr>
  <?php endforeach; ?>
  <?php if (!$rows): ?><tr><td colspan="13" class="muted">Sin movimientos.</td></tr><?php endif; ?>
  </tbody></table></div>
</div>
<?php if ($certs): ?><div class="card"><h3>Certificados cargados</h3><ul class="chips"><?php foreach ($certs as $d): ?><li><a class="file-chip" target="_blank" href="<?= e(url('doc', ['id' => $d['id']])) ?>">Transf. N° <?= (int)$d['numero'] ?> · <?= $d['tipo'] === 'CERT_COMPRADOR' ? 'como comprador' : 'como vendedor' ?> · <?= e($d['nombre_original']) ?></a></li><?php endforeach; ?></ul></div><?php endif; ?>

<div class="grid2">
<div class="card"><h3>Gravámenes <?= can('gravamenes') ? '<a class="small" href="' . e(url('gravamenes', ['c' => $c['id']])) . '">· ir al registro</a>' : '' ?></h3>
  <?php if (!$grav): ?><p class="muted">Sin gravámenes anotados.</p><?php else: ?>
  <table class="tbl small"><tr><th>Anotado</th><th>Serie</th><th class="r">Acciones</th><th>Tipo</th><th>A favor de</th><th>Estado</th></tr>
  <?php foreach ($grav as $g): ?><tr><td><?= e(fdate($g['fecha_anotacion'])) ?></td><td><?= e($g['serie']) ?></td><td class="r"><?= fnum($g['cantidad']) ?></td><td><?= e(TIPOS_GRAVAMEN[$g['tipo']] ?? $g['tipo']) ?></td><td><?= e($g['a_favor_de']) ?></td><td><?= $g['estado'] === 'VIGENTE' ? badge('Vigente', 'warn') : badge('Alzado ' . fdate($g['alzado_fecha']), 'neutral') ?></td></tr><?php endforeach; ?></table>
  <?php endif; ?>
  <?php if ($c['regimen'] === 'RES'): ?><p class="muted small">Sociedad RES: anotación de control interno; no se certifica.</p><?php endif; ?>
</div>
<div class="card"><h3>Certificados de accionista vigentes</h3>
  <?php if (!$ccs): ?><p class="muted">Sin certificados vigentes.</p><?php else: ?>
  <ul><?php foreach ($ccs as $ce): ?><li><?= can('certificados') ? '<a href="' . e(url('certificados.ver', ['id' => $ce['id']])) . '">N° ' . e($ce['numero']) . '</a>' : 'N° ' . e($ce['numero']) ?> · <?= e($ce['serie']) ?> · <?= fnum($ce['cantidad']) ?> acc. · <?= $ce['estado'] === 'FIRMADO' ? badge('Firmado', 'ok') : badge('Pendiente de firma', 'warn') ?></li><?php endforeach; ?></ul>
  <?php endif; ?>
</div>
</div>
