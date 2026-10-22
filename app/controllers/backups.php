<?php
declare(strict_types=1);
require_once __DIR__ . '/users.php';

const BACKUP_TABLES = ['users', 'user_permissions', 'companies', 'user_companies', 'documents', 'series', 'persons', 'beneficiarios', 'members', 'member_notes', 'movements', 'libro_entregas', 'audit_log', 'backups', 'holidays', 'settings', 'gravamenes', 'certificates', 'portal_users'];

function build_backup(bool $withFiles): array
{
    $u = current_user();
    $data = ['app' => 'consa-accionistas', 'version' => APP_VERSION, 'generado_en' => date('c'), 'generado_por' => $u['email'] ?? 'sistema', 'con_documentos' => $withFiles, 'tablas' => [], 'archivos' => []];
    foreach (BACKUP_TABLES as $t) {
        try { $data['tablas'][$t] = all("SELECT * FROM `$t`"); } catch (Throwable $e) { /* tabla aún no creada */ }
    }
    if ($withFiles) {
        foreach (all('SELECT ruta FROM documents') as $d) {
            $f = STORAGE . '/' . $d['ruta'];
            if (is_file($f)) $data['archivos'][$d['ruta']] = base64_encode(file_get_contents($f));
        }
    }
    $data['sha256_tablas'] = hash('sha256', json_encode($data['tablas'], JSON_UNESCAPED_UNICODE));
    return $data;
}

function ctl_respaldos_index(): void
{
    master_only();
    $year = (int)getp('anio', date('Y'));
    render('backups', ['hist' => all('SELECT b.*, u.nombre FROM backups b LEFT JOIN users u ON u.id=b.user_id ORDER BY b.id DESC LIMIT 30'),
        'holidays' => all('SELECT * FROM holidays WHERE YEAR(fecha)=? ORDER BY fecha', [$year]), 'year' => $year,
        'pending' => backup_pending_today(), 'business' => is_business_day(today()),
        'maxUpload' => ini_get('upload_max_filesize'), 'title' => 'Respaldos y feriados']);
}

function ctl_respaldos_descargar(): void
{
    master_only();
    csrf_check();
    @set_time_limit(300);
    @ini_set('memory_limit', '1024M');
    $with = isset($_POST['con_documentos']);
    $data = build_backup($with);
    $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    $name = 'respaldo-accionistas-consa-' . date('Ymd-His') . ($with ? '' : '-sin-documentos') . '.json';
    q('INSERT INTO backups (tipo,user_id,archivo,bytes,con_documentos,creado_en) VALUES (?,?,?,?,?,?)', ['DESCARGA', current_user()['id'], $name, strlen($json), $with ? 1 : 0, now()]);
    audit('RESPALDO_DESCARGADO', 'backup', (int)db()->lastInsertId(), null, ['archivo' => $name, 'bytes' => strlen($json), 'con_documentos' => $with]);
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $name . '"');
    header('Content-Length: ' . strlen($json));
    header('Cache-Control: private, no-store');
    echo $json;
    exit;
}

