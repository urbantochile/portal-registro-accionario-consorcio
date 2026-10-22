<?php
$nav = [
    ['dashboard', 'Panel', null, '▦'],
    ['sociedades', 'Sociedades', 'sociedades', '▣'],
    ['personas', 'Personas y diligencia', 'socios', '◉'],
    ['socios', 'Registro de socios', 'socios', '☰'],
    ['accionistas', 'Registro de accionistas', 'accionistas', '⇄'],
    ['certificados', 'Certificados', 'certificados', '✎'],
    ['gravamenes', 'Gravámenes', 'gravamenes', '⚖'],
    ['bitacora', 'Bitácora', 'bitacora', '◷'],
    ['usuarios', 'Usuarios y permisos', 'usuarios', '⚿'],
    ['respaldos', 'Respaldos y feriados', 'respaldos', '⛁'],
    ['manual', 'Manual de usuario', null, '?'],
];
$cur = explode('.', (string)($_GET['r'] ?? 'dashboard'))[0];
$flashes = $_SESSION['flash'] ?? [];
unset($_SESSION['flash']);
?><!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e(($title ?? '') ? $title . ' · ' : '') ?>Accionistas CONSA</title>
<link rel="stylesheet" href="assets/app.css?v=<?= APP_VERSION ?>">
<link rel="icon" href="assets/favicon.svg">
</head>
<body>
<div class="shell">
  <aside class="side">
    <a class="brand" href="<?= e(url('dashboard')) ?>"><img src="assets/logo-consa-blanco.svg" alt="CONSA"><small>Plataforma de Accionistas</small></a>
    <nav>
      <?php foreach ($nav as [$r, $label, $mod, $ico]): if ($mod && !can($mod)) continue; ?>
        <a href="<?= e(url($r)) ?>" class="<?= $cur === $r ? 'on' : '' ?>"><span class="ico"><?= $ico ?></span><?= e($label) ?></a>
      <?php endforeach; ?>
    </nav>
    <div class="side-foot">v<?= APP_VERSION ?> · <?= e(date('d-m-Y')) ?></div>
  </aside>
  <main class="main">
    <header class="top">
      <div class="crumbs"><?= e($title ?? '') ?></div>
      <div class="who">
        <span class="role <?= is_master() ? 'master' : '' ?>"><?= is_master() ? 'MÁSTER' : 'USUARIO' ?></span>
        <span><?= e($user['nombre'] ?? '') ?></span>
        <a href="<?= e(url('cuenta')) ?>">Cambiar clave</a>
        <a href="<?= e(url('logout')) ?>">Salir</a>
      </div>
    </header>
    <?php if (is_master() && backup_pending_today()): ?>
      <div class="alert warn strong">⚠ Aún no se ha descargado el respaldo de hoy (<?= e(date('d-m-Y')) ?>, día hábil). <a href="<?= e(url('respaldos')) ?>">Descargar respaldo ahora</a></div>
    <?php endif; ?>
    <?php if (!empty($user['clave_inicial'])): ?>
      <div class="alert info">Está usando la clave inicial. Por seguridad, <a href="<?= e(url('cuenta')) ?>">cámbiela aquí</a>.</div>
    <?php endif; ?>
    <?php foreach ($flashes as [$t, $m]): ?>
      <div class="alert <?= e($t) ?>"><?= e($m) ?></div>
    <?php endforeach; ?>
    <div class="content"><?= $content ?></div>
  </main>
</div>
<script src="assets/app.js?v=<?= APP_VERSION ?>"></script>
</body>
</html>
