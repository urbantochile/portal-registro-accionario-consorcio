<?php $isRes = $c['regimen'] === 'RES'; ?>
<div class="page-head">
  <div><h1><?= e($c['razon_social']) ?></h1><p class="muted"><?= e(rut_format($c['rut'])) ?> · <?= e(TIPOS_SOCIALES[$c['tipo_social']] ?? '') ?> · <?= $isRes ? badge('RES', 'res') : badge('Tradicional', 'trad') ?> <?= $c['estado'] === 'ACTIVA' ? badge('Activa', 'ok') : badge('Inactiva', 'neutral') ?></p></div>
  <div class="btns"><?= help_button('sociedades') ?>
    <?php if (can('socios')): ?><a class="btn" href="<?= e(url('socios', ['c' => $c['id']])) ?>">Registro de socios</a><?php endif; ?>
    <?php if (can('accionistas')): ?><a class="btn" href="<?= e(url('accionistas', ['c' => $c['id']])) ?>">Registro de accionistas</a><?php endif; ?>
    <?php if (can('sociedades', 'editar')): ?><a class="btn primary" href="<?= e(url('sociedades.form', ['id' => $c['id']])) ?>">Editar</a><?php endif; ?>
  </div>
</div>
<?php if ($miss['base']): ?><div class="alert error"><strong>Respaldo documental incompleto:</strong> <?= e(implode(' · ', $miss['base'])) ?></div><?php endif; ?>
<?php if ($miss['cumplimiento']): ?><div class="alert warn"><strong>Cumplimiento Ley 20.393 / 21.595 pendiente:</strong><ul class="cols2"><?php foreach ($miss['cumplimiento'] as $m): ?><li><?= e($m) ?></li><?php endforeach; ?></ul></div><?php endif; ?>

<div class="grid2">
  <div class="card"><h3>Identificación</h3>
    <dl class="dl">
      <dt>Nombre de fantasía</dt><dd><?= e($c['nombre_fantasia']) ?></dd>
      <dt>Giro</dt><dd><?= e($c['giro']) ?></dd>
      <dt>Constitución</dt><dd><?= e(fdate($c['fecha_constitucion'])) ?></dd>
      <dt>Domicilio</dt><dd><?= e(implode(', ', array_filter([$c['domicilio'], $c['comuna'], $c['ciudad'], $c['region']]))) ?></dd>
      <dt>Contacto</dt><dd><?= e(implode(' · ', array_filter([$c['email'], $c['telefono']]))) ?></dd>
      <dt>Capital social</dt><dd><?= $c['capital_social'] !== null ? '$' . fnum($c['capital_social']) : '' ?></dd>
      <dt>Acciones según estatuto</dt><dd><?= fnum($c['total_acciones_estatuto']) ?></dd>
    </dl>
  </div>
  <div class="card"><h3>Inscripción · <?= $isRes ? 'RES' : 'Sistema tradicional' ?></h3>
    <dl class="dl">
    <?php if ($isRes): ?>
      <dt>Fecha inscripción RES</dt><dd><?= e(fdate($c['res_fecha_inscripcion'])) ?></dd>
      <dt>CVE</dt><dd class="mono"><?= e($c['res_cve']) ?></dd>
      <dt>N° de atención</dt><dd><?= e($c['res_numero_atencion']) ?></dd>
      <dt>Naturaleza del registro</dt><dd>Registro de administración interna. No acredita titularidad; acceso solo para usuarios autorizados. Exportación en Excel.</dd>
    <?php else: ?>
      <dt>Escritura</dt><dd><?= e($c['notaria']) ?> · <?= e(fdate($c['fecha_escritura'])) ?><?= $c['repertorio'] ? ' · Rep. ' . e($c['repertorio']) : '' ?></dd>
      <dt>Inscripción</dt><dd>Fojas <?= e($c['fojas']) ?> N° <?= e($c['numero_inscripcion']) ?> del año <?= e($c['anio_inscripcion']) ?>, <?= e($c['cbr']) ?></dd>
      <dt>Diario Oficial</dt><dd><?= e(fdate($c['fecha_diario_oficial'])) ?></dd>
      <dt>Naturaleza del registro</dt><dd>Documento válido de control de accionistas. Se emite el PDF «Libro de Accionistas» para el representante legal.</dd>
    <?php endif; ?>
    </dl>
  </div>
  <div class="card"><h3>Representante legal</h3>
    <dl class="dl">
      <dt>Nombre</dt><dd><?= e($c['rep_nombre']) ?> · <?= e(rut_format($c['rep_rut'])) ?></dd>
      <dt>Cargo</dt><dd><?= e($c['rep_cargo']) ?></dd>
      <dt>Nacionalidad / profesión</dt><dd><?= e(implode(' · ', array_filter([$c['rep_nacionalidad'], $c['rep_profesion']]))) ?></dd>
      <dt>Contacto</dt><dd><?= e(implode(' · ', array_filter([$c['rep_email'], $c['rep_telefono']]))) ?></dd>
      <dt>Poderes</dt><dd><?= $c['poderes_fuente'] === 'ACTA_DIRECTORIO' ? 'Sesión de directorio' : 'Certificado de poderes' ?> de <?= e(fdate($c['poderes_fecha'])) ?> <?= e($c['poderes_detalle']) ?></dd>
    </dl>
  </div>
  <div class="card"><h3>Imagen corporativa</h3>
    <div class="brandbox">
      <?php if ($c['logo_path']): ?><img src="<?= e(url('doc.logo', ['c' => $c['id']])) ?>" alt="Logotipo"><?php else: ?><span class="muted">Sin logotipo</span><?php endif; ?>
      <div class="swatches">
        <div><span class="sw" style="background:<?= e($c['color1_hex'] ?: '#820503') ?>"></span><?= e($c['color1_hex']) ?><br><small><?= e($c['color1_pantone']) ?></small></div>
        <div><span class="sw" style="background:<?= e($c['color2_hex'] ?: '#FE4101') ?>"></span><?= e($c['color2_hex']) ?><br><small><?= e($c['color2_pantone']) ?></small></div>
      </div>
    </div>
  </div>