function ctl_respaldos_restaurar(): void
{
    master_only();
    csrf_check();
    @set_time_limit(600);
    @ini_set('memory_limit', '1024M');
    if (post('confirmacion') !== 'RESTAURAR') { flash('error', 'Escriba RESTAURAR (en mayúsculas) para confirmar.'); redirect('respaldos'); }
    $f = $_FILES['respaldo'] ?? null;
    if (!$f || $f['error'] !== UPLOAD_ERR_OK) { flash('error', 'Seleccione el archivo de respaldo (.json). Si es muy grande, revise el límite de subida del hosting.'); redirect('respaldos'); }
    $data = json_decode((string)file_get_contents($f['tmp_name']), true);
    if (!is_array($data) || ($data['app'] ?? '') !== 'consa-accionistas' || !isset($data['tablas']) || !is_array($data['tablas'])) {
        flash('error', 'El archivo no es un respaldo válido de esta plataforma.'); redirect('respaldos');
    }
    if (isset($data['sha256_tablas']) && !hash_equals($data['sha256_tablas'], hash('sha256', json_encode($data['tablas'], JSON_UNESCAPED_UNICODE)))) {
        flash('error', 'El respaldo fue modificado o está dañado (la huella de integridad no coincide). No se restauró.'); redirect('respaldos');
    }
    if (empty($data['tablas']['users'])) { flash('error', 'El respaldo no contiene usuarios. No se restauró.'); redirect('respaldos'); }
    // Copia automática del estado actual antes de restaurar
    $dir = STORAGE . '/backups';
    if (!is_dir($dir)) mkdir($dir, 0750, true);
    $pre = 'pre-restauracion-' . date('Ymd-His') . '.json';
    file_put_contents($dir . '/' . $pre, json_encode(build_backup(true), JSON_UNESCAPED_UNICODE));
    $me = current_user();
    $pdo = db();
    try {
        $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
        $pdo->beginTransaction();
        foreach (array_reverse(BACKUP_TABLES) as $t) { try { $pdo->exec("DELETE FROM `$t`"); } catch (Throwable $e) { if ($e instanceof PDOException && (int)($e->errorInfo[1] ?? 0) !== 1146) throw $e; } }
        foreach (BACKUP_TABLES as $t) {
            foreach ($data['tablas'][$t] ?? [] as $row) {
                $cols = array_keys($row);
                foreach ($cols as $col) if (!preg_match('/^[a-z0-9_]+$/', (string)$col)) throw new RuntimeException('Columna inválida en el respaldo.');
                $sql = "INSERT INTO `$t` (`" . implode('`,`', $cols) . '`) VALUES (' . implode(',', array_fill(0, count($cols), '?')) . ')';
                q($sql, array_values($row));
            }
        }
        $pdo->commit();
        $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    } catch (Throwable $ex) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
        flash('error', 'Error al restaurar: ' . $ex->getMessage() . '. Los datos no se modificaron. Copia previa: ' . $pre);
        redirect('respaldos');
    }
    $n = 0;
    foreach (($data['archivos'] ?? []) as $ruta => $b64) {
        if (!preg_match('#^docs/\d{4}/\d{2}/[a-f0-9]{32}\.(pdf|png|jpg)$#', (string)$ruta)) continue;
        $dest = STORAGE . '/' . $ruta;
        if (!is_dir(dirname($dest))) mkdir(dirname($dest), 0750, true);
        file_put_contents($dest, base64_decode((string)$b64));
        $n++;
    }
    q('INSERT INTO backups (tipo,user_id,archivo,bytes,con_documentos,creado_en) VALUES (?,?,?,?,?,?)', ['RESTAURACION', null, $f['name'], (int)$f['size'], empty($data['archivos']) ? 0 : 1, now()]);
    q('INSERT INTO audit_log (user_id,user_email,accion,entidad,detalle,ip,user_agent,creado_en) VALUES (?,?,?,?,?,?,?,?)', [null, $me['email'], 'RESPALDO_RESTAURADO', 'backup',
        json_encode(['archivo' => $f['name'], 'generado_en' => $data['generado_en'] ?? null, 'archivos_restaurados' => $n, 'copia_previa' => $pre], JSON_UNESCAPED_UNICODE), $_SERVER['REMOTE_ADDR'] ?? '', mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 250), now()]);
    // La sesión puede no corresponder a los usuarios restaurados: se cierra
    $_SESSION = [];
    session_destroy();
    session_start();
    flash('ok', 'Respaldo restaurado (' . count($data['tablas']['companies'] ?? []) . ' sociedades, ' . $n . ' documentos). Ingrese nuevamente con las credenciales vigentes en el respaldo. Copia del estado anterior guardada en el servidor: ' . $pre);
    redirect('login');
}

function ctl_respaldos_feriado(): void
{
    master_only();
    csrf_check();
    $acc = post('accion');
    if ($acc === 'agregar') {
        $f = ndate(post('fecha')); $n = nn(post('nombre'));
        if ($f && $n) { q('REPLACE INTO holidays (fecha,nombre) VALUES (?,?)', [$f, $n]); audit('FERIADO_AGREGADO', 'holiday', null, null, ['fecha' => $f, 'nombre' => $n]); flash('ok', 'Feriado agregado.'); }
        else flash('error', 'Indique fecha y nombre.');
        $y = substr((string)$f, 0, 4);
    } elseif ($acc === 'quitar') {
        $f = ndate(post('fecha'));
        q('DELETE FROM holidays WHERE fecha=?', [$f]);
        audit('FERIADO_ELIMINADO', 'holiday', null, null, ['fecha' => $f]);
        flash('ok', 'Feriado eliminado.');
        $y = substr((string)$f, 0, 4);
    } elseif ($acc === 'cargar') {
        $y = (int)post('anio');
        if ($y >= 2020 && $y <= 2100) {
            foreach (chile_holidays($y) as $f => $n) q('INSERT IGNORE INTO holidays (fecha,nombre) VALUES (?,?)', [$f, $n]);
            audit('FERIADOS_CARGADOS', 'holiday', null, null, ['anio' => $y]);
            flash('ok', 'Feriados legales de ' . $y . ' cargados. Revise si hay feriados adicionales (elecciones, leyes especiales).');
        }
    }
    redirect('respaldos', ['anio' => $y ?? date('Y')]);
}
