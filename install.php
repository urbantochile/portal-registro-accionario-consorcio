<?php
/**
 * Instalador de la Plataforma de Accionistas CONSA.
 * Uso web: abrir https://<subdominio>/install.php una sola vez.
 * Uso CLI: php install.php host base usuario clave [url_base]
 */
declare(strict_types=1);
define('APP_ROOT', __DIR__);
date_default_timezone_set('America/Santiago');
require __DIR__ . '/app/helpers.php';
require __DIR__ . '/app/migrations.php';

$cli = PHP_SAPI === 'cli';
$msg = null; $ok = false;

if (is_file(__DIR__ . '/config.php')) {
    if ($cli) { fwrite(STDERR, "Ya instalado (existe config.php).\n"); exit(1); }
    http_response_code(403);
    exit('<h2>La plataforma ya está instalada.</h2><p>Por seguridad elimine el archivo <code>install.php</code> del servidor.</p>');
}

function do_install(array $in): string
{
    $dsn = 'mysql:host=' . $in['host'] . ';port=' . (int)($in['port'] ?: 3306) . ';dbname=' . $in['name'] . ';charset=utf8mb4';
    $pdo = new PDO($dsn, $in['user'], $in['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $sql = file_get_contents(__DIR__ . '/app/schema.sql');
    foreach (array_filter(array_map('trim', preg_split('/;\s*\n/', preg_replace('/^--.*$/m', '', $sql)))) as $stmt) {
        $pdo->exec($stmt);
    }
    schema_migrate($pdo);
    if ((int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() === 0) {
        $st = $pdo->prepare('INSERT INTO users (email,nombre,password_hash,is_master,activo,clave_inicial,creado_en) VALUES (?,?,?,?,?,?,?)');
        $st->execute(['master@consa.cl', 'Administrador Máster', password_hash('Consa-jr5x1o-D1GpFr', PASSWORD_DEFAULT), 1, 1, 1, date('Y-m-d H:i:s')]);
    }
    $h = $pdo->prepare('INSERT IGNORE INTO holidays (fecha,nombre) VALUES (?,?)');
    for ($y = 2026; $y <= 2030; $y++) foreach (chile_holidays($y) as $f => $n) $h->execute([$f, $n]);
    $pdo->prepare('INSERT INTO audit_log (user_email,accion,detalle,ip,creado_en) VALUES (?,?,?,?,?)')->execute(['sistema', 'INSTALACION', 'Versión ' . '1.2.1', $_SERVER['REMOTE_ADDR'] ?? 'cli', date('Y-m-d H:i:s')]);

    $storage = rtrim($in['storage'] ?: __DIR__ . '/storage', '/');
    foreach ([$storage, $storage . '/docs', $storage . '/backups'] as $d) if (!is_dir($d)) mkdir($d, 0750, true);
    file_put_contents($storage . '/.htaccess', "Require all denied\nDeny from all\n");
    $cfg = "<?php\n// Generado por install.php el " . date('d-m-Y H:i') . "\nreturn " . var_export([
        'db' => ['host' => $in['host'], 'port' => (int)($in['port'] ?: 3306), 'name' => $in['name'], 'user' => $in['user'], 'pass' => $in['pass']],
        'base_url' => rtrim($in['url'], '/'),
        'storage_path' => $storage,
        'timezone' => 'America/Santiago',
        'session_idle_minutes' => 30,
        'debug' => false,
    ], true) . ";\n";
    if (file_put_contents(__DIR__ . '/config.php', $cfg) === false) throw new RuntimeException('No se pudo escribir config.php (permisos de la carpeta).');
    @chmod(__DIR__ . '/config.php', 0640);
    return $storage;
}

if ($cli) {
    [$_, $host, $name, $user, $pass] = array_pad($argv, 5, '');
    $url = $argv[5] ?? 'https://socialis.grupoconsa.cl';
    try { do_install(['host' => $host, 'port' => 3306, 'name' => $name, 'user' => $user, 'pass' => $pass, 'url' => $url, 'storage' => $argv[6] ?? '']); echo "Instalación completa.\n"; }
    catch (Throwable $e) { fwrite(STDERR, 'Error: ' . $e->getMessage() . "\n"); exit(1); }
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        do_install(['host' => trim($_POST['host'] ?? 'localhost'), 'port' => trim($_POST['port'] ?? '3306'), 'name' => trim($_POST['name'] ?? ''), 'user' => trim($_POST['user'] ?? ''),
            'pass' => (string)($_POST['pass'] ?? ''), 'url' => trim($_POST['url'] ?? ''), 'storage' => trim($_POST['storage'] ?? '')]);
        $ok = true;
    } catch (Throwable $e) {
        $msg = $e->getMessage();
    }
}
$checks = [
    'PHP 8.1 o superior' => version_compare(PHP_VERSION, '8.1.0', '>='),
    'Extensión PDO MySQL' => extension_loaded('pdo_mysql'),
    'Extensión ZIP (Excel)' => class_exists('ZipArchive'),
    'Extensión GD (logos en PDF)' => extension_loaded('gd'),
    'Extensión mbstring' => extension_loaded('mbstring'),
    'Extensión fileinfo' => extension_loaded('fileinfo'),
    'Carpeta escribible' => is_writable(__DIR__),
];
?><!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Instalación · Plataforma de Accionistas CONSA</title><link rel="stylesheet" href="assets/app.css"></head>
<body class="login-body"><div class="install card">
<img src="assets/logo-consa.svg" alt="CONSA" style="width:180px"><h1>Instalación · Plataforma de Accionistas CONSA</h1>
<?php if ($ok): ?>
  <div class="alert ok"><strong>Instalación completa.</strong></div>
  <p>Ingrese con <strong>master@consa.cl</strong> y la clave <strong>Consa-jr5x1o-D1GpFr</strong>, y cámbiela de inmediato en «Cambiar clave».</p>
  <p class="alert warn"><strong>Importante:</strong> elimine ahora el archivo <code>install.php</code> desde el Administrador de archivos de SiteGround.</p>
  <a class="btn primary" href="index.php">Ir a la plataforma</a>
<?php else: ?>
  <h3>Requisitos</h3><ul class="req"><?php foreach ($checks as $k => $v): ?><li class="<?= $v ? 't-ok' : 't-error' ?>"><?= $v ? '✔' : '✘' ?> <?= e($k) ?></li><?php endforeach; ?></ul>
  <?php if ($msg): ?><div class="alert error"><?= e($msg) ?></div><?php endif; ?>
  <form method="post" class="form-stack">
    <div class="grid2">
      <label>Servidor MySQL<input name="host" value="<?= e($_POST['host'] ?? 'localhost') ?>"></label>
      <label>Puerto<input name="port" value="<?= e($_POST['port'] ?? '3306') ?>"></label>
      <label>Nombre de la base de datos<input name="name" required value="<?= e($_POST['name'] ?? '') ?>"></label>
      <label>Usuario de la base<input name="user" required value="<?= e($_POST['user'] ?? '') ?>"></label>
      <label>Clave de la base<input type="password" name="pass" value=""></label>
      <label>URL de la plataforma<input name="url" value="<?= e($_POST['url'] ?? 'https://socialis.grupoconsa.cl') ?>"></label>
      <label class="span2">Carpeta de documentos (opcional; recomendado fuera de public_html)<input name="storage" value="<?= e($_POST['storage'] ?? '') ?>" placeholder="<?= e(__DIR__ . '/storage') ?>"></label>
    </div>
    <button class="btn primary">Instalar</button>
  </form>
<?php endif; ?>
</div></body></html>
