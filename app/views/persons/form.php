<?php $v = fn($k) => e((string)($p[$k] ?? '')); $tipo = $p['tipo'] ?? (getp('tipo') ?: 'NATURAL'); ?>
<div class="page-head"><h1><?= $id ? 'Editar persona' : 'Nueva persona' ?></h1><?= help_button('personas') ?></div>
<?php if ($errors): ?><div class="alert error"><strong>Revise el formulario:</strong><ul><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<form method="post" class="form-stack" id="personForm"><?= csrf_field() ?>
<section class="card">
  <h3>Tipo y RUT</h3>
  <div class="grid3">
    <?php if ($id): ?>
      <label>Tipo<input value="<?= $tipo === 'NATURAL' ? 'Persona natural' : 'Persona jurídica' ?>" disabled><input type="hidden" name="tipo" value="<?= e($tipo) ?>"></label>
      <label>RUT<input value="<?= e(rut_format($p['rut'])) ?>" disabled></label>
      <p class="muted small">El RUT y el tipo no se modifican. Si están errados, registre una nueva persona.</p>
    <?php else: ?>
      <label>Tipo *<select name="tipo" id="tipoPersona"><option value="NATURAL"<?= sel('NATURAL', $tipo) ?>>Persona natural</option><option value="JURIDICA"<?= sel('JURIDICA', $tipo) ?>>Persona jurídica</option></select></label>
      <label>RUT *<input name="rut" data-rut required value="<?= e(rut_format($p['rut'] ?? '')) ?>" placeholder="12.345.678-5"><small class="rut-msg"></small></label>
    <?php endif; ?>
  </div>
</section>
<section class="card t-natural">
  <h3>Persona natural</h3>
  <div class="grid3">
    <label>Nombres *<input name="nombres" value="<?= $v('nombres') ?>"></label>
    <label>Apellido paterno *<input name="apellido_paterno" value="<?= $v('apellido_paterno') ?>"></label>
    <label>Apellido materno<input name="apellido_materno" value="<?= $v('apellido_materno') ?>"></label>
    <label>Nacionalidad *<input name="nacionalidad" value="<?= $v('nacionalidad') ?: 'Chilena' ?>"></label>
    <label>Fecha de nacimiento * <small class="muted">(solo mayores de edad)</small><input type="date" name="fecha_nacimiento" max="<?= e(date('Y-m-d', strtotime('-18 years'))) ?>" value="<?= $v('fecha_nacimiento') ?>"></label>
    <label>Estado civil *<select name="estado_civil"><option value="">Seleccione…</option><?php foreach (ESTADOS_CIVILES as $ec): ?><option<?= sel($ec, $p['estado_civil'] ?? '') ?>><?= e($ec) ?></option><?php endforeach; ?></select></label>
    <label class="span2">Profesión u oficio *<input name="profesion" class="prof-nat" value="<?= $v('profesion') ?>"></label>
  </div>
</section>
<section class="card t-juridica">
  <h3>Persona jurídica</h3>
  <div class="grid3">
    <label class="span2">Razón social *<input name="razon_social" value="<?= $v('razon_social') ?>"></label>
    <label>Tipo de sociedad *<input name="jur_tipo_sociedad" value="<?= $v('jur_tipo_sociedad') ?>" placeholder="SpA, S.A., Ltda., EIRL…"></label>
    <label>Fecha de constitución<input type="date" name="jur_fecha_constitucion" value="<?= $v('jur_fecha_constitucion') ?>"></label>
    <label>País de constitución / nacionalidad<input name="nacionalidad" class="nac-jur" value="<?= $v('nacionalidad') ?: 'Chilena' ?>"></label>
    <label>Giro *<input name="profesion" class="prof-jur" value="<?= $v('profesion') ?>"></label>
    <label class="span2">Representante legal *<input name="jur_rep_nombre" value="<?= $v('jur_rep_nombre') ?>"></label>
    <label>RUT representante *<input name="jur_rep_rut" data-rut value="<?= e(rut_format($p['jur_rep_rut'] ?? '')) ?>"></label>
  </div>
</section>
<section class="card">
  <h3>Domicilio y contacto</h3>
  <div class="grid4">
    <label class="span2">Calle *<input name="calle" required value="<?= $v('calle') ?>"></label>
    <label>Número *<input name="numero" required value="<?= $v('numero') ?>"></label>
    <label>Depto. / oficina<input name="depto" value="<?= $v('depto') ?>"></label>
    <label>Comuna *<input name="comuna" required value="<?= $v('comuna') ?>"></label>
    <label>Ciudad *<input name="ciudad" required value="<?= $v('ciudad') ?>"></label>
    <label>Región<input name="region" value="<?= $v('region') ?>"></label>
    <label>País<input name="pais" value="<?= $v('pais') ?: 'Chile' ?>"></label>
    <label class="span2">Correo electrónico *<input type="email" name="email" required value="<?= $v('email') ?>"></label>
    <label>Teléfono *<input name="telefono" required value="<?= $v('telefono') ?>"></label>
  </div>
</section>
<div class="actions"><a class="btn" href="<?= e($id ? url('personas.ver', ['id' => $id]) : url('personas')) ?>">Cancelar</a><button class="btn primary"><?= $id ? 'Guardar cambios' : 'Registrar persona' ?></button></div>
</form>
