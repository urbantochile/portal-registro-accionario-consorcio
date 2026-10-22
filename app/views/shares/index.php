<?php $isRes = $c['regimen'] === 'RES'; ?>
<div class="page-head">
  <div><h1>Registro de accionistas</h1><p class="muted"><?= e($c['razon_social']) ?> · <?= e(rut_format($c['rut'])) ?> · <?= $isRes ? badge('RES · registro de administración', 'res') : badge('Tradicional · documento de control', 'trad') ?></p></div>
  <div class="btns"><?= help_button('accionistas') ?>
    <?php if ($isRes): ?><a class="btn" href="<?= e(url('accionistas.excel', ['c' => $c['id']])) ?>">Descargar Excel</a>
    <?php elseif (can('libro_pdf')): ?><button class="btn" type="button" data-open="libroBox">Libro de Accionistas (PDF)</button><?php endif; ?>
    <?php if (can('accionistas', 'editar') && $c['estado'] === 'ACTIVA'): ?><a class="btn primary" href="<?= e(url('accionistas.nuevo', ['c' => $c['id']])) ?>">+ Registrar movimiento</a><?php endif; ?>
  </div>
</div>
<form method="get" class="company-switch"><input type="hidden" name="r" value="accionistas"><input type="hidden" name="tab" value="<?= e($tab) ?>"><label>Sociedad <select name="c" onchange="this.form.submit()"><?php foreach ($companies as $co): ?><option value="<?= $co['id'] ?>"<?= sel($co['id'], $c['id']) ?>><?= e($co['razon_social']) ?> (<?= $co['regimen'] === 'RES' ? 'RES' : 'Trad.' ?>)</option><?php endforeach; ?></select></label></form>

<?php if ($isRes): ?><div class="alert info small">Sociedad RES: este registro es de <strong>administración interna</strong> y no acredita titularidad. El certificado oficial lo emite el Registro de Empresas y Sociedades (CVE). Acceso restringido a usuarios autorizados.</div><?php endif; ?>

<?php if (!$isRes && can('libro_pdf')): ?>
<form method="post" action="<?= e(url('accionistas.libro')) ?>" id="libroBox" class="card hidden libro-box"><?= csrf_field() ?><input type="hidden" name="c" value="<?= $c['id'] ?>">
  <h3>Entrega del LIBRO DE ACCIONISTAS</h3>
  <p>El PDF se genera con el logotipo y colores corporativos de la sociedad, lleva folio y la leyenda de entrega, y queda registrado en la bitácora. <strong>Se entrega únicamente al representante legal:</strong></p>
  <dl class="dl"><dt>Representante legal</dt><dd><strong><?= e($c['rep_nombre']) ?></strong> · RUT <?= e(rut_format($c['rep_rut'])) ?> · <?= e($c['rep_cargo']) ?></dd></dl>
  <label class="check"><input type="checkbox" name="confirmo" required> Confirmo que este libro será entregado exclusivamente a <?= e($c['rep_nombre']) ?>, representante legal de la sociedad.</label>
  <button class="btn primary">Generar y descargar PDF</button>
  <?php if ($deliveries): ?><h4>Últimas entregas</h4><table class="tbl small"><tr><th>Folio</th><th>Entregado a</th><th>Generado</th><th>Por</th></tr><?php foreach ($deliveries as $d): ?><tr><td class="mono"><?= e($d['folio']) ?></td><td><?= e($d['entregado_a_nombre']) ?></td><td><?= e(fdatetime($d['generado_en'])) ?></td><td><?= e($d['por']) ?></td></tr><?php endforeach; ?></table><?php endif; ?>
</form>
<?php endif; ?>

<div class="kpis">
  <div class="kpi"><span>Accionistas vigentes</span><strong><?= count($sh['rows']) ?></strong></div>
  <div class="kpi"><span>Acciones suscritas</span><strong><?= fnum($sh['total']) ?></strong></div>
  <?php foreach ($sh['series'] as $s): ?><div class="kpi"><span><?= e(serie_label($s['nombre'])) ?></span><strong><?= fnum($issued[(int)$s['id']] ?? 0) ?></strong><small><?= $s['acciones_autorizadas'] !== null ? 'de ' . fnum($s['acciones_autorizadas']) . ' autorizadas' : 'sin límite autorizado' ?></small></div><?php endforeach; ?>
</div>

<div class="tabs">
  <a href="<?= e(url('accionistas', ['c' => $c['id'], 'tab' => 'vigentes'])) ?>" class="<?= $tab === 'vigentes' ? 'on' : '' ?>">Saldos por accionista</a>
  <a href="<?= e(url('accionistas', ['c' => $c['id'], 'tab' => 'movimientos'])) ?>" class="<?= $tab === 'movimientos' ? 'on' : '' ?>">Movimientos (<?= count($movs) ?>)</a>
</div>

