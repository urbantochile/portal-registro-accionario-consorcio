<?php
$st = dd_status($p);
$locked = $p['dd_estado'] === 'APROBADA';
$canEdit = can('socios', 'editar');
$ddDocs = [];
foreach ($docs as $d) $ddDocs[$d['tipo']][] = $d;
?>
<div class="page-head">
  <div><h1><?= e(person_name($p)) ?></h1>
  <p class="muted"><?= e(rut_format($p['rut'])) ?> · <?= $p['tipo'] === 'NATURAL' ? 'Persona natural' : 'Persona jurídica' ?> ·
    <?= ['PENDIENTE' => badge('Diligencia pendiente', 'warn'), 'APROBADA' => badge('Diligencia aprobada', 'ok'), 'RECHAZADA' => badge('Diligencia rechazada', 'error')][$p['dd_estado']] ?></p></div>
  <div class="btns"><?= help_button('personas') ?><?php if ($canEdit): ?><a class="btn" href="<?= e(url('personas.form', ['id' => $p['id']])) ?>">Editar datos</a><?php endif; ?></div>
</div>

<div class="grid2">
  <div class="card"><h3>Antecedentes</h3>
    <dl class="dl">
      <?php if ($p['tipo'] === 'NATURAL'): ?>
        <dt>Nacionalidad</dt><dd><?= e($p['nacionalidad']) ?></dd>
        <dt>Fecha de nacimiento</dt><dd><?= e(fdate($p['fecha_nacimiento'])) ?> (<?= e(edad($p['fecha_nacimiento'])) ?> años)</dd>
        <dt>Estado civil</dt><dd><?= e($p['estado_civil']) ?></dd>
        <dt>Profesión u oficio</dt><dd><?= e($p['profesion']) ?></dd>
      <?php else: ?>
        <dt>Tipo de sociedad</dt><dd><?= e($p['jur_tipo_sociedad']) ?></dd>
        <dt>Constitución</dt><dd><?= e(fdate($p['jur_fecha_constitucion'])) ?> · <?= e($p['nacionalidad']) ?></dd>
        <dt>Giro</dt><dd><?= e($p['profesion']) ?></dd>
        <dt>Representante legal</dt><dd><?= e($p['jur_rep_nombre']) ?> · <?= e(rut_format($p['jur_rep_rut'])) ?></dd>
      <?php endif; ?>
      <dt>Domicilio</dt><dd><?= e(person_address($p)) ?><?= $p['pais'] ? ', ' . e($p['pais']) : '' ?></dd>
      <dt>Contacto</dt><dd><?= e($p['email']) ?> · <?= e($p['telefono']) ?></dd>
    </dl>
  </div>
  <div class="card"><h3>Sociedades en que participa</h3>
    <?php if (!$memberships): ?><p class="muted">Aún no está incorporado al registro de socios de ninguna sociedad.</p><?php endif; ?>
    <table class="tbl"><tbody>
    <?php foreach ($memberships as $m): if (!$m['_visible']) continue; ?>
      <tr><td><a href="<?= e(url('socios', ['c' => $m['company_id']])) ?>"><?= e($m['razon_social']) ?></a> <?= $m['regimen'] === 'RES' ? badge('RES', 'res') : badge('Trad.', 'trad') ?><br><small class="muted">Socio N° <?= str_pad((string)$m['numero'], 2, '0', STR_PAD_LEFT) ?> desde <?= e(fdate($m['fecha_ingreso'])) ?></small></td>
      <td class="r"><?= $m['_saldo'] > 0 ? '<strong>' . fnum($m['_saldo']) . '</strong> acciones' : '<span class="muted">sin acciones</span>' ?></td></tr>
    <?php endforeach; ?></tbody></table>
    <?php if ($canEdit && $p['dd_estado'] === 'APROBADA' && $available): ?>
      <form method="post" action="<?= e(url('socios.agregar')) ?>" enctype="multipart/form-data" class="subform"><?= csrf_field() ?>
        <input type="hidden" name="person_id" value="<?= $p['id'] ?>"><input type="hidden" name="volver" value="persona">
        <h4>Incorporar al registro de socios de otra sociedad</h4>
        <div class="grid3">
          <label class="span3">Sociedad<select name="company_id" required><?php foreach ($available as $c): ?><option value="<?= $c['id'] ?>"><?= e($c['razon_social']) ?> (<?= $c['regimen'] === 'RES' ? 'RES' : 'Tradicional' ?>)</option><?php endforeach; ?></select></label>
          <label>Fecha de ingreso<input type="date" name="fecha_ingreso" required value="<?= e(today()) ?>"></label>
          <label class="span2">Observaciones<input name="observaciones"></label>
          <label class="span3">Firma del socio (imagen o PDF, opcional)<input type="file" name="firma" accept="application/pdf,image/png,image/jpeg"></label>
        </div>
        <button class="btn primary sm">Incorporar como socio</button>
      </form>
    <?php elseif ($p['dd_estado'] !== 'APROBADA'): ?>
      <p class="alert info small">Para incorporarla a un registro de socios, primero apruebe la debida diligencia.</p>
    <?php endif; ?>
  </div>
