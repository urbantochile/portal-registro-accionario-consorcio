<?php
declare(strict_types=1);
require_once __DIR__ . '/members.php';
require_once __DIR__ . '/../certificado_pdf.php';

const CERT_ESTADOS = ['SIN_FIRMA' => ['Pendiente de firma', 'warn'], 'FIRMADO' => ['Firmado · vigente', 'ok'], 'REEMPLAZADO' => ['Reemplazado', 'neutral'], 'ANULADO' => ['Anulado', 'error']];

function cert_series_names(int $cid): array
{
    $r = [];
    foreach (all('SELECT * FROM series WHERE company_id=? ORDER BY id', [$cid]) as $s) $r[(int)$s['id']] = $s;
    return $r;
}

/** Posiciones accionista-serie con saldo y su certificado vigente (si lo hay). */
function cert_positions(int $cid): array
{
    $bal = balances($cid);
    $series = cert_series_names($cid);
    $vig = [];
    foreach (all("SELECT * FROM certificates WHERE company_id=? AND estado IN ('SIN_FIRMA','FIRMADO')", [$cid]) as $c) $vig[(int)$c['member_id']][(int)$c['series_id']] = $c;
    $liens = liens_vigentes($cid);
    $out = [];
    foreach ($bal as $mid => $bySeries) {
        foreach ($bySeries as $sid => $q) {
            if ($q <= 0) continue;
            $m = one('SELECT p.*, mm.id AS member_id, mm.numero AS socio_numero FROM members mm JOIN persons p ON p.id=mm.person_id WHERE mm.id=?', [$mid]);
            $pend = (int)val('SELECT COUNT(*) FROM movements WHERE company_id=? AND comprador_member_id=? AND series_id=? AND anulado=0 AND res_pendiente=1', [$cid, $mid, $sid]);
            $out[] = ['member' => $m, 'serie' => $series[$sid], 'cantidad' => $q, 'cert' => $vig[$mid][$sid] ?? null, 'gravamenes' => count($liens[$mid][$sid] ?? []), 'res_pendientes' => $pend];
        }
    }
    usort($out, fn($a, $b) => [$a['member']['socio_numero'], $a['serie']['id']] <=> [$b['member']['socio_numero'], $b['serie']['id']]);
    return $out;
}