</div>

<div class="card"><h3>Series de acciones <small class="muted">(<?= count($series) ?>)</small></h3><p class="muted small">Una sociedad puede tener varias series (A, B, C, D u otros nombres), cada una con sus votos por acción, acciones autorizadas y preferencias.</p>
  <table class="tbl"><thead><tr><th>Serie</th><th class="r">Votos por acción</th><th class="r">Autorizadas</th><th class="r">Emitidas</th><th class="r">Disponibles</th><th>Preferencias</th><?php if (can('sociedades', 'editar')): ?><th></th><?php endif; ?></tr></thead><tbody>
  <?php foreach ($series as $s): $em = $issued[(int)$s['id']] ?? 0; ?>
    <tr><td><strong><?= e($s['nombre']) ?></strong></td><td class="r"><?= e(rtrim(rtrim(number_format((float)$s['votos_por_accion'], 4, ',', '.'), '0'), ',')) ?></td>
    <td class="r"><?= $s['acciones_autorizadas'] !== null ? fnum($s['acciones_autorizadas']) : '<span class="muted">sin límite</span>' ?></td><td class="r"><?= fnum($em) ?></td>
    <td class="r"><?= $s['acciones_autorizadas'] !== null ? fnum((int)$s['acciones_autorizadas'] - $em) : '—' ?></td><td><?= e($s['preferencias']) ?></td>
    <?php if (can('sociedades', 'editar')): ?><td><button class="btn sm" type="button" data-open="serie<?= $s['id'] ?>">Editar</button>
      <?php if ($em === 0 && count($series) > 1): ?><form method="post" action="<?= e(url('sociedades.seriebaja')) ?>" style="display:inline" data-confirm="¿Eliminar la serie <?= e($s['nombre']) ?>? Solo es posible si no tiene movimientos."><?= csrf_field() ?><input type="hidden" name="id" value="<?= $c['id'] ?>"><input type="hidden" name="serie_id" value="<?= $s['id'] ?>"><button class="btn sm ghost">Eliminar</button></form><?php endif; ?></td><?php endif; ?></tr>
    <?php if (can('sociedades', 'editar')): ?>
    <tr id="serie<?= $s['id'] ?>" class="hidden"><td colspan="7"><form method="post" action="<?= e(url('sociedades.serie')) ?>" class="inline-form"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $c['id'] ?>"><input type="hidden" name="serie_id" value="<?= $s['id'] ?>">
      <input name="nombre" value="<?= e($s['nombre']) ?>" placeholder="Nombre"><input name="votos_por_accion" value="<?= e((string)(float)$s['votos_por_accion']) ?>" placeholder="Votos"><input name="acciones_autorizadas" data-num value="<?= e(fnum($s['acciones_autorizadas'])) ?>" placeholder="Autorizadas"><input name="preferencias" value="<?= e($s['preferencias']) ?>" placeholder="Preferencias"><button class="btn primary sm">Guardar</button></form></td></tr>
    <?php endif; ?>
  <?php endforeach; ?></tbody></table>
  <?php if (can('sociedades', 'editar')): ?>
  <form method="post" action="<?= e(url('sociedades.serie')) ?>" class="inline-form mt"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $c['id'] ?>">
    <strong>Nueva serie:</strong><input name="nombre" placeholder="Nombre (ej.: Serie C, Preferentes)" required maxlength="60"><input name="votos_por_accion" value="1" placeholder="Votos por acción"><input name="acciones_autorizadas" data-num placeholder="Acciones autorizadas"><input name="preferencias" placeholder="Preferencias"><button class="btn sm">Agregar serie</button></form>
  <?php endif; ?>
