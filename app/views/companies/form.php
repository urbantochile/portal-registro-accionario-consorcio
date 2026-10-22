<?php
$v = fn($k) => e((string)($c[$k] ?? ''));
$reg = $c['regimen'] ?? '';
?>
<div class="page-head"><h1><?= $id ? 'Editar sociedad' : 'Nueva sociedad' ?></h1><?= help_button('sociedades') ?></div>
<?php if ($errors): ?><div class="alert error"><strong>Revise el formulario:</strong><ul><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<form method="post" enctype="multipart/form-data" class="form-stack" id="companyForm">
<?= csrf_field() ?>

<section class="card">
  <h3><span class="step">1</span> Identificación de la sociedad</h3>
  <div class="grid3">
    <label class="span2">Razón social *<input name="razon_social" required value="<?= $v('razon_social') ?>"></label>
    <label>RUT *<input name="rut" required data-rut value="<?= e(rut_format($c['rut'] ?? '')) ?>" placeholder="76.123.456-7"></label>
    <label>Nombre de fantasía<input name="nombre_fantasia" value="<?= $v('nombre_fantasia') ?>"></label>
    <label>Tipo social *<select name="tipo_social" required><option value="">Seleccione…</option><?php foreach (TIPOS_SOCIALES as $k => $l): ?><option value="<?= $k ?>"<?= sel($k, $c['tipo_social'] ?? '') ?>><?= e($l) ?></option><?php endforeach; ?></select></label>
    <label>Fecha de constitución *<input type="date" name="fecha_constitucion" required value="<?= $v('fecha_constitucion') ?>"></label>
    <label class="span3">Giro / objeto<input name="giro" value="<?= $v('giro') ?>"></label>
    <label class="span2">Domicilio *<input name="domicilio" required value="<?= $v('domicilio') ?>" placeholder="Calle, número, oficina"></label>
    <label>Comuna *<input name="comuna" required value="<?= $v('comuna') ?>"></label>
    <label>Ciudad<input name="ciudad" value="<?= $v('ciudad') ?>"></label>
    <label>Región<input name="region" value="<?= $v('region') ?>"></label>
    <label>Teléfono<input name="telefono" value="<?= $v('telefono') ?>"></label>
    <label class="span2">Correo electrónico<input type="email" name="email" value="<?= $v('email') ?>"></label>
  </div>
</section>

<section class="card">
  <h3><span class="step">2</span> Régimen de constitución e inscripción</h3>
  <div class="regimen-pick">
    <?php foreach (REGIMENES as $k => $l): ?>
      <label class="radio-card"><input type="radio" name="regimen" value="<?= $k ?>" required<?= $reg === $k ? ' checked' : '' ?>><span><strong><?= $k === 'RES' ? 'RES' : 'Tradicional' ?></strong><small><?= e($l) ?></small></span></label>
    <?php endforeach; ?>
  </div>
  <div class="reg-block" data-reg="TRADICIONAL">
    <p class="muted small">Datos de la escritura de constitución, su inscripción en el Registro de Comercio del Conservador de Bienes Raíces y la publicación del extracto.</p>
    <div class="grid3">
      <label class="span2">Notaría *<input name="notaria" value="<?= $v('notaria') ?>" placeholder="Notaría de Santiago de don/doña …"></label>
      <label>Fecha escritura *<input type="date" name="fecha_escritura" value="<?= $v('fecha_escritura') ?>"></label>
      <label>Repertorio N°<input name="repertorio" value="<?= $v('repertorio') ?>"></label>
      <label class="span2">Conservador de Bienes Raíces *<input name="cbr" value="<?= $v('cbr') ?>" placeholder="Conservador de Bienes Raíces de Santiago"></label>
      <label>Fojas *<input name="fojas" value="<?= $v('fojas') ?>"></label>
      <label>Número *<input name="numero_inscripcion" value="<?= $v('numero_inscripcion') ?>"></label>
      <label>Año *<input name="anio_inscripcion" value="<?= $v('anio_inscripcion') ?>"></label>
      <label>Publicación Diario Oficial *<input type="date" name="fecha_diario_oficial" value="<?= $v('fecha_diario_oficial') ?>"></label>
    </div>
  </div>
  <div class="reg-block" data-reg="RES">
    <p class="muted small">Datos de la inscripción en el Registro de Empresas y Sociedades (Ley 20.659). El registro de accionistas de estas sociedades es de administración y no acredita titularidad.</p>
    <div class="grid3">
      <label>Fecha de inscripción RES *<input type="date" name="res_fecha_inscripcion" value="<?= $v('res_fecha_inscripcion') ?>"></label>
      <label>CVE constitución / estatuto actualizado *<input name="res_cve" value="<?= $v('res_cve') ?>" placeholder="Ej.: CRpSnTiGLXyA"></label>
      <label>N° de atención / actuación<input name="res_numero_atencion" value="<?= $v('res_numero_atencion') ?>"></label>
    </div>
  </div>
</section>