<?php if ($tab === 'vigentes'): ?>
<div class="card">
  <table class="tbl"><thead><tr><th>N°</th><th>Accionista</th><th>RUT</th><?php foreach ($sh['series'] as $s): ?><th class="r"><?= e(serie_label($s['nombre'])) ?></th><?php endforeach; ?><th class="r">Total</th><th class="r">% capital</th><th class="r">% votos</th><th></th></tr></thead><tbody>
  <?php foreach ($sh['rows'] as $r): $m = $r['member']; ?>
    <tr><td class="mono"><?= str_pad((string)$m['socio_numero'], 2, '0', STR_PAD_LEFT) ?></td><td><strong><?= e(person_name($m)) ?></strong></td><td class="nowrap"><?= e(rut_format($m['rut'])) ?></td>
    <?php foreach ($sh['series'] as $s): ?><td class="r"><?= fnum($r['series'][(int)$s['id']] ?? 0) ?></td><?php endforeach; ?>
    <td class="r"><strong><?= fnum($r['total']) ?></strong></td><td class="r"><?= fpct($r['pct']) ?></td><td class="r"><?= fpct($r['pct_votos']) ?></td>
    <td><a class="btn sm" href="<?= e(url('accionistas.ficha', ['c' => $c['id'], 'm' => $m['member_id']])) ?>">Hoja</a></td></tr>
  <?php endforeach; ?>
  <?php if (!$sh['rows']): ?><tr><td colspan="9" class="muted">Aún no hay accionistas. Registre una emisión de acciones a un socio.</td></tr><?php endif; ?>
  </tbody>
  <?php if ($sh['rows']): ?><tfoot><tr><td></td><td><strong>TOTAL</strong></td><td></td><?php foreach ($sh['series'] as $s): ?><td class="r"><strong><?= fnum(array_sum(array_map(fn($r) => $r['series'][(int)$s['id']] ?? 0, $sh['rows']))) ?></strong></td><?php endforeach; ?><td class="r"><strong><?= fnum($sh['total']) ?></strong></td><td class="r">100,00 %</td><td class="r">100,00 %</td><td></td></tr></tfoot><?php endif; ?>
  </table>
  <p class="muted small">Los socios que enajenaron la totalidad de sus acciones dejan de aparecer aquí automáticamente; su historia se conserva en su hoja y en el registro de socios queda la anotación correspondiente.</p>
</div>
<?php else: ?>
<div class="card book">
  <div class="book-title">REGISTRO DE ACCIONISTAS <span>Movimientos en orden cronológico</span></div>
  <div class="scroll-x">
  <table class="tbl ledger"><thead><tr><th>Fecha</th><th>N° transf.</th><th>Serie</th><th>Título inutilizado</th><th>Vendido por</th><th>Comprado por</th><th>Título nuevo comprador</th><th>Título nuevo vendedor</th><th class="r">Cantidad</th><th>Certificado</th><th>Observaciones</th><?php if (is_master()): ?><th></th><?php endif; ?></tr></thead><tbody>
  <?php foreach ($movs as $m): ?>
    <tr class="<?= $m['anulado'] ? 'voided' : '' ?>">
      <td class="nowrap"><?= e(fdate($m['fecha'])) ?></td><td class="mono"><?= (int)$m['numero'] ?></td><td><?= e($m['serie']) ?></td><td><?= e($m['titulo_inutilizado']) ?></td>
      <td><?= $m['vendedor_member_id'] ? '<a href="' . e(url('accionistas.ficha', ['c' => $c['id'], 'm' => $m['vendedor_member_id']])) . '">' . e($names[(int)$m['vendedor_member_id']] ?? '') . '</a>' : '<em>La sociedad (emisión)</em>' ?></td>
      <td><a href="<?= e(url('accionistas.ficha', ['c' => $c['id'], 'm' => $m['comprador_member_id']])) ?>"><?= e($names[(int)$m['comprador_member_id']] ?? '') ?></a></td>
      <td><?= e($m['titulo_nuevo_comprador']) ?></td><td><?= e($m['titulo_nuevo_vendedor']) ?></td>
      <td class="r"><strong><?= fnum($m['cantidad']) ?></strong></td>
      <td class="small"><?php if (!empty($m['res_pendiente']) && !$m['anulado']): ?><?= badge('RES pendiente', 'error') ?>
        <?php if (can('accionistas', 'editar')): ?><br><button type="button" class="link small" data-open="cve<?= $m['id'] ?>">Registrar CVE</button>
        <form id="cve<?= $m['id'] ?>" class="hidden subform" method="post" enctype="multipart/form-data" action="<?= e(url('accionistas.cve')) ?>"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $m['id'] ?>"><input name="cve" placeholder="CVE (12 caracteres)" required pattern="[A-Za-z0-9]{12}"><input type="file" name="cert_pdf" accept="application/pdf" required><button class="btn sm primary">Guardar CVE</button></form><?php endif; ?>
      <?php elseif ($m['cert_doc_id']): ?><a class="file-chip" target="_blank" href="<?= e(url('doc', ['id' => $m['cert_doc_id']])) ?>"><?= e($m['cert_codigo']) ?></a><?php else: ?><?= e($m['cert_codigo']) ?><?php endif; ?>
        <?php if ($m['cert_vendedor_codigo']): ?><br><a class="file-chip alt" target="_blank" href="<?= e(url('doc', ['id' => $m['cert_vendedor_doc_id']])) ?>">V: <?= e($m['cert_vendedor_codigo']) ?></a><?php endif; ?></td>
      <td class="small"><?= e($m['observaciones']) ?><?= $m['anulado'] ? '<div class="t-error"><strong>ANULADO</strong> ' . e(fdate($m['anulado_en'])) . ': ' . e($m['anulado_motivo']) . '</div>' : '' ?></td>
      <?php if (is_master()): ?><td><?php if (!$m['anulado']): ?><button class="btn sm ghost" type="button" data-open="anular<?= $m['id'] ?>">Anular</button>
        <form id="anular<?= $m['id'] ?>" class="hidden subform" method="post" action="<?= e(url('accionistas.anular')) ?>" data-confirm="¿Anular este movimiento? Los saldos se recalcularán."><?= csrf_field() ?><input type="hidden" name="id" value="<?= $m['id'] ?>"><input name="motivo" placeholder="Motivo" required><button class="btn sm danger">Confirmar</button></form><?php endif; ?></td><?php endif; ?>
    </tr>
  <?php endforeach; ?>
  <?php if (!$movs): ?><tr><td colspan="12" class="muted">Sin movimientos.</td></tr><?php endif; ?>
  </tbody></table></div>
</div>
<?php endif; ?>
