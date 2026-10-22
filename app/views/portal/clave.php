<h1>Cambiar clave</h1>
<?php if ((int)$pu['clave_inicial'] === 1): ?><div class="alert info">Por seguridad, antes de ver sus certificados debe reemplazar la clave inicial.</div><?php endif; ?>
<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
<form method="post" class="card narrow"><?= csrf_field() ?>
  <label>Clave actual<input type="password" name="actual" required></label>
  <label>Nueva clave<input type="password" name="nueva" required minlength="10"></label>
  <label>Repetir nueva clave<input type="password" name="repetir" required minlength="10"></label>
  <p class="muted small">Mínimo 10 caracteres, combinando letras y números.</p>
  <button class="btn primary">Guardar</button>
</form>
