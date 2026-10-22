<div class="page-head"><h1>Actualización de la base de datos</h1></div>
<div class="card narrow" style="max-width:760px">
  <p>Se instalaron archivos de la versión <strong><?= e(APP_VERSION) ?></strong>. Antes de seguir usando la plataforma hay que actualizar la base de datos:</p>
  <ul><?php foreach ($pending as $v => $m): ?><li><?= e($m['titulo']) ?></li><?php endforeach; ?></ul>
  <div class="alert warn"><strong>Paso 1:</strong> descargue un respaldo completo antes de actualizar. <a href="<?= e(url('respaldos')) ?>">Ir a Respaldos</a></div>
  <form method="post"><?= csrf_field() ?>
    <label class="check"><input type="checkbox" name="respaldo_ok" required> Ya descargué un respaldo completo de hoy</label>
    <button class="btn primary mt">Paso 2: Actualizar base de datos</button>
  </form>
  <p class="muted small mt">Los demás usuarios verán «Plataforma en actualización» hasta que se complete. La actualización no borra datos: solo agrega tablas y columnas.</p>
</div>
