<?php
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';
require __DIR__ . '/app/ledger.php';
require __DIR__ . '/app/migrations.php';

$r = preg_replace('/[^a-z_.]/', '', (string)($_GET['r'] ?? 'dashboard'));
[$ctrl, $action] = array_pad(explode('.', $r, 2), 2, 'index');

$map = [
    'login' => 'auth', 'logout' => 'auth', 'cuenta' => 'auth',
    'dashboard' => 'dashboard',
    'sociedades' => 'companies',
    'personas' => 'persons',
    'socios' => 'members',
    'accionistas' => 'shares',
    'usuarios' => 'users',
    'respaldos' => 'backups',
    'bitacora' => 'auditlog',
    'manual' => 'manual',
    'doc' => 'files',
    'certificados' => 'certificates',
    'gravamenes' => 'liens',
    'portal' => 'portal',
    'actualizar' => 'upgrade',
];
if (!isset($map[$ctrl])) { http_response_code(404); $ctrl = 'dashboard'; $action = 'index'; }
// Sesión de accionista (portal): solo puede usar el portal
if (!empty($_SESSION['pid'])) {
    if (!in_array($ctrl, ['portal', 'logout'], true)) { $ctrl = 'portal'; $action = 'index'; }
} elseif ($ctrl === 'portal') {
    redirect('login');
}
if (!in_array($ctrl, ['login', 'portal', 'logout'], true)) {
    if (!current_user()) redirect('login');
    // Actualización de base de datos pendiente
    if (schema_pending(db())) {
        if (is_master()) {
            if (!in_array($ctrl, ['actualizar', 'respaldos', 'logout', 'cuenta'], true)) { $ctrl = 'actualizar'; $action = 'index'; }
        } else {
            render('error', ['title' => 'Plataforma en actualización', 'msg' => 'El usuario Máster debe completar la actualización de la base de datos antes de continuar. Intente más tarde.']);
            exit;
        }
    }
}
require __DIR__ . '/app/controllers/' . $map[$ctrl] . '.php';
$fn = 'ctl_' . $ctrl . '_' . $action;
if (!function_exists($fn)) { http_response_code(404); render('error', ['title' => 'Página no encontrada', 'msg' => 'La ruta solicitada no existe.']); exit; }
try {
    $fn();
} catch (Throwable $ex) {
    error_log('[accionistas] ' . $ex->getMessage() . ' @ ' . $ex->getFile() . ':' . $ex->getLine());
    http_response_code(500);
    render('error', ['title' => 'Error', 'msg' => !empty($CONFIG['debug']) ? $ex->getMessage() . ' (' . basename($ex->getFile()) . ':' . $ex->getLine() . ')' : 'Ocurrió un error inesperado. Si persiste, informe al administrador.']);
}
