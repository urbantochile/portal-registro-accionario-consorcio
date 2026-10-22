<div class="page-head">
  <div><h1>Registro de socios</h1><p class="muted"><?= e($c['razon_social']) ?> · <?= e(rut_format($c['rut'])) ?> · <?= $c['regimen'] === 'RES' ? badge('RES', 'res') : badge('Tradicional', 'trad') ?></p></div>
  <div class="btns"><?= help_button('socios') ?><a class="btn" href="<?= e(url('socios.excel', ['c' => $c['id']])) ?>">Descargar Excel</a><?php if (can('accionistas')): ?><a class="btn" href="<?= e(url('accionistas', ['c' => $c['id']])) ?>">Ir al registro de accionistas</a><?php endif; ?></div>
</div>
<form method="get" class="company-switch"><input type="hidden" name="r" value="socios"><label>Sociedad <select name="c" onchange="this.form.submit()"><?php foreach ($companies as $co): ?><option value="<?= $co['id'] ?>"<?= sel($co['id'], $c['id']) ?>><?= e($co['razon_social']) ?></option><?php endforeach; ?></select></label></form>

<?php if (can('socios', 'editar') && $c['estado'] === 'ACTIVA'): ?>
<details class="card add-box"<?= $rows ? '' : ' open' ?>><summary><strong>+ Incorporar socio</strong> <span class="muted small">(solo personas con debida diligencia aprobada)</span></summary>
  <?php if (!$eligible): ?><p class="muted">No hay personas con diligencia aprobada pendientes de incorporar. <a href="<?= e(url('personas.form')) ?>">Registrar nueva persona</a>.</p>
  <?php else: ?>
  <form method="post" action="<?= e(url('socios.agregar')) ?>" enctype="multipart/form-data"><?= csrf_field() ?><input type="hidden" name="company_id" value="<?= $c['id'] ?>">
    <div class="grid4">
      <label class="span2">Persona<select name="person_id" required data-search><option value="">Seleccione…</option><?php foreach ($eligible as $p): ?><option value="<?= $p['id'] ?>"><?= e(person_name($p)) ?> · <?= e(rut_format($p['rut'])) ?></option><?php endforeach; ?></select></label>
      <label>Fecha de ingreso<input type="date" name="fecha_ingreso" required value="<?= e(today()) ?>"></label>
      <label>Firma (opcional)<input type="file" name="firma" accept="application/pdf,image/png,image/jpeg"></label>
      <label class="span4">Observaciones<input name="observaciones"></label>
    </div>
    <button class="btn primary">Incorporar al registro de socios</button>
  </form>
  <?php endif; ?>
</details>
<?php endif; ?>

<div class="card book">
  <div class="book-title">REGISTRO DE SOCIOS <span><?= e($c['razon_social']) ?></span></div>
  <div class="scroll-x">
  <table class="tbl ledger"><thead><tr><th>N°</th><th>Nombres</th><th>Edad</th><th>Estado civil</th><th>Fecha ingreso</th><th>Profesión</th><th>Domicilio</th><th>Carnet</th><th>Firmas</th><th>Observaciones</th><th>Acciones</th></tr></thead><tbody>
  <?php foreach ($rows as $r): ?>
    <tr class="<?= $r['_saldo'] <= 0 && $r['_hist'] ? 'dim' : '' ?>">
      <td class="mono"><?= str_pad((string)$r['socio_numero'], 2, '0', STR_PAD_LEFT) ?></td>
      <td><a href="<?= e(url('personas.ver', ['id' => $r['person_id']])) ?>"><strong><?= e(person_name($r)) ?></strong></a><?= $r['tipo'] === 'JURIDICA' ? '<br><small class="muted">p.p. ' . e($r['jur_rep_nombre']) . '</small>' : '' ?></td>
      <td><?= $r['tipo'] === 'NATURAL' ? e(edad($r['fecha_nacimiento'])) : '—' ?></td>
      <td class="small"><?= $r['tipo'] === 'NATURAL' ? e($r['estado_civil']) : '—' ?></td>
      <td class="nowrap"><?= e(fdate($r['fecha_ingreso'])) ?></td>
      <td class="small"><?= e($r['profesion']) ?></td>
      <td class="small"><?= e(person_address($r)) ?></td>
      <td class="nowrap mono"><?= e(rut_format($r['rut'])) ?></td>
      <td><?= $r['_firma'] ? '<a class="file-chip" target="_blank" href="' . e(url('doc', ['id' => $r['_firma']['id']])) . '">Ver firma</a>' : '<span class="muted small">—</span>' ?></td>
      <td class="small obs">
        <?php if ($r['m_obs']): ?><div><?= e($r['m_obs']) ?></div><?php endif; ?>
        <?php foreach ($r['_notas'] as $n): ?><div class="note <?= $n['automatica'] ? 'auto' : '' ?>"><strong><?= e(fdate($n['fecha'])) ?>:</strong> <?= e($n['texto']) ?></div><?php endforeach; ?>
        <?php if (can('socios', 'editar')): ?>
          <button type="button" class="link small" data-open="nota<?= $r['member_id'] ?>">+ anotación / firma</button>
          <form id="nota<?= $r['member_id'] ?>" class="hidden subform" method="post" action="<?= e(url('socios.nota')) ?>" enctype="multipart/form-data"><?= csrf_field() ?><input type="hidden" name="member_id" value="<?= $r['member_id'] ?>">
            <input type="date" name="fecha" value="<?= e(today()) ?>"><input name="texto" placeholder="Anotación"><input type="file" name="firma" accept="application/pdf,image/png,image/jpeg"><button class="btn sm">Guardar</button></form>
        <?php endif; ?>
      </td>
      <td class="r nowrap"><?= $r['_saldo'] > 0 ? '<strong>' . fnum($r['_saldo']) . '</strong>' : ($r['_hist'] ? badge('Enajenó todo', 'neutral') : '<span class="muted">0</span>') ?></td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$rows): ?><tr><td colspan="11" class="muted">El registro de socios de esta sociedad está vacío.</td></tr><?php endif; ?>
  </tbody></table></div>
</div>