</div>

<?php if (can('certificados')): ?>
<div class="card"><div class="card-head"><h3>Acceso al portal de accionista</h3>
  <?= $portal ? ($portal['activo'] ? badge('Activo', 'ok') : badge('Desactivado', 'neutral')) : badge('Sin acceso', 'neutral') ?></div>
  <p class="muted small">El accionista ingresa en la misma dirección de la plataforma con su correo y clave, y solo ve sus certificados firmados y vigentes. La plataforma no envía correos: comunique la clave inicial por un canal seguro; al primer ingreso deberá cambiarla.</p>
  <?php if ($portal): ?><p>Correo de acceso: <strong><?= e($portal['email']) ?></strong> · último ingreso: <?= e(fdatetime($portal['ultimo_acceso']) ?: 'nunca') ?><?= $portal['clave_inicial'] ? ' · ' . badge('Clave inicial sin cambiar', 'warn') : '' ?></p><?php endif; ?>
  <?php if (can('certificados', 'editar')): ?>
  <form method="post" action="<?= e(url('certificados.acceso')) ?>" class="inline-form"><?= csrf_field() ?><input type="hidden" name="person_id" value="<?= $p['id'] ?>"><input type="hidden" name="accion" value="<?= $portal ? 'clave' : 'crear' ?>">
    <input type="email" name="email" required value="<?= e($portal['email'] ?? $p['email']) ?>" placeholder="Correo"><input type="text" name="clave" required minlength="10" placeholder="Clave inicial (mín. 10, letras y números)" autocomplete="new-password">
    <button class="btn sm primary"><?= $portal ? 'Asignar nueva clave inicial' : 'Crear acceso al portal' ?></button></form>
  <?php if ($portal): ?><form method="post" action="<?= e(url('certificados.acceso')) ?>" class="mt"><?= csrf_field() ?><input type="hidden" name="person_id" value="<?= $p['id'] ?>"><input type="hidden" name="accion" value="estado"><button class="btn sm ghost"><?= $portal['activo'] ? 'Desactivar acceso' : 'Reactivar acceso' ?></button></form><?php endif; ?>
  <?php endif; ?>
</div>
<?php endif; ?>

