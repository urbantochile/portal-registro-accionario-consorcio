<div class="page-head"><h1><?= $id ? 'Editar usuario' : 'Nuevo usuario' ?></h1><?= help_button('usuarios') ?></div>
<?php if ($errors): ?><div class="alert error"><ul><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<form method="post" class="form-stack"><?= csrf_field() ?>
<section class="card"><h3>Datos de acceso</h3>
  <div class="grid3">
    <label>Nombre *<input name="nombre" required value="<?= e($u['nombre'] ?? '') ?>"></label>
    <label>Correo (usuario) *<input type="email" name="email" required value="<?= e($u['email'] ?? '') ?>"></label>
    <label><?= $id ? 'Nueva clave (dejar vacío para mantener)' : 'Clave inicial *' ?><input type="text" name="clave" autocomplete="new-password" <?= $id ? '' : 'required' ?> minlength="10"></label>
    <label class="check"><input type="checkbox" name="activo"<?= chk($u['activo'] ?? 1) ?>> Usuario activo</label>
  </div>
</section>
<section class="card"><h3>Permisos por módulo</h3>
  <table class="tbl perm"><thead><tr><th>Módulo</th><th class="c">Ver</th><th class="c">Ver y editar</th></tr></thead><tbody>
  <?php foreach (MODULOS as $k => $l): $p = $perms[$k] ?? null; ?>
    <tr><td><?= e($l) ?></td><td class="c"><input type="checkbox" name="ver[<?= $k ?>]"<?= chk($p) ?>></td><td class="c"><?php if (!in_array($k, MODULOS_SOLO_VER, true)): ?><input type="checkbox" name="editar[<?= $k ?>]"<?= chk($p['editar'] ?? 0) ?>><?php else: ?><span class="muted small">n/a</span><?php endif; ?></td></tr>
  <?php endforeach; ?>
  <tr class="muted"><td>Usuarios y permisos · Respaldos y feriados</td><td class="c" colspan="2">Exclusivo del Máster</td></tr>
  </tbody></table>
  <p class="muted small">«Entrega del Libro de Accionistas» permite generar el PDF del libro de las sociedades tradicionales asignadas. «Certificados» con editar permite emitir, subir el certificado firmado y crear accesos al portal de accionistas; para descargar un certificado aún sin firma se requiere además «Ver certificados sin firma». La bitácora muestra la actividad de todos los usuarios.</p>
</section>
<section class="card"><h3>Sociedades a las que accede</h3>
  <div class="checks-grid">
  <?php foreach ($companies as $c): ?><label class="check"><input type="checkbox" name="companies[]" value="<?= $c['id'] ?>"<?= in_array((int)$c['id'], $comps, true) ? ' checked' : '' ?>> <?= e($c['razon_social']) ?> <?= $c['regimen'] === 'RES' ? badge('RES', 'res') : badge('Trad.', 'trad') ?></label><?php endforeach; ?>
  <?php if (!$companies): ?><p class="muted">Aún no hay sociedades.</p><?php endif; ?>
  </div>
</section>
<div class="actions"><a class="btn" href="<?= e(url('usuarios')) ?>">Cancelar</a><button class="btn primary">Guardar usuario</button></div>
</form>
