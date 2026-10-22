<?php $isRes = $c['regimen'] === 'RES'; $fv = fn($k) => e((string)($f[$k] ?? '')); ?>
<div class="page-head"><div><h1>Registrar movimiento de acciones</h1><p class="muted"><?= e($c['razon_social']) ?> · <?= $isRes ? badge('RES', 'res') : badge('Tradicional', 'trad') ?></p></div><?= help_button('accionistas') ?></div>
<?php if ($errors): ?><div class="alert error"><strong>No se registró el movimiento:</strong><ul><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<?php if (!$members): ?><div class="alert warn">El registro de socios de esta sociedad está vacío. Para ser accionista, la persona debe estar primero en el registro de socios. <a href="<?= e(url('socios', ['c' => $c['id']])) ?>">Ir al registro de socios</a></div><?php endif; ?>
<form method="post" enctype="multipart/form-data" class="form-stack" id="movForm"><?= csrf_field() ?><input type="hidden" name="company_id" value="<?= $c['id'] ?>">
<section class="card">
  <h3>1. Operación</h3>
  <div class="regimen-pick">
    <label class="radio-card"><input type="radio" name="tipo" value="EMISION"<?= ($f['tipo'] ?? '') === 'EMISION' ? ' checked' : '' ?>><span><strong>Emisión / suscripción</strong><small>La sociedad emite acciones a un socio (aumenta las acciones suscritas).</small></span></label>
    <label class="radio-card"><input type="radio" name="tipo" value="TRANSFERENCIA"<?= ($f['tipo'] ?? '') !== 'EMISION' ? ' checked' : '' ?>><span><strong>Transferencia (compraventa)</strong><small>Un accionista vende a otro socio. La venta de uno es la compra del otro.</small></span></label>
  </div>
  <div class="grid3">
    <label>Serie *<select name="series_id" id="serieSel" required><?php foreach ($series as $s): ?><option value="<?= $s['id'] ?>"<?= sel($s['id'], $f['series_id'] ?? '') ?>><?= e($s['nombre']) ?></option><?php endforeach; ?></select></label>
    <label>Fecha de la transferencia *<input type="date" name="fecha" required max="<?= e(today()) ?>" value="<?= $fv('fecha') ?>"></label>
    <label>Cantidad de acciones *<input name="cantidad" data-num required value="<?= e(fnum($f['cantidad'] ?? '')) ?>"></label>
  </div>
</section>
<section class="card">
  <h3>2. Partes</h3>
  <div class="grid2">
    <label class="only-transfer">Vendedor (accionista con saldo) *<select name="vendedor_member_id" id="vendSel"><option value="">Seleccione…</option>
      <?php foreach ($members as $m): $tot = array_sum($bal[(int)$m['mid']] ?? []); if ($tot <= 0) continue; ?>
        <option value="<?= $m['mid'] ?>" data-bal='<?= e(json_encode($bal[(int)$m['mid']] ?? [])) ?>' data-grav='<?= e(json_encode(array_map(fn($gs) => array_sum(array_column($gs, 'cantidad')), $liens[(int)$m['mid']] ?? []))) ?>'<?= sel($m['mid'], $f['vendedor'] ?? '') ?>><?= e(person_name($m)) ?> · <?= e(rut_format($m['rut'])) ?></option>
      <?php endforeach; ?></select><small id="vendSaldo" class="muted"></small><small id="vendGrav" class="t-error"></small></label>
    <label class="only-emision">Vendedor<input value="La sociedad (emisión de acciones)" disabled></label>
    <label>Comprador (desde el registro de socios) *<select name="comprador_member_id" required><option value="">Seleccione…</option>
      <?php foreach ($members as $m): ?><option value="<?= $m['mid'] ?>"<?= sel($m['mid'], $f['comprador'] ?? '') ?><?= $m['dd_estado'] !== 'APROBADA' ? ' disabled' : '' ?>><?= str_pad((string)$m['socio_n'], 2, '0', STR_PAD_LEFT) ?> · <?= e(person_name($m)) ?> · <?= e(rut_format($m['rut'])) ?><?= $m['dd_estado'] !== 'APROBADA' ? ' (diligencia no aprobada)' : '' ?></option><?php endforeach; ?>
    </select></label>
  </div>
</section>
<section class="card">
  <h3>3. Títulos</h3>
  <div class="grid4">
    <label class="only-transfer">N° título inutilizado<input name="titulo_inutilizado" value="<?= $fv('titulo_inutilizado') ?>"></label>
    <label>N° título nuevo del comprador<input name="titulo_nuevo_comprador" value="<?= $fv('titulo_nuevo_comprador') ?>"></label>
    <label class="only-transfer">N° título nuevo del vendedor<input name="titulo_nuevo_vendedor" value="<?= $fv('titulo_nuevo_vendedor') ?>"></label>
    <label>Precio total (CLP, opcional)<input name="precio_total" data-num value="<?= e(fnum($f['precio_total'] ?? '')) ?>"></label>
    <label class="span4">Observaciones<input name="observaciones" value="<?= $fv('observaciones') ?>"></label>
  </div>
</section>
<section class="card">
  <h3>4. Certificado entregado por la sociedad</h3>
  <p class="muted small"><?= $isRes ? 'Sociedad RES: ingrese el <strong>CVE</strong> (código de verificación electrónico, 12 caracteres) del certificado de accionista emitido por el Registro de Empresas y Sociedades, y suba el PDF.' : 'Sociedad tradicional: ingrese el <strong>código del certificado</strong> emitido por la sociedad y suba el PDF.' ?> Esta plataforma no emite certificados.</p>
  <div class="grid2">
    <?php if ($isRes): ?><label class="check span2"><input type="checkbox" name="res_pendiente" id="resPend"<?= chk($f['res_pendiente'] ?? false) ?>> <strong>Registro RES pendiente</strong>: el comprador aún no inscribe la compra en el portal del RES (se registra sin CVE y se completa después)</label><?php endif; ?>
    <label class="cert-comp"><?= $isRes ? 'CVE' : 'Código de certificado' ?> del comprador *<input name="cert_codigo" required value="<?= $fv('cert_codigo') ?>" <?= $isRes ? 'pattern="[A-Za-z0-9]{12}" title="12 caracteres alfanuméricos"' : '' ?>></label>
    <label class="cert-comp">Certificado del comprador (PDF) *<input type="file" name="cert_pdf" accept="application/pdf" required></label>
    <label class="only-transfer"><?= $isRes ? 'CVE' : 'Código' ?> del certificado del vendedor (opcional, si le queda saldo)<input name="cert_vendedor_codigo" value="<?= $fv('cert_vendedor_codigo') ?>"></label>
    <label class="only-transfer">Certificado del vendedor (PDF, opcional)<input type="file" name="cert_vendedor_pdf" accept="application/pdf"></label>
  </div>
</section>
<div class="actions"><a class="btn" href="<?= e(url('accionistas', ['c' => $c['id']])) ?>">Cancelar</a><button class="btn primary">Registrar movimiento</button></div>
</form>
