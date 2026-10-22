<?php
declare(strict_types=1);
require_once __DIR__ . '/auth.php';

function master_only(): void
{
    require_login();
    if (!is_master()) {
        http_response_code(403);
        render('error', ['title' => 'Solo Máster', 'msg' => 'Esta sección está reservada al usuario Máster.']);
        exit;
    }
}

function ctl_usuarios_index(): void
{
    master_only();
    $rows = all('SELECT * FROM users ORDER BY is_master DESC, nombre');
    foreach ($rows as &$u) {
        $u['_perms'] = all('SELECT * FROM user_permissions WHERE user_id=?', [$u['id']]);
        $u['_comp'] = all('SELECT c.razon_social FROM user_companies uc JOIN companies c ON c.id=uc.company_id WHERE uc.user_id=?', [$u['id']]);
    }
    render('users/index', ['rows' => $rows, 'title' => 'Usuarios y permisos']);
}

function ctl_usuarios_form(): void
{
    master_only();
    $id = (int)getp('id', 0);
    $u = $id ? one('SELECT * FROM users WHERE id=?', [$id]) : null;
    if ($id && (!$u || $u['is_master'])) { flash('error', 'El usuario Máster se administra desde «Cambiar clave».'); redirect('usuarios'); }
    $perms = [];
    $comps = [];
    if ($u) {
        foreach (all('SELECT * FROM user_permissions WHERE user_id=?', [$id]) as $p) $perms[$p['modulo']] = $p;
        $comps = array_map('intval', array_column(all('SELECT company_id FROM user_companies WHERE user_id=?', [$id]), 'company_id'));
    }
    $errors = [];
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        $nombre = nn(post('nombre'));
        $email = mb_strtolower((string)post('email'));
        $clave = (string)($_POST['clave'] ?? '');
        $activo = isset($_POST['activo']) ? 1 : 0;
        if (!$nombre) $errors[] = 'Nombre obligatorio.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Correo inválido.';
        elseif (val('SELECT id FROM users WHERE email=? AND id<>?', [$email, $id])) $errors[] = 'Ya existe un usuario con ese correo.';
        if (!$id || $clave !== '') { if ($p = password_problem($clave)) $errors[] = $p; }
        $newPerms = [];
        foreach (MODULOS as $k => $_) {
            $ver = isset($_POST['ver'][$k]); $ed = isset($_POST['editar'][$k]) && !in_array($k, MODULOS_SOLO_VER, true);
            if ($ver || $ed) $newPerms[$k] = ['ver' => 1, 'editar' => $ed ? 1 : 0];
        }
        $newComps = array_map('intval', (array)($_POST['companies'] ?? []));
        if (!$newPerms) $errors[] = 'Asigne al menos un módulo.';
        if (array_intersect(array_keys($newPerms), ['sociedades', 'socios', 'accionistas', 'libro_pdf', 'certificados', 'cert_sin_firma', 'gravamenes']) && !$newComps) $errors[] = 'Asigne al menos una sociedad.';
        if (!$errors) {
            db()->beginTransaction();
            if ($id) {
                q('UPDATE users SET nombre=?, email=?, activo=? WHERE id=?', [$nombre, $email, $activo, $id]);
                if ($clave !== '') q('UPDATE users SET password_hash=?, clave_inicial=1, intentos_fallidos=0, bloqueado_hasta=NULL WHERE id=?', [password_hash($clave, PASSWORD_DEFAULT), $id]);
            } else {
                q('INSERT INTO users (email,nombre,password_hash,is_master,activo,clave_inicial,creado_en,creado_por) VALUES (?,?,?,?,?,?,?,?)', [$email, $nombre, password_hash($clave, PASSWORD_DEFAULT), 0, $activo, 1, now(), current_user()['id']]);
                $id = (int)db()->lastInsertId();
            }
            q('DELETE FROM user_permissions WHERE user_id=?', [$id]);
            foreach ($newPerms as $k => $p) q('INSERT INTO user_permissions (user_id,modulo,ver,editar) VALUES (?,?,?,?)', [$id, $k, $p['ver'], $p['editar']]);
            q('DELETE FROM user_companies WHERE user_id=?', [$id]);
            foreach ($newComps as $cid) q('INSERT INTO user_companies (user_id,company_id) VALUES (?,?)', [$id, $cid]);
            db()->commit();
            audit($u ? 'USUARIO_EDITADO' : 'USUARIO_CREADO', 'user', $id, null, ['email' => $email, 'activo' => $activo, 'permisos' => $newPerms, 'sociedades' => $newComps, 'clave_cambiada' => $clave !== '']);
            flash('ok', 'Usuario guardado.' . ($clave !== '' ? ' Comunique la clave inicial al usuario por un canal seguro; se le pedirá cambiarla.' : ''));
            redirect('usuarios');
        }
        $u = array_merge($u ?? [], ['nombre' => $nombre, 'email' => $email, 'activo' => $activo]);
        $perms = $newPerms;
        $comps = $newComps;
    }
    render('users/form', ['u' => $u, 'id' => $id, 'perms' => $perms, 'comps' => $comps, 'companies' => all('SELECT * FROM companies ORDER BY razon_social'), 'errors' => $errors, 'title' => $id ? 'Editar usuario' : 'Nuevo usuario']);
}