function ctl_certificados_index(): void
{
    require_perm('certificados');
    $c = pick_company('certificados', 'certificados');
    if (!$c) { render('error', ['title' => 'Sin sociedades', 'msg' => 'No hay sociedades registradas o asignadas a su usuario.']); return; }
    $pos = cert_positions((int)$c['id']);
    $estado = getp('estado');
    $where = 'c.company_id=?'; $p = [$c['id']];
    if ($estado && isset(CERT_ESTADOS[$estado])) { $where .= ' AND c.estado=?'; $p[] = $estado; }
    $certs = all("SELECT c.*, s.nombre AS serie, pp.tipo, pp.nombres, pp.apellido_paterno, pp.apellido_materno, pp.razon_social, pp.rut
        FROM certificates c JOIN series s ON s.id=c.series_id JOIN members m ON m.id=c.member_id JOIN persons pp ON pp.id=m.person_id
        WHERE $where ORDER BY c.id DESC", $p);
    render('certificates/index', ['c' => $c, 'pos' => $pos, 'certs' => $certs, 'estado' => $estado, 'companies' => companies_for_user(), 'tab' => getp('tab', 'posiciones'), 'title' => 'Certificados · ' . $c['razon_social']]);
}

/** Arma los datos del certificado desde el registro actual. */
function cert_build_data(array $c, int $memberId, int $seriesId): array
{
    $m = one('SELECT p.*, mm.id AS member_id FROM members mm JOIN persons p ON p.id=mm.person_id WHERE mm.id=? AND mm.company_id=?', [$memberId, $c['id']]);
    $s = one('SELECT * FROM series WHERE id=? AND company_id=?', [$seriesId, $c['id']]);
    if (!$m || !$s) throw new RuntimeException('Accionista o serie no encontrados.');
    $cantidad = (int)(balances((int)$c['id'])[$memberId][$seriesId] ?? 0);
    $compras = all('SELECT * FROM movements WHERE company_id=? AND comprador_member_id=? AND series_id=? AND anulado=0 ORDER BY fecha, numero', [$c['id'], $memberId, $seriesId]);
    $ult = end($compras) ?: null;
    $codes = array_values(array_unique(array_filter(array_column($compras, 'cert_codigo'))));
    $resPend = (bool)array_filter($compras, fn($x) => (int)$x['res_pendiente'] === 1);
    $grav = array_map(fn($g) => ['tipo' => TIPOS_GRAVAMEN[$g['tipo']] ?? $g['tipo'], 'a_favor_de' => $g['a_favor_de'], 'cantidad' => (int)$g['cantidad'], 'fecha_anotacion' => $g['fecha_anotacion']],
        liens_vigentes((int)$c['id'])[$memberId][$seriesId] ?? []);
    return [
        'titular' => ['nombre' => person_name($m), 'rut' => $m['rut'], 'person_id' => (int)$m['id']],
        'serie' => ['id' => (int)$s['id'], 'nombre' => $s['nombre'], 'votos' => (float)$s['votos_por_accion'], 'preferencias' => $s['preferencias']],
        'cantidad' => $cantidad,
        'origen' => $c['regimen'] === 'RES' ? ['cves' => $codes] : ['folios' => $codes],
        'res_pendiente_auto' => $resPend,
        'inscripcion' => ['folio' => $ult ? (string)$ult['numero'] : '', 'fecha' => $ult['fecha'] ?? null],
        'gravamenes' => $grav,
        'representante' => ['nombre' => $c['rep_nombre'], 'rut' => $c['rep_rut'], 'cargo' => $c['rep_cargo']],
    ];
}

function cert_new_code(): string
{
    $abc = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    do {
        $s = '';
        for ($i = 0; $i < 12; $i++) $s .= $abc[random_int(0, strlen($abc) - 1)];
        $code = substr($s, 0, 4) . '-' . substr($s, 4, 4) . '-' . substr($s, 8, 4);
    } while (val('SELECT 1 FROM certificates WHERE codigo=?', [$code]));
    return $code;
}

function ctl_certificados_emitir(): void
{
    require_perm('certificados', 'editar');
    $c = require_company((int)(getp('c') ?: post('c')));
    $mid = (int)(getp('m') ?: post('m'));
    $sid = (int)(getp('s') ?: post('s'));
    try { $D = cert_build_data($c, $mid, $sid); } catch (Throwable $e) { flash('error', $e->getMessage()); redirect('certificados', ['c' => $c['id']]); }
    $errors = [];
    $f = ['estado_pago' => 'INTEGRAS', 'lugar' => $c['ciudad'] ?: $c['comuna'], 'fecha_emision' => today(), 'res_pendiente' => $D['res_pendiente_auto']];
    if ($D['cantidad'] <= 0) { flash('error', 'El accionista no tiene acciones de esa serie.'); redirect('certificados', ['c' => $c['id']]); }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        $f = ['estado_pago' => post('estado_pago'), 'monto_pagado' => nint(post('monto_pagado')), 'monto_total' => nint(post('monto_total')), 'plazo_entero' => ndate(post('plazo_entero')),
            'lugar' => nn(post('lugar')), 'fecha_emision' => ndate(post('fecha_emision')), 'res_pendiente' => $c['regimen'] === 'RES' && (isset($_POST['res_pendiente']) || $D['res_pendiente_auto'])];
        if (!in_array($f['estado_pago'], ['INTEGRAS', 'PARCIAL', 'NO_PAGADAS'], true)) $errors[] = 'Indique el estado de pago.';
        if ($f['estado_pago'] === 'PARCIAL') {
            if (!$f['monto_pagado'] || !$f['monto_total']) $errors[] = 'Para acciones parcialmente pagadas indique lo pagado y el total.';
            elseif ($f['monto_pagado'] >= $f['monto_total']) $errors[] = 'Lo pagado debe ser menor que el total (si está todo pagado, marque «Íntegramente pagadas»).';
        }
        if ($f['estado_pago'] === 'NO_PAGADAS' && !$f['plazo_entero']) $errors[] = 'Indique el plazo de entero de las acciones no pagadas.';
        if (!$f['lugar']) $errors[] = 'Indique el lugar de emisión.';
        if (!$f['fecha_emision'] || $f['fecha_emision'] > today()) $errors[] = 'Fecha de emisión inválida (no puede ser futura).';
        if ($c['regimen'] === 'RES' && !$f['res_pendiente'] && !($D['origen']['cves'] ?? [])) $errors[] = 'No hay CVE registrado para esta posición: marque «Registro RES pendiente».';
        if ($c['regimen'] === 'TRADICIONAL' && !($D['origen']['folios'] ?? [])) $errors[] = 'No hay folio de certificado de venta registrado en las compras de esta serie.';
        if (!$c['rep_nombre'] || !$c['rep_rut']) $errors[] = 'La sociedad no tiene representante legal registrado.';
        if (!$errors) {
            db()->beginTransaction();
            try {
                $anio = (int)substr($f['fecha_emision'], 0, 4);
                $corr = (int)val('SELECT COALESCE(MAX(correlativo),0)+1 FROM certificates WHERE company_id=? AND anio=? FOR UPDATE', [$c['id'], $anio]);
                $cert = [
                    'company_id' => (int)$c['id'], 'member_id' => $mid, 'series_id' => $sid, 'anio' => $anio, 'correlativo' => $corr,
                    'numero' => $anio . '-' . str_pad((string)$corr, 6, '0', STR_PAD_LEFT), 'codigo' => cert_new_code(), 'regimen' => $c['regimen'],
                    'cantidad' => $D['cantidad'], 'votos_por_accion' => $D['serie']['votos'], 'estado_pago' => $f['estado_pago'],
                    'monto_pagado' => $f['estado_pago'] === 'PARCIAL' ? $f['monto_pagado'] : null, 'monto_total' => $f['estado_pago'] === 'PARCIAL' ? $f['monto_total'] : null,
                    'plazo_entero' => in_array($f['estado_pago'], ['PARCIAL', 'NO_PAGADAS'], true) ? $f['plazo_entero'] : null,
                    'res_pendiente' => $f['res_pendiente'] ? 1 : 0, 'datos' => json_encode($D, JSON_UNESCAPED_UNICODE), 'lugar' => $f['lugar'], 'fecha_emision' => $f['fecha_emision'],
                ];
                // Reemplaza el vigente anterior de esa posición
                cert_invalidate($mid, $sid, 'Emisión de un nuevo certificado N° ' . $cert['numero']);
                $cols = array_keys($cert);
                q('INSERT INTO certificates (' . implode(',', $cols) . ', estado, creado_por, creado_en) VALUES (' . implode(',', array_fill(0, count($cols), '?')) . ", 'SIN_FIRMA', ?, ?)", array_merge(array_values($cert), [current_user()['id'], now()]));
                $id = (int)db()->lastInsertId();
                $pdf = build_certificate_pdf($cert, $c);
                $dir = STORAGE . '/docs/' . date('Y/m');
                if (!is_dir($dir)) mkdir($dir, 0750, true);
                $name = bin2hex(random_bytes(16)) . '.pdf';
                file_put_contents($dir . '/' . $name, $pdf);
                q('INSERT INTO documents (owner_type,owner_id,tipo,nombre_original,ruta,mime,bytes,sha256,subido_por,subido_en) VALUES (?,?,?,?,?,?,?,?,?,?)',
                    ['certificate', $id, 'CERT_SIN_FIRMA', 'Certificado-' . $cert['numero'] . '-' . rut_clean($c['rut']) . '-SIN-FIRMA.pdf', 'docs/' . date('Y/m') . '/' . $name, 'application/pdf', strlen($pdf), hash('sha256', $pdf), current_user()['id'], now()]);
                q('UPDATE certificates SET doc_id=?, sha256=? WHERE id=?', [(int)db()->lastInsertId(), hash('sha256', $pdf), $id]);
                db()->commit();
            } catch (Throwable $e) {
                db()->rollBack();
                $errors[] = $e->getMessage();
            }
            if (!$errors) {
                audit('CERTIFICADO_EMITIDO', 'certificate', $id, (int)$c['id'], ['numero' => $cert['numero'], 'codigo' => $cert['codigo'], 'accionista' => $D['titular']['nombre'], 'serie' => $D['serie']['nombre'], 'cantidad' => $D['cantidad']]);
                flash('ok', 'Certificado N° ' . $cert['numero'] . ' generado. Descárguelo, hágalo firmar en FirmaYa por ' . $c['rep_nombre'] . ' y suba el PDF firmado.');
                redirect('certificados.ver', ['id' => $id]);
            }
        }
    }
    render('certificates/emit', compact('c', 'D', 'f', 'errors', 'mid', 'sid') + ['title' => 'Emitir certificado · ' . $c['razon_social']]);
}

