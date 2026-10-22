<div class="page-head"><h1>Respaldos y feriados</h1><?= help_button('respaldos') ?></div>
<div class="grid2">
  <div class="card">
    <h3>Descargar respaldo</h3>
    <?php if ($pending): ?><div class="alert warn">Hoy es día hábil y aún no se descarga el respaldo.</div>
    <?php elseif (!$business): ?><div class="alert info small">Hoy no es día hábil (fin de semana o feriado): el respaldo diario no es obligatorio.</div>
    <?php else: ?><div class="alert ok small">El respaldo de hoy ya fue descargado.</div><?php endif; ?>
    <p>Genera un archivo <strong>JSON</strong> con toda la información: sociedades, personas, diligencias, registros de socios, movimientos, usuarios, bitácora y feriados. Con la opción marcada incluye además todos los documentos PDF e imágenes subidos.</p>
    <form method="post" action="<?= e(url('respaldos.descargar')) ?>"><?= csrf_field() ?>
      <label class="check"><input type="checkbox" name="con_documentos" checked> Incluir documentos (respaldo completo)</label>
      <button class="btn primary">Descargar respaldo JSON</button>
    </form>
    <p class="muted small">Guarde el archivo fuera del servidor (computador, nube corporativa o disco externo). Contiene datos personales: resguárdelo.</p>
  </div>
  <div class="card">
    <h3>Restaurar respaldo</h3>
    <div class="alert error small"><strong>Atención:</strong> restaurar reemplaza <u>todos</u> los datos actuales por los del archivo, incluidos usuarios y claves. Antes de restaurar, el sistema guarda automáticamente una copia del estado actual en el servidor.</div>
    <form method="post" enctype="multipart/form-data" action="<?= e(url('respaldos.restaurar')) ?>" data-confirm="¿Seguro que desea reemplazar todos los datos por los del respaldo?"><?= csrf_field() ?>
      <label>Archivo de respaldo (.json) · máximo del servidor: <?= e($maxUpload) ?><input type="file" name="respaldo" accept="application/json,.json" required></label>
      <label>Escriba <strong>RESTAURAR</strong> para confirmar<input name="confirmacion" autocomplete="off" required></label>
      <button class="btn danger">Restaurar</button>
    </form>
  </div>
</div>
<div class="card"><h3>Historial</h3>
  <table class="tbl small"><thead><tr><th>Fecha</th><th>Tipo</th><th>Usuario</th><th>Archivo</th><th class="r">Tamaño</th><th>Documentos</th></tr></thead><tbody>
  <?php foreach ($hist as $h): ?><tr><td><?= e(fdatetime($h['creado_en'])) ?></td><td><?= $h['tipo'] === 'DESCARGA' ? badge('Descarga', 'ok') : badge('Restauración', 'warn') ?></td><td><?= e($h['nombre'] ?? '—') ?></td><td class="mono"><?= e($h['archivo']) ?></td><td class="r"><?= fnum(ceil(((int)$h['bytes']) / 1024)) ?> KB</td><td><?= $h['con_documentos'] ? 'Sí' : 'No' ?></td></tr><?php endforeach; ?>
  <?php if (!$hist): ?><tr><td colspan="6" class="muted">Sin respaldos.</td></tr><?php endif; ?>
  </tbody></table>
</div>
<div class="card"><div class="card-head"><h3>Feriados <?= $year ?></h3>
  <form method="get" class="inline-form"><input type="hidden" name="r" value="respaldos"><select name="anio" onchange="this.form.submit()"><?php for ($y = 2026; $y <= 2035; $y++): ?><option<?= sel($y, $year) ?>><?= $y ?></option><?php endfor; ?></select></form></div>
  <p class="muted small">El aviso de respaldo diario no aparece sábados, domingos ni en estos feriados. Se precargaron los feriados legales nacionales 2026–2030; agregue feriados extraordinarios (elecciones, leyes especiales, feriados regionales) o quite los que no correspondan.</p>
  <table class="tbl small"><thead><tr><th>Fecha</th><th>Día</th><th>Feriado</th><th></th></tr></thead><tbody>
  <?php $dias = ['', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado', 'domingo']; foreach ($holidays as $h): ?>
    <tr><td><?= e(fdate($h['fecha'])) ?></td><td><?= $dias[(int)date('N', strtotime($h['fecha']))] ?></td><td><?= e($h['nombre']) ?></td>
    <td><form method="post" action="<?= e(url('respaldos.feriado')) ?>" data-confirm="¿Quitar este feriado?"><?= csrf_field() ?><input type="hidden" name="accion" value="quitar"><input type="hidden" name="fecha" value="<?= e($h['fecha']) ?>"><button class="btn sm ghost">Quitar</button></form></td></tr>
  <?php endforeach; ?>
  <?php if (!$holidays): ?><tr><td colspan="4" class="muted">No hay feriados cargados para <?= $year ?>.</td></tr><?php endif; ?>
  </tbody></table>
  <div class="grid2 mt">
    <form method="post" action="<?= e(url('respaldos.feriado')) ?>" class="inline-form"><?= csrf_field() ?><input type="hidden" name="accion" value="agregar"><input type="date" name="fecha" required><input name="nombre" placeholder="Nombre del feriado" required><button class="btn sm">Agregar feriado</button></form>
    <form method="post" action="<?= e(url('respaldos.feriado')) ?>" class="inline-form"><?= csrf_field() ?><input type="hidden" name="accion" value="cargar"><input type="hidden" name="anio" value="<?= $year ?>"><button class="btn sm">Cargar feriados legales de <?= $year ?></button></form>
  </div>
</div>