<section class="card">
  <h3><span class="step">3</span> Capital y series de acciones</h3>
  <div class="grid3">
    <label>Capital social (CLP)<input name="capital_social" data-num value="<?= e(fnum($c['capital_social'] ?? '')) ?>"></label>
    <label>Total de acciones según estatuto<input name="total_acciones_estatuto" data-num value="<?= e(fnum($c['total_acciones_estatuto'] ?? '')) ?>"></label>
  </div>
  <?php if (!$id):
    $sn = (array)($_POST['serie_nombre'] ?? ['Única']);
    $sv = (array)($_POST['serie_votos'] ?? ['1']);
    $sa = (array)($_POST['serie_autorizadas'] ?? ['']);
    $sp = (array)($_POST['serie_preferencias'] ?? ['']); ?>
  <h4>Series de acciones</h4>
  <p class="muted small">Defina una o varias series con el nombre que indique el estatuto: «Única», «Serie A», «Serie B», «C», «Preferentes», etc. Luego podrá agregar, editar o eliminar series (si no tienen movimientos) desde la ficha de la sociedad.</p>
  <table class="tbl" id="seriesTable"><thead><tr><th>Nombre de la serie *</th><th style="width:130px">Votos por acción</th><th style="width:170px">Acciones autorizadas</th><th>Preferencias / características</th><th style="width:40px"></th></tr></thead><tbody>
  <?php foreach ($sn as $i => $n): ?>
    <tr><td><input name="serie_nombre[]" value="<?= e((string)$n) ?>" maxlength="60"></td><td><input name="serie_votos[]" value="<?= e((string)($sv[$i] ?? '1')) ?>"></td><td><input name="serie_autorizadas[]" data-num value="<?= e((string)($sa[$i] ?? '')) ?>"></td><td><input name="serie_preferencias[]" value="<?= e((string)($sp[$i] ?? '')) ?>"></td><td><button type="button" class="btn sm ghost" data-del-row title="Quitar">✕</button></td></tr>
  <?php endforeach; ?>
  </tbody></table>
  <button type="button" class="btn sm" id="addSerie">+ Agregar serie</button>
  <?php endif; ?>
</section>

<section class="card">
  <h3><span class="step">4</span> Representante legal y poderes</h3>
  <div class="grid3">
    <label class="span2">Nombre completo *<input name="rep_nombre" required value="<?= $v('rep_nombre') ?>"></label>
    <label>RUT *<input name="rep_rut" required data-rut value="<?= e(rut_format($c['rep_rut'] ?? '')) ?>"></label>
    <label>Cargo *<input name="rep_cargo" required value="<?= $v('rep_cargo') ?>" placeholder="Gerente General"></label>
    <label>Nacionalidad<input name="rep_nacionalidad" value="<?= $v('rep_nacionalidad') ?>"></label>
    <label>Profesión<input name="rep_profesion" value="<?= $v('rep_profesion') ?>"></label>
    <label>Correo<input type="email" name="rep_email" value="<?= $v('rep_email') ?>"></label>
    <label>Teléfono<input name="rep_telefono" value="<?= $v('rep_telefono') ?>"></label>
    <label>Domicilio<input name="rep_domicilio" value="<?= $v('rep_domicilio') ?>"></label>
    <label>Los poderes constan en *<select name="poderes_fuente" required id="poderesFuente">
      <option value="">Seleccione…</option>
      <option value="CERT_PODERES"<?= sel('CERT_PODERES', $c['poderes_fuente'] ?? '') ?>>Certificado de poderes</option>
      <option value="ACTA_DIRECTORIO"<?= sel('ACTA_DIRECTORIO', $c['poderes_fuente'] ?? '') ?>>Última sesión de directorio (sin certificado de poderes)</option>
    </select></label>
    <label>Fecha del certificado / sesión *<input type="date" name="poderes_fecha" required value="<?= $v('poderes_fecha') ?>"></label>
    <label>Detalle (notaría, repertorio, CVE)<input name="poderes_detalle" value="<?= $v('poderes_detalle') ?>"></label>
  </div>
</section>

<section class="card">
  <h3><span class="step">5</span> Respaldo documental <?= $id ? '<small class="muted">(adjunte solo si reemplaza o agrega)</small>' : '' ?></h3>
  <div class="grid3">
    <label>Estatuto social <?= $id ? '' : '*' ?> (PDF)<input type="file" name="doc_ESTATUTO" accept="application/pdf"></label>
    <label class="pod pod-CERT_PODERES">Certificado de poderes <?= $id ? '' : '*' ?> (PDF)<input type="file" name="doc_CERT_PODERES" accept="application/pdf"></label>
    <label class="pod pod-ACTA_DIRECTORIO">Acta última sesión de directorio <?= $id ? '' : '*' ?> (PDF)<input type="file" name="doc_ACTA_DIRECTORIO" accept="application/pdf"></label>
  </div>
</section>

