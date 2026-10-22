<?php $isRes = $cert['regimen'] === 'RES'; [$el, $ek] = CERT_ESTADOS[$cert['estado']]; ?>
<div class="page-head">
  <div><h1>Certificado N° <?= e($cert['numero']) ?></h1><p class="muted"><?= e($c['razon_social']) ?> · <?= badge($el, $ek) ?> <?= $isRes ? badge('RES · control interno', 'res') : badge('Tradicional', 'trad') ?></p></div>
  <div class="btns"><?= help_button('certificados') ?><a class="btn" href="<?= e(url('certificados', ['c' => $c['id'], 'tab' => 'emitidos'])) ?>">Volver</a></div>
</div>
<?php if ($cert['estado'] === 'SIN_FIRMA'): ?><div class="alert warn"><strong>Sin firma: este certificado aún no tiene validez.</strong> Descárguelo, hágalo firmar en FirmaYa por <?= e($D['representante']['nombre']) ?> y suba aquí el PDF firmado.</div><?php endif; ?>
<?php if (in_array($cert['estado'], ['REEMPLAZADO', 'ANULADO'], true)): ?><div class="alert error"><strong><?= e($el) ?></strong> el <?= e(fdatetime($cert['baja_en'])) ?>: <?= e($cert['baja_motivo']) ?></div><?php endif; ?>
<div class="grid2">
<div class="card"><h3>Contenido</h3>
  <dl class="dl">
    <dt>Titular</dt><dd><strong><?= e($D['titular']['nombre']) ?></strong> · <?= e(rut_format($D['titular']['rut'])) ?></dd>
    <dt>Acciones</dt><dd><strong><?= fnum($cert['cantidad']) ?></strong> de <?= e($D['serie']['nombre']) ?> (<?= e(rtrim(rtrim(number_format((float)$cert['votos_por_accion'], 4, ',', '.'), '0'), ',')) ?> voto/s por acción)</dd>
    <dt>Estado de pago</dt><dd><?= e(['INTEGRAS' => 'Íntegramente pagadas', 'PARCIAL' => 'Parcialmente pagadas: $' . fnum($cert['monto_pagado']) . ' de $' . fnum($cert['monto_total']), 'NO_PAGADAS' => 'No pagadas'][$cert['estado_pago']]) ?><?= $cert['plazo_entero'] ? ' · plazo de entero ' . e(fdate($cert['plazo_entero'])) : '' ?></dd>
    <dt>Gravámenes</dt><dd><?= $isRes ? 'No se certifican (RES)' : ($D['gravamenes'] ? e(implode('; ', array_map(fn($g) => $g['tipo'] . ' a favor de ' . $g['a_favor_de'], $D['gravamenes']))) : 'Libres') ?></dd>
    <dt><?= $isRes ? 'CVE' : 'Folio cert. de venta' ?></dt><dd class="mono"><?= $isRes && $cert['res_pendiente'] ? '<span class="t-error">Registro RES pendiente</span> ' : '' ?><?= e(implode(', ', $isRes ? $D['origen']['cves'] : $D['origen']['folios'])) ?></dd>
    <dt>Inscripción</dt><dd>Folio <?= e($D['inscripcion']['folio']) ?> · <?= e(fdate($D['inscripcion']['fecha'])) ?></dd>
    <dt>Código de verificación</dt><dd class="mono"><?= e($cert['codigo']) ?></dd>
    <dt>Emisión</dt><dd><?= e($cert['lugar']) ?>, <?= e(fdate($cert['fecha_emision'])) ?></dd>
    <dt>Firma</dt><dd><?= e($D['representante']['nombre']) ?> · <?= e($D['representante']['cargo']) ?></dd>
  </dl>
</div>
<div class="card"><h3>Documentos</h3>
  <p><strong>1. Certificado para firmar</strong><br>
  <?php if (can('cert_sin_firma')): ?><a class="btn" href="<?= e(url('certificados.pdf', ['id' => $cert['id']])) ?>" target="_blank">Ver PDF sin firma</a> <a class="btn" href="<?= e(url('certificados.pdf', ['id' => $cert['id'], 'dl' => 1])) ?>">Descargar para FirmaYa</a>
  <?php else: ?><span class="muted small">Su usuario no tiene permiso para ver certificados sin firma.</span><?php endif; ?></p>
  <p class="muted small">SHA-256 sin firma: <span class="mono"><?= e(substr((string)$cert['sha256'], 0, 32)) ?>…</span></p>
  <?php if ($cert['estado'] === 'SIN_FIRMA' && can('certificados', 'editar')): ?>
  <form method="post" enctype="multipart/form-data" action="<?= e(url('certificados.firmado')) ?>" class="subform"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $cert['id'] ?>">
    <h4>2. Subir el certificado firmado en FirmaYa</h4>
    <input type="file" name="firmado" accept="application/pdf" required><button class="btn primary sm">Subir PDF firmado</button>
    <p class="muted small" style="width:100%">Se verifica que el PDF traiga firma electrónica y que corresponda a este certificado (N° y código).</p>
  </form>
  <?php endif; ?>
  <?php if ($cert['firmado_doc_id']): ?>
  <p><strong>Certificado firmado</strong> · cargado el <?= e(fdatetime($cert['firmado_en'])) ?> por <?= e($firmadoPor['nombre'] ?? '') ?><br>
    <a class="btn primary" href="<?= e(url('certificados.pdf', ['id' => $cert['id'], 'v' => 'firmado'])) ?>" target="_blank">Ver PDF firmado</a></p>
  <p class="muted small">SHA-256 firmado: <span class="mono"><?= e(substr((string)$cert['firmado_sha256'], 0, 32)) ?>…</span> · Valide la firma en <a href="<?= e(URL_VALIDA_FIRMAYA) ?>" target="_blank" rel="noopener">FirmaYa</a>.</p>
  <?php endif; ?>
  <p class="small"><?= $isRes ? 'Validación del CVE: <a href="' . e(URL_VALIDA_RES) . '" target="_blank" rel="noopener">Registro de Empresas y Sociedades</a>' : 'Validación del folio: <a href="' . e(URL_VALIDA_PORTAL) . '" target="_blank" rel="noopener">socialis.consorciosalinero.cl</a>' ?></p>
</div>
</div>
<div class="card"><h3>Historial de certificados de esta posición</h3>
  <table class="tbl small"><tr><th>N°</th><th>Emisión</th><th>Estado</th><th>Motivo de baja</th></tr>
  <?php foreach ($hist as $h): ?><tr><td><a href="<?= e(url('certificados.ver', ['id' => $h['id']])) ?>"><?= e($h['numero']) ?></a></td><td><?= e(fdate($h['fecha_emision'])) ?></td><td><?= badge(CERT_ESTADOS[$h['estado']][0], CERT_ESTADOS[$h['estado']][1]) ?></td><td><?= e($h['baja_motivo']) ?></td></tr><?php endforeach; ?></table>
</div>
<?php if (is_master() && in_array($cert['estado'], ['SIN_FIRMA', 'FIRMADO'], true)): ?>
<form method="post" action="<?= e(url('certificados.anular')) ?>" class="inline-form" data-confirm="¿Anular este certificado?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $cert['id'] ?>"><input name="motivo" placeholder="Motivo de la anulación" required><button class="btn ghost">Anular certificado (Máster)</button></form>
<?php endif; ?>