function cert_load(int $id): array
{
    $cert = one('SELECT * FROM certificates WHERE id=?', [$id]);
    if (!$cert) { flash('error', 'Certificado no encontrado.'); redirect('certificados'); }
    $c = require_company((int)$cert['company_id']);
    return [$cert, $c];
}

function ctl_certificados_ver(): void
{
    require_perm('certificados');
    [$cert, $c] = cert_load((int)getp('id'));
    $D = json_decode($cert['datos'], true);
    $hist = all('SELECT id, numero, estado, fecha_emision, baja_motivo FROM certificates WHERE member_id=? AND series_id=? ORDER BY id DESC', [$cert['member_id'], $cert['series_id']]);
    $firmadoPor = $cert['firmado_por'] ? one('SELECT nombre FROM users WHERE id=?', [$cert['firmado_por']]) : null;
    render('certificates/view', compact('cert', 'c', 'D', 'hist', 'firmadoPor') + ['title' => 'Certificado N° ' . $cert['numero']]);
}

function ctl_certificados_pdf(): void
{
    require_perm('certificados');
    [$cert, $c] = cert_load((int)getp('id'));
    $firmado = getp('v') === 'firmado';
    if ($firmado) {
        if (!$cert['firmado_doc_id']) redirect('certificados.ver', ['id' => $cert['id']]);
        $d = one('SELECT * FROM documents WHERE id=?', [$cert['firmado_doc_id']]);
    } else {
        if (!can('cert_sin_firma')) { flash('error', 'Su usuario no tiene permiso para ver certificados sin firma.'); redirect('certificados.ver', ['id' => $cert['id']]); }
        $d = one('SELECT * FROM documents WHERE id=?', [$cert['doc_id']]);
    }
    audit($firmado ? 'CERTIFICADO_FIRMADO_DESCARGADO' : 'CERTIFICADO_SIN_FIRMA_DESCARGADO', 'certificate', (int)$cert['id'], (int)$c['id'], ['numero' => $cert['numero']]);
    require_once __DIR__ . '/files.php';
    send_file(STORAGE . '/' . $d['ruta'], 'application/pdf', $d['nombre_original'], getp('dl') !== '1');
}

