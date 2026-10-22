<!doctype html>
<html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Ingreso · Plataforma de Accionistas CONSA</title>
<link rel="stylesheet" href="assets/app.css?v=<?= APP_VERSION ?>"><link rel="icon" href="assets/favicon.svg"></head>
<body class="login-body">
<div class="login-wrap">
  <div class="login-art">
    <img class="login-logo" src="assets/logo-consa-blanco.svg" alt="CONSA · Consorcio Salinero del Tarapacá S.A.">
    <h1>Plataforma de Registro de Accionistas</h1>
    <p>Registro de socios, libro de accionistas y control societario multisociedad, para sociedades RES y del sistema registral tradicional.</p>
    <ul class="ticks"><li>Debida diligencia previa al ingreso</li><li>Saldos de acciones automáticos</li><li>Bitácora de todas las actuaciones</li></ul>
  </div>
  <form method="post" class="login-card" autocomplete="off">
    <img class="card-logo" src="assets/logo-consa.svg" alt="CONSA"><h2>Ingreso</h2>
    <?php foreach (($_SESSION['flash'] ?? []) as [$t, $m]): ?><div class="alert <?= e($t) ?>"><?= e($m) ?></div><?php endforeach; unset($_SESSION['flash']); ?>
    <?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
    <?= csrf_field() ?>
    <label>Correo electrónico<input type="email" name="email" required autofocus value="<?= e(post('email')) ?>"></label>
    <label>Clave<input type="password" name="password" required></label>
    <button class="btn primary block">Ingresar</button>
    <p class="muted small">Acceso exclusivo para usuarios autorizados y accionistas con acceso habilitado. Todas las acciones quedan registradas.</p>
  </form>
</div>
</body></html>
