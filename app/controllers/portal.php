<?php
declare(strict_types=1);
require_once __DIR__ . '/../certificado_pdf.php';

function portal_user(): array
{
    $pu = one('SELECT pu.*, p.tipo, p.nombres, p.apellido_paterno, p.apellido_materno, p.razon_social, p.rut FROM portal_users pu JOIN persons p ON p.id=pu.person_id WHERE pu.id=? AND pu.activo=1', [(int)($_SESSION['pid'] ?? 0)]);
    if (!$pu) { $_SESSION = []; session_destroy(); header('Location: ' . url('login')); exit; }
    return $pu;
}

function portal_render(string $view, array $vars): void
{
    $vars['pu'] = $vars['pu'] ?? portal_user();
    extract($vars);
    ob_start();
    require __DIR__ . '/../views/portal/' . $view . '.php';
    $content = ob_get_clean();
    require __DIR__ . '/../views/portal/layout.php';
}

function ctl_portal_index(): void
{
    $pu = portal_user();
    if ((int)$pu['clave_inicial'] === 1) redirect('portal.clave');
    $certs = all("SELECT ce.*, c.razon_social, c.rut AS c_rut, c.regimen AS c_regimen, s.nombre AS serie
        FROM certificates ce JOIN members m ON m.id=ce.member_id JOIN companies c ON c.id=ce.company_id JOIN series s ON s.id=ce.series_id
        WHERE m.person_id=? AND ce.estado='FIRMADO' ORDER BY c.razon_social, s.id", [$pu['person_id']]);
    portal_render('index', ['certs' => $certs, 'pu' => $pu]);
}

function ctl_portal_pdf(): void
{
    $pu = portal_user();
    $ce = one("SELECT ce.* FROM certificates ce JOIN members m ON m.id=ce.member_id WHERE ce.id=? AND m.person_id=? AND ce.estado='FIRMADO'", [(int)getp('id'), $pu['person_id']]);
    if (!$ce) { http_response_code(404); exit('Certificado no disponible.'); }
    $d = one('SELECT * FROM documents WHERE id=?', [$ce['firmado_doc_id']]);
    audit('PORTAL_CERTIFICADO_DESCARGADO', 'certificate', (int)$ce['id'], (int)$ce['company_id'], ['numero' => $ce['numero'], 'portal_user' => $pu['id']]);
    require_once __DIR__ . '/files.php';
    send_file(STORAGE . '/' . $d['ruta'], 'application/pdf', $d['nombre_original'], getp('dl') !== '1');
}

function ctl_portal_clave(): void
{
    $pu = portal_user();
    require_once __DIR__ . '/auth.php';
    $error = null;
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        $actual = (string)($_POST['actual'] ?? ''); $nueva = (string)($_POST['nueva'] ?? ''); $rep = (string)($_POST['repetir'] ?? '');
        if (!password_verify($actual, $pu['password_hash'])) $error = 'La clave actual no es correcta.';
        elseif ($nueva !== $rep) $error = 'La nueva clave y su repetición no coinciden.';
        elseif ($nueva === $actual) $error = 'La nueva clave debe ser distinta de la actual.';
        elseif ($p = password_problem($nueva)) $error = $p;
        else {
            q('UPDATE portal_users SET password_hash=?, clave_inicial=0 WHERE id=?', [password_hash($nueva, PASSWORD_DEFAULT), $pu['id']]);
            audit('PORTAL_CAMBIO_CLAVE', 'portal_user', (int)$pu['id']);
            $_SESSION['flash'][] = ['ok', 'Clave actualizada.'];
            redirect('portal');
        }
    }
    portal_render('clave', ['pu' => $pu, 'error' => $error]);
}