function ctl_certificados_firmado(): void
{
    require_perm('certificados', 'editar');
    csrf_check();
    [$cert, $c] = cert_load((int)post('id'));
    if ($cert['estado'] !== 'SIN_FIRMA') { flash('error', 'Solo se puede subir la versión firmada de un certificado pendiente de firma.'); redirect('certificados.ver', ['id' => $cert['id']]); }
    $f = $_FILES['firmado'] ?? null;
    if (!$f || $f['error'] !== UPLOAD_ERR_OK) { flash('error', 'Seleccione el PDF firmado.'); redirect('certificados.ver', ['id' => $cert['id']]); }
    $raw = (string)file_get_contents($f['tmp_name']);
    $err = null;
    if (!str_starts_with($raw, '%PDF')) $err = 'El archivo no es un PDF.';
    elseif (!pdf_has_signature($raw)) $err = 'El PDF no contiene una firma electrónica incrustada. Descargue el documento firmado desde FirmaYa y vuelva a subirlo.';
    elseif (!pdf_contains($raw, $cert['codigo']) || !pdf_contains($raw, $cert['numero'])) $err = 'El PDF firmado no corresponde a este certificado (no contiene el N° ' . $cert['numero'] . ' ni el código ' . $cert['codigo'] . ').';
    elseif (hash('sha256', $raw) === $cert['sha256']) $err = 'Es el mismo archivo sin firmar.';
    if ($err) {
        audit('CERTIFICADO_FIRMADO_RECHAZADO', 'certificate', (int)$cert['id'], (int)$c['id'], ['motivo' => $err]);
        flash('error', $err);
        redirect('certificados.ver', ['id' => $cert['id']]);
    }
    try {
        $docId = store_upload('firmado', 'certificate', (int)$cert['id'], 'CERT_FIRMADO', ['pdf']);
    } catch (Throwable $e) { flash('error', $e->getMessage()); redirect('certificados.ver', ['id' => $cert['id']]); }
    q('UPDATE documents SET nombre_original=? WHERE id=?', ['Certificado-' . $cert['numero'] . '-' . rut_clean($c['rut']) . '-FIRMADO.pdf', $docId]);
    q("UPDATE certificates SET estado='FIRMADO', firmado_doc_id=?, firmado_sha256=?, firmado_por=?, firmado_en=? WHERE id=?", [$docId, hash('sha256', $raw), current_user()['id'], now(), $cert['id']]);
    audit('CERTIFICADO_FIRMADO_SUBIDO', 'certificate', (int)$cert['id'], (int)$c['id'], ['numero' => $cert['numero'], 'sha256' => hash('sha256', $raw)]);
    flash('ok', 'Certificado firmado cargado. Ya está disponible para el accionista en su portal.');
    redirect('certificados.ver', ['id' => $cert['id']]);
}

