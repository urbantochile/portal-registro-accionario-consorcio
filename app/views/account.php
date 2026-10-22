<div class="page-head"><h1>Mi cuenta</h1></div>
<form method="post" class="card narrow">
  <?= csrf_field() ?>
  <h3>Cambio de clave</h3>
  <p class="muted"><?= e($user['nombre']) ?> · <?= e($user['email']) ?></p>
  <label>Clave actual<input type="password" name="actual" required></label>
  <label>Nueva clave<input type="password" name="nueva" required minlength="10"></label>
  <label>Repetir nueva clave<input type="password" name="repetir" required minlength="10"></label>
  <p class="muted small">Mínimo 10 caracteres, combinando letras y números.</p>
  <button class="btn primary">Guardar nueva clave</button>
</form>