</div>

<div class="card"><h3>Cumplimiento Ley 20.393 / 21.595</h3>
  <dl class="dl cols">
    <dt>MPD adoptado</dt><dd><?= $c['mpd_adoptado'] ? 'Sí, aprobado el ' . e(fdate($c['mpd_fecha_aprobacion'])) . ' por ' . e($c['mpd_organo']) : '<span class="t-error">No</span>' ?></dd>
    <dt>Encargado de Prevención</dt><dd><?= e($c['enc_nombre']) ?> <?= e(rut_format($c['enc_rut'])) ?> <?= $c['enc_email'] ? '· ' . e($c['enc_email']) : '' ?></dd>
    <dt>Designación</dt><dd><?= e(fdate($c['enc_fecha_designacion'])) ?> <?= $c['enc_vigencia_hasta'] ? '— vigente hasta ' . e(fdate($c['enc_vigencia_hasta'])) : '' ?></dd>
    <dt>Canal de denuncias</dt><dd><?= e($c['canal_denuncias']) ?></dd>
    <dt>Matriz de riesgos</dt><dd><?= e(fdate($c['matriz_fecha'])) ?></dd>
    <dt>Código de ética</dt><dd><?= e(fdate($c['codigo_etica_fecha'])) ?></dd>
    <dt>Última capacitación</dt><dd><?= e(fdate($c['capacitacion_fecha'])) ?></dd>
    <dt>Certificación MPD</dt><dd><?= $c['cert_mpd'] ? e($c['cert_mpd_entidad']) . ' · vigente hasta ' . e(fdate($c['cert_mpd_vigencia'])) : 'No certificado (opcional)' ?></dd>
    <?php if ($c['cumplimiento_obs']): ?><dt>Observaciones</dt><dd><?= nl2br(e($c['cumplimiento_obs'])) ?></dd><?php endif; ?>
  </dl>
</div>

<div class="card"><h3>Documentos de respaldo</h3>
  <table class="tbl"><thead><tr><th>Tipo</th><th>Archivo</th><th>Subido</th><th>SHA-256</th><th></th></tr></thead><tbody>
  <?php foreach ($docs as $d): if ($d['tipo'] === 'LOGO') continue; ?>
    <tr><td><?= e(DOCS_SOCIEDAD[$d['tipo']][0] ?? $d['tipo']) ?></td><td><a href="<?= e(url('doc', ['id' => $d['id']])) ?>" target="_blank"><?= e($d['nombre_original']) ?></a> <small class="muted"><?= fnum(ceil($d['bytes'] / 1024)) ?> KB</small></td>
    <td class="nowrap"><?= e(fdatetime($d['subido_en'])) ?></td><td class="mono small"><?= e(substr($d['sha256'], 0, 16)) ?>…</td>
    <td><?php if (can('sociedades', 'editar')): ?><form method="post" action="<?= e(url('sociedades.docbaja')) ?>" data-confirm="¿Marcar este documento como no vigente?"><?= csrf_field() ?><input type="hidden" name="doc" value="<?= $d['id'] ?>"><button class="btn sm ghost">Dar de baja</button></form><?php endif; ?></td></tr>
  <?php endforeach; ?></tbody></table>
  <?php if (can('sociedades', 'editar')): ?>
  <form method="post" enctype="multipart/form-data" action="<?= e(url('sociedades.doc')) ?>" class="inline-form mt"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $c['id'] ?>">
    <strong>Agregar documento:</strong>
    <select name="tipo"><?php foreach (DOCS_SOCIEDAD as $k => [$l]): ?><option value="<?= $k ?>"><?= e($l) ?></option><?php endforeach; ?></select>
    <input type="file" name="archivo" accept="application/pdf" required><button class="btn sm">Subir PDF</button></form>
  <?php endif; ?>
</div>
<?php if (is_master()): ?>
<form method="post" action="<?= e(url('sociedades.estado')) ?>" class="mt" data-confirm="¿Cambiar el estado de la sociedad?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $c['id'] ?>"><button class="btn ghost"><?= $c['estado'] === 'ACTIVA' ? 'Desactivar sociedad' : 'Reactivar sociedad' ?></button></form>
<?php endif; ?>
