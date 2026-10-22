<h1>Mis certificados de accionista</h1>
<p class="muted">Aquí están los certificados vigentes y firmados electrónicamente por el representante legal de cada sociedad en que usted es accionista.</p>
<?php if (!$certs): ?><div class="card"><p class="muted">Aún no tiene certificados firmados disponibles. Si compró acciones recientemente, el certificado aparecerá cuando la sociedad lo emita y firme.</p></div><?php endif; ?>
<div class="portal-grid">
<?php foreach ($certs as $ce): $res = $ce['c_regimen'] === 'RES'; ?>
  <div class="card portal-cert">
    <div class="pc-head"><strong><?= e($ce['razon_social']) ?></strong><span class="muted small">RUT <?= e(rut_format($ce['c_rut'])) ?></span></div>
    <div class="pc-num"><?= fnum($ce['cantidad']) ?><small> acciones · <?= e($ce['serie']) ?></small></div>
    <dl class="dl"><dt>Certificado N°</dt><dd><?= e($ce['numero']) ?></dd><dt>Emitido</dt><dd><?= e(fdate($ce['fecha_emision'])) ?></dd><dt>Código</dt><dd class="mono"><?= e($ce['codigo']) ?></dd></dl>
    <?php if ($res): ?><p class="alert info small">Certificado de control interno (sociedad RES). No reemplaza el certificado emitido por el Registro de Empresas y Sociedades.</p><?php endif; ?>
    <div class="btns"><a class="btn primary" href="<?= e(url('portal.pdf', ['id' => $ce['id']])) ?>" target="_blank">Ver certificado</a><a class="btn" href="<?= e(url('portal.pdf', ['id' => $ce['id'], 'dl' => 1])) ?>">Descargar PDF</a></div>
    <p class="small muted">Validar firma: <a href="<?= e(URL_VALIDA_FIRMAYA) ?>" target="_blank" rel="noopener">FirmaYa</a> · <?= $res ? 'Validar CVE: <a href="' . e(URL_VALIDA_RES) . '" target="_blank" rel="noopener">Registro de Empresas y Sociedades</a>' : 'Validar folio: <a href="' . e(URL_VALIDA_PORTAL) . '" target="_blank" rel="noopener">socialis.grupoconsa.cl</a>' ?></p>
  </div>
<?php endforeach; ?>
</div>
<div class="card"><h3>Preguntas frecuentes</h3><div class="faq">
<details><summary>¿Por qué no veo un certificado?</summary><p>Solo se muestran certificados firmados electrónicamente y vigentes. Si hubo una compra, venta o anotación de gravamen, la sociedad emite un nuevo certificado y el anterior deja de mostrarse.</p></details>
<details><summary>¿Cómo compruebo que el certificado es auténtico?</summary><p>Suba el PDF en <?= e(URL_VALIDA_FIRMAYA) ?> para validar la firma electrónica. En sociedades RES puede validar el CVE en el Registro de Empresas y Sociedades; en las demás, el folio del certificado de venta en socialis.grupoconsa.cl.</p></details>
<details><summary>Olvidé mi clave</summary><p>Contacte a la administración de la sociedad para que le asigne una nueva clave inicial.</p></details>
</div></div>