<form method="post" action="<?= e(url('personas.dd')) ?>" enctype="multipart/form-data" class="card" id="ddForm"><?= csrf_field() ?>
  <input type="hidden" name="id" value="<?= $p['id'] ?>">
  <div class="card-head"><h3>Debida diligencia</h3>
    <?php if ($p['dd_fecha']): ?><span class="muted small"><?= e($p['dd_estado'] === 'APROBADA' ? 'Aprobada' : 'Resuelta') ?> el <?= e(fdatetime($p['dd_fecha'])) ?> por <?= e($revisor['nombre'] ?? '') ?></span><?php endif; ?>
  </div>
  <p class="muted small">Marque cada verificación y adjunte su respaldo (PDF o imagen). Sin la lista completa la persona no queda habilitada como socio.</p>
  <table class="tbl checklist"><thead><tr><th style="width:36px"></th><th>Verificación</th><th>Respaldo</th></tr></thead><tbody>
  <?php foreach ($st as $k => $s): ?>
    <tr class="<?= $s['checked'] && $s['doc'] ? 'done' : '' ?>">
      <td><input type="checkbox" name="chk_<?= $k ?>"<?= chk($s['checked']) ?><?= $k === 'rut' || $locked || !$canEdit ? ' disabled' : '' ?>></td>
      <td><?= e($s['label']) ?></td>
      <td><?php if ($s['docType']): ?>
        <?php foreach ($ddDocs[$s['docType']] ?? [] as $d): ?><a class="file-chip" href="<?= e(url('doc', ['id' => $d['id']])) ?>" target="_blank"><?= e($d['nombre_original']) ?></a><?php endforeach; ?>
        <?php if (!$locked && $canEdit): ?><input type="file" name="doc_<?= $k ?>" accept="application/pdf,image/png,image/jpeg" class="small"><?php endif; ?>
        <?php if (!$s['doc']): ?><span class="t-error small">Falta respaldo</span><?php endif; ?>
      <?php else: ?><span class="muted small">Validación automática</span><?php endif; ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody></table>
  <fieldset <?= $locked || !$canEdit ? 'disabled' : '' ?>>
  <div class="grid3 mt">
    <label>¿Es Persona Expuesta Políticamente (PEP)? *<select name="dd_pep"><option value="">Seleccione…</option><option value="NO"<?= sel('NO', $p['dd_pep']) ?>>No</option><option value="SI"<?= sel('SI', $p['dd_pep']) ?>>Sí (diligencia reforzada)</option></select></label>
    <label class="span2">Detalle PEP (cargo, vínculo, período)<input name="dd_pep_detalle" value="<?= e($p['dd_pep_detalle']) ?>"></label>
    <label>Resultado listas ONU / OFAC *<select name="dd_listas_resultado"><option value="">Seleccione…</option><option value="SIN_COINCIDENCIAS"<?= sel('SIN_COINCIDENCIAS', $p['dd_listas_resultado']) ?>>Sin coincidencias</option><option value="CON_COINCIDENCIAS"<?= sel('CON_COINCIDENCIAS', $p['dd_listas_resultado']) ?>>Con coincidencias</option></select></label>
    <label>Fecha de la revisión *<input type="date" name="dd_listas_fecha" value="<?= e($p['dd_listas_fecha']) ?>"></label>
    <label class="span3">Origen de los fondos *<textarea name="dd_origen_fondos" rows="2"><?= e($p['dd_origen_fondos']) ?></textarea></label>
  </div>
  <?php if ($p['tipo'] === 'JURIDICA'): ?>
    <h4>Beneficiarios finales (≥ 10 % de participación o control)</h4>
    <table class="tbl" id="benTable"><thead><tr><th>Nombre</th><th>RUT / ID</th><th>%</th><th>PEP</th></tr></thead><tbody>
      <?php $rowsB = $bens ?: [[]]; foreach ($rowsB as $i => $b): ?>
      <tr><td><input name="ben_nombre[]" value="<?= e($b['nombre'] ?? '') ?>"></td><td><input name="ben_rut[]" value="<?= e(rut_format($b['rut'] ?? '')) ?>"></td><td><input name="ben_pct[]" value="<?= e(isset($b['porcentaje']) ? (string)(float)$b['porcentaje'] : '') ?>" style="width:80px"></td><td><input type="checkbox" name="ben_pep[<?= $i ?>]" value="1"<?= chk($b['es_pep'] ?? 0) ?>></td></tr>
      <?php endforeach; ?>
    </tbody></table>
    <button type="button" class="btn sm" id="addBen">+ Agregar beneficiario</button>
  <?php endif; ?>
  <label class="mt">Observaciones de la diligencia (obligatorio para rechazar)<textarea name="dd_obs" rows="2"><?= e($p['dd_obs']) ?></textarea></label>
  </fieldset>
  <?php if ($canEdit): ?>
  <div class="actions">
    <?php if (!$locked): ?>
      <button class="btn" name="accion" value="guardar">Guardar avance</button>
      <button class="btn danger" name="accion" value="rechazar" data-confirm="¿Rechazar la diligencia? La persona no podrá ingresar como socio.">Rechazar</button>
      <button class="btn primary" name="accion" value="aprobar">Aprobar diligencia</button>
    <?php elseif (is_master()): ?>
      <input type="hidden" name="motivo" value="Reapertura por el Máster">
      <button class="btn" name="accion" value="reabrir" data-confirm="¿Reabrir la diligencia para modificarla?">Reabrir diligencia (Máster)</button>
    <?php endif; ?>
  </div>
  <?php endif; ?>
</form>

<?php $other = array_filter($docs, fn($d) => !str_starts_with($d['tipo'], 'DD_')); if ($other): ?>
<div class="card"><h3>Otros documentos</h3><ul><?php foreach ($other as $d): ?><li><a href="<?= e(url('doc', ['id' => $d['id']])) ?>"><?= e($d['nombre_original']) ?></a></li><?php endforeach; ?></ul></div>
<?php endif; ?>