function ctl_certificados_anular(): void
{
    if (!is_master()) { flash('error', 'Solo el Máster puede anular certificados.'); redirect('certificados'); }
    csrf_check();
    [$cert, $c] = cert_load((int)post('id'));
    $motivo = nn(post('motivo'));
    if (!$motivo) { flash('error', 'Indique el motivo.'); redirect('certificados.ver', ['id' => $cert['id']]); }
    if (!in_array($cert['estado'], ['SIN_FIRMA', 'FIRMADO'], true)) redirect('certificados.ver', ['id' => $cert['id']]);
    q("UPDATE certificates SET estado='ANULADO', baja_motivo=?, baja_en=?, baja_por=? WHERE id=?", [$motivo, now(), current_user()['id'], $cert['id']]);
    audit('CERTIFICADO_ANULADO', 'certificate', (int)$cert['id'], (int)$c['id'], ['numero' => $cert['numero'], 'motivo' => $motivo]);
    flash('ok', 'Certificado anulado.');
    redirect('certificados.ver', ['id' => $cert['id']]);
}

/** Acceso al portal de accionista (desde la ficha de la persona). */
function ctl_certificados_acceso(): void
{
    require_perm('certificados', 'editar');
    csrf_check();
    $p = one('SELECT * FROM persons WHERE id=?', [(int)post('person_id')]);
    if (!$p) redirect('personas');
    $pu = one('SELECT * FROM portal_users WHERE person_id=?', [$p['id']]);
    $acc = post('accion');
    $back = ['personas.ver', ['id' => $p['id']]];
    if ($acc === 'crear' || $acc === 'clave') {
        $email = mb_strtolower((string)post('email', $pu['email'] ?? $p['email']));
        $clave = (string)($_POST['clave'] ?? '');
        require_once __DIR__ . '/auth.php';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { flash('error', 'Correo inválido.'); redirect(...$back); }
        if ($pr = password_problem($clave)) { flash('error', $pr); redirect(...$back); }
        if (val('SELECT 1 FROM users WHERE email=?', [$email])) { flash('error', 'Ese correo pertenece a un usuario interno de la plataforma; use otro correo para el portal.'); redirect(...$back); }
        if (val('SELECT 1 FROM portal_users WHERE email=? AND person_id<>?', [$email, $p['id']])) { flash('error', 'Ese correo ya está asignado a otro accionista.'); redirect(...$back); }
        if ($pu) {
            q('UPDATE portal_users SET email=?, password_hash=?, clave_inicial=1, activo=1, intentos_fallidos=0, bloqueado_hasta=NULL WHERE id=?', [$email, password_hash($clave, PASSWORD_DEFAULT), $pu['id']]);
            audit('PORTAL_CLAVE_ASIGNADA', 'person', (int)$p['id'], null, ['email' => $email]);
        } else {
            q('INSERT INTO portal_users (person_id,email,password_hash,activo,clave_inicial,creado_por,creado_en) VALUES (?,?,?,?,?,?,?)', [$p['id'], $email, password_hash($clave, PASSWORD_DEFAULT), 1, 1, current_user()['id'], now()]);
            audit('PORTAL_ACCESO_CREADO', 'person', (int)$p['id'], null, ['email' => $email]);
        }
        flash('ok', 'Acceso al portal listo para ' . $email . '. Comunique la clave inicial al accionista por un canal seguro; al ingresar deberá cambiarla.');
    } elseif ($pu && $acc === 'estado') {
        q('UPDATE portal_users SET activo=1-activo WHERE id=?', [$pu['id']]);
        audit($pu['activo'] ? 'PORTAL_ACCESO_DESACTIVADO' : 'PORTAL_ACCESO_ACTIVADO', 'person', (int)$p['id']);
        flash('ok', $pu['activo'] ? 'Acceso desactivado.' : 'Acceso activado.');
    }
    redirect(...$back);
}
