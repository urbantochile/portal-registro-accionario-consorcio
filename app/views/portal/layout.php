<?php $flashes = $_SESSION['flash'] ?? []; unset($_SESSION['flash']); ?><!doctype html>
<html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Portal del accionista · CONSA</title><link rel="stylesheet" href="assets/app.css?v=<?= APP_VERSION ?>"><link rel="icon" href="assets/favicon.svg"></head>
<body class="portal-body">
<header class="portal-top"><img src="assets/logo-consa-blanco.svg" alt="CONSA"><div><strong>Portal del accionista</strong><span><?= e(person_name($pu)) ?> · <?= e(rut_format($pu['rut'])) ?></span></div>
<nav><a href="<?= e(url('portal')) ?>">Mis certificados</a><a href="<?= e(url('portal.clave')) ?>">Cambiar clave</a><a href="<?= e(url('logout')) ?>">Salir</a></nav></header>
<main class="portal-main">
<?php foreach ($flashes as [$t, $m]): ?><div class="alert <?= e($t) ?>"><?= e($m) ?></div><?php endforeach; ?>
<?= $content ?>
</main>
<footer class="portal-foot">Plataforma de Accionistas CONSA · Acceso personal e intransferible. Todas las consultas quedan registradas.</footer>
</body></html>