<section class="card">
  <h3><span class="step">6</span> Imagen corporativa</h3>
  <p class="muted small">Se usan en el PDF «Libro de Accionistas» (sociedades del sistema tradicional). Logotipo PNG o JPG hasta 2 MB; se recomienda PNG con fondo transparente.</p>
  <div class="grid3">
    <label>Logotipo<input type="file" name="logo" accept="image/png,image/jpeg"></label>
    <?php if (!empty($c['logo_path'])): ?><div class="logo-prev"><img src="<?= e(url('doc.logo', ['c' => $id])) ?>" alt="logo"></div><?php endif; ?>
  </div>
  <div class="grid4">
    <label>Color primario HEX<span class="color-in"><input type="color" data-sync="color1_hex" value="<?= e($c['color1_hex'] ?? '#820503') ?>"><input name="color1_hex" id="color1_hex" value="<?= e($c['color1_hex'] ?? '#820503') ?>"></span></label>
    <label>Pantone primario<input name="color1_pantone" value="<?= $v('color1_pantone') ?>" placeholder="PANTONE 7687 C"></label>
    <label>Color secundario HEX<span class="color-in"><input type="color" data-sync="color2_hex" value="<?= e($c['color2_hex'] ?? '#FE4101') ?>"><input name="color2_hex" id="color2_hex" value="<?= e($c['color2_hex'] ?? '#FE4101') ?>"></span></label>
    <label>Pantone secundario<input name="color2_pantone" value="<?= $v('color2_pantone') ?>" placeholder="PANTONE 2727 C"></label>
  </div>
</section>

<section class="card">
  <h3><span class="step">7</span> Cumplimiento normativo · Ley 20.393 y Ley 21.595 (Delitos Económicos)</h3>
  <p class="muted small">Si falta algún antecedente, la sociedad queda operativa pero marcada «cumplimiento pendiente» con alerta en el panel.</p>
  <div class="grid3">
    <label class="check"><input type="checkbox" name="mpd_adoptado"<?= chk($c['mpd_adoptado'] ?? 0) ?>> La sociedad adoptó un Modelo de Prevención de Delitos (MPD)</label>
    <label>Fecha aprobación MPD<input type="date" name="mpd_fecha_aprobacion" value="<?= $v('mpd_fecha_aprobacion') ?>"></label>
    <label>Órgano que aprobó<input name="mpd_organo" value="<?= $v('mpd_organo') ?>" placeholder="Directorio / Administración"></label>
    <label>Encargado de Prevención<input name="enc_nombre" value="<?= $v('enc_nombre') ?>"></label>
    <label>RUT Encargado<input name="enc_rut" data-rut value="<?= e(rut_format($c['enc_rut'] ?? '')) ?>"></label>
    <label>Correo Encargado<input type="email" name="enc_email" value="<?= $v('enc_email') ?>"></label>
    <label>Fecha designación<input type="date" name="enc_fecha_designacion" value="<?= $v('enc_fecha_designacion') ?>"></label>
    <label>Vigencia de la designación hasta<input type="date" name="enc_vigencia_hasta" value="<?= $v('enc_vigencia_hasta') ?>"></label>
    <label>Canal de denuncias (URL o descripción)<input name="canal_denuncias" value="<?= $v('canal_denuncias') ?>"></label>
    <label>Fecha matriz de riesgos<input type="date" name="matriz_fecha" value="<?= $v('matriz_fecha') ?>"></label>
    <label>Fecha código de ética<input type="date" name="codigo_etica_fecha" value="<?= $v('codigo_etica_fecha') ?>"></label>
    <label>Última capacitación<input type="date" name="capacitacion_fecha" value="<?= $v('capacitacion_fecha') ?>"></label>
    <label class="check"><input type="checkbox" name="cert_mpd"<?= chk($c['cert_mpd'] ?? 0) ?>> MPD certificado (opcional)</label>
    <label>Entidad certificadora<input name="cert_mpd_entidad" value="<?= $v('cert_mpd_entidad') ?>"></label>
    <label>Vigencia certificación<input type="date" name="cert_mpd_vigencia" value="<?= $v('cert_mpd_vigencia') ?>"></label>
    <label class="span3">Observaciones de cumplimiento<textarea name="cumplimiento_obs" rows="2"><?= $v('cumplimiento_obs') ?></textarea></label>
  </div>
  <h4>Documentos de cumplimiento (PDF)</h4>
  <div class="grid3">
    <?php foreach (array_merge(DOCS_COMPLIANCE, ['CERT_MPD']) as $t): ?>
      <label><?= e(DOCS_SOCIEDAD[$t][0]) ?><input type="file" name="doc_<?= $t ?>" accept="application/pdf"></label>
    <?php endforeach; ?>
  </div>
</section>

<div class="actions">
  <a class="btn" href="<?= e($id ? url('sociedades.ver', ['id' => $id]) : url('sociedades')) ?>">Cancelar</a>
  <button class="btn primary"><?= $id ? 'Guardar cambios' : 'Registrar sociedad' ?></button>
</div>
</form>
