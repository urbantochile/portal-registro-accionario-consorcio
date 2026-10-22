<?php
declare(strict_types=1);

function ctl_login_index(): void
{
    if (current_user()) redirect('dashboard');
    if (!empty($_SESSION['pid'])) redirect('portal');
    $error = null;
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        $email = mb_strtolower(post('email'));
        $pass = (string)($_POST['password'] ?? '');
        $u = one('SELECT * FROM users WHERE email=?', [$email]);
        $_SESSION['audit_email'] = $email;
        if ($u && $u['bloqueado_hasta'] && strtotime($u['bloqueado_hasta']) > time()) {
            $error = 'Usuario bloqueado temporalmente por intentos fallidos. Intente después de las ' . date('H:i', strtotime($u['bloqueado_hasta'])) . '.';
            audit('LOGIN_BLOQUEADO', 'user', (int)$u['id']);
        } elseif ($u && (int)$u['activo'] === 1 && password_verify($pass, $u['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['uid'] = (int)$u['id'];
            q('UPDATE users SET intentos_fallidos=0, bloqueado_hasta=NULL, ultimo_acceso=? WHERE id=?', [now(), $u['id']]);
            audit('LOGIN', 'user', (int)$u['id']);
            redirect('dashboard');
        } elseif (!$u && ($pu = portal_user_by_email($email))) {
            if ($pu['bloqueado_hasta'] && strtotime($pu['bloqueado_hasta']) > time()) {
                $error = 'Acceso bloqueado temporalmente por intentos fallidos. Intente después de las ' . date('H:i', strtotime($pu['bloqueado_hasta'])) . '.';
            } elseif ((int)$pu['activo'] === 1 && password_verify($pass, $pu['password_hash'])) {
                session_regenerate_id(true);
                $_SESSION['pid'] = (int)$pu['id'];
                q('UPDATE portal_users SET intentos_fallidos=0, bloqueado_hasta=NULL, ultimo_acceso=? WHERE id=?', [now(), $pu['id']]);
                audit('PORTAL_LOGIN', 'portal_user', (int)$pu['id']);
                redirect('portal');
            } else {
                $n = (int)$pu['intentos_fallidos'] + 1;
                $lock = $n >= 5 ? date('Y-m-d H:i:s', time() + 15 * 60) : null;
                q('UPDATE portal_users SET intentos_fallidos=?, bloqueado_hasta=? WHERE id=?', [$lock ? 0 : $n, $lock, $pu['id']]);
                audit('PORTAL_LOGIN_FALLIDO', 'portal_user', (int)$pu['id']);
                $error = 'Correo o clave incorrectos.';
                usleep(400000);
            }
        } else {
            if ($u) {
                $n = (int)$u['intentos_fallidos'] + 1;
                $lock = $n >= 5 ? date('Y-m-d H:i:s', time() + 15 * 60) : null;
                q('UPDATE users SET intentos_fallidos=?, bloqueado_hasta=? WHERE id=?', [$lock ? 0 : $n, $lock, $u['id']]);
            }
            audit('LOGIN_FALLIDO', 'user', $u ? (int)$u['id'] : null);
            $error = 'Correo o clave incorrectos.';
            usleep(400000);
        }
    }
    render('login', ['error' => $error, 'bare' => true]);
}

function portal_user_by_email(string $email): ?array
{
    try {
        return one('SELECT * FROM portal_users WHERE email=?', [$email]);
    } catch (Throwable $e) {
        return null; // tabla aún no creada (actualización pendiente)
    }
}

function ctl_logout_index(): void
{
    if (!empty($_SESSION['pid'])) audit('PORTAL_LOGOUT', 'portal_user', (int)$_SESSION['pid']);
    elseif (current_user()) audit('LOGOUT', 'user', (int)current_user()['id']);
    $_SESSION = [];
    session_destroy();
    header('Location: ' . url('login'));
    exit;
}

function password_problem(string $p): ?string
{
    if (mb_strlen($p) < 10) return 'La clave debe tener al menos 10 caracteres.';
    if (!preg_match('/[A-Za-z]/', $p) || !preg_match('/[0-9]/', $p)) return 'La clave debe combinar letras y números.';
    return null;
}

function ctl_cuenta_index(): void
{
    $u = require_login();
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        $actual = (string)($_POST['actual'] ?? '');
        $nueva = (string)($_POST['nueva'] ?? '');
        $rep = (string)($_POST['repetir'] ?? '');
        if (!password_verify($actual, $u['password_hash'])) {
            flash('error', 'La clave actual no es correcta.');
        } elseif ($nueva !== $rep) {
            flash('error', 'La nueva clave y su repetición no coinciden.');
        } elseif ($nueva === $actual) {
            flash('error', 'La nueva clave debe ser distinta de la actual.');
        } elseif ($p = password_problem($nueva)) {
            flash('error', $p);
        } else {
            q('UPDATE users SET password_hash=?, clave_inicial=0 WHERE id=?', [password_hash($nueva, PASSWORD_DEFAULT), $u['id']]);
            audit('CAMBIO_CLAVE', 'user', (int)$u['id']);
            flash('ok', 'Clave actualizada correctamente.');
            redirect('dashboard');
        }
        redirect('cuenta');
    }
    render('account', ['title' => 'Mi cuenta · Cambio de clave']);
}
