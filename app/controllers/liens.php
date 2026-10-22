<?php
declare(strict_types=1);
require_once __DIR__ . '/members.php';

function ctl_gravamenes_index(): void
{
    require_perm('gravamenes');
    $c = pick_company('gravamenes', 'gravamenes');
    if (!$c) { render('error', ['title' => 'Sin sociedades', 'msg' => 'No hay sociedades registradas o asignadas a su usuario.']); return; }
    $rows = all("SELECT g.*, s.nombre AS serie, p.tipo AS ptipo, p.nombres, p.apellido_paterno, p.apellido_materno, p.razon_social, p.rut
        FROM gravamenes g JOIN series s ON s.id=g.series_id JOIN members m ON m.id=g.member_id JOIN persons p ON p.id=m.person_id
        WHERE g.company_id=? ORDER BY g.estado='VIGENTE' DESC, g.fecha_anotacion DESC, g.id DESC", [$c['id']]);
    foreach ($rows as &$r) { $r['tipo_p'] = $r['tipo']; $r['tipo'] = $r['ptipo']; }
    unset($r);
    $bal = balances((int)$c['id']);
    $opts = [];
    foreach (all('SELECT m.id AS mid, m.numero AS socio_n, p.* FROM members m JOIN persons p ON p.id=m.person_id WHERE m.company_id=? ORDER BY m.numero', [$c['id']]) as $m) {
        if (array_sum($bal[(int)$m['mid']] ?? []) > 0) $opts[] = $m + ['_bal' => $bal[(int)$m['mid']]];
    }
    render('liens/index', ['c' => $c, 'rows' => $rows, 'opts' => $opts, 'series' => all('SELECT * FROM series WHERE company_id=? ORDER BY id', [$c['id']]),
        'companies' => companies_for_user(), 'title' => 'Gravámenes · ' . $c['razon_social']]);
}

function ctl_gravamenes_anotar(): void
{
    require_perm('gravamenes', 'editar');
    csrf_check();
    $c = require_company((int)post('c'));
    $mid = (int)post('member_id'); $sid = (int)post('series_id');
    $cant = nint(post('cantidad'));
    $tipo = post('tipo'); $fav = nn(post('a_favor_de')); $fecha = ndate(post('fecha_anotacion'));
    $saldo = (int)(balances((int)$c['id'])[$mid][$sid] ?? 0);
    $err = null;
    if (!one('SELECT 1 FROM members WHERE id=? AND company_id=?', [$mid, $c['id']])) $err = 'Seleccione el accionista.';
    elseif (!one('SELECT 1 FROM series WHERE id=? AND company_id=?', [$sid, $c['id']])) $err = 'Seleccione la serie.';
    elseif (!isset(TIPOS_GRAVAMEN[$tipo])) $err = 'Seleccione el tipo de gravamen.';
    elseif (!$fav) $err = 'Indique a favor de quién se constituye.';
    elseif (!$cant || $cant <= 0) $err = 'Indique la cantidad de acciones afectadas.';
    elseif ($cant > $saldo) $err = 'El accionista solo tiene ' . fnum($saldo) . ' acciones de esa serie.';
    elseif (!$fecha || $fecha > today()) $err = 'Fecha de anotación inválida.';
    elseif (empty($_FILES['documento']['name'])) $err = 'Adjunte el documento que respalda el gravamen (PDF).';
    if ($err) { flash('error', $err); redirect('gravamenes', ['c' => $c['id']]); }
    db()->beginTransaction();
    try {
        q('INSERT INTO gravamenes (company_id,member_id,series_id,tipo,a_favor_de,cantidad,fecha_anotacion,descripcion,estado,creado_por,creado_en) VALUES (?,?,?,?,?,?,?,?,?,?,?)',
            [$c['id'], $mid, $sid, $tipo, $fav, $cant, $fecha, nn(post('descripcion')), 'VIGENTE', current_user()['id'], now()]);
        $gid = (int)db()->lastInsertId();
        $doc = store_upload('documento', 'gravamen', $gid, 'GRAVAMEN', ['pdf']);
        q('UPDATE gravamenes SET doc_id=? WHERE id=?', [$doc, $gid]);
        q('INSERT INTO member_notes (member_id,fecha,texto,automatica,creado_por,creado_en) VALUES (?,?,?,?,?,?)', [$mid, $fecha,
            'Se anota ' . TIPOS_GRAVAMEN[$tipo] . ' sobre ' . fnum($cant) . ' acciones de ' . val('SELECT nombre FROM series WHERE id=?', [$sid]) . ' a favor de ' . $fav . '.', 1, current_user()['id'], now()]);
        cert_invalidate($mid, $sid, 'Anotación de gravamen');
        db()->commit();
    } catch (Throwable $e) {
        db()->rollBack();
        flash('error', $e->getMessage());
        redirect('gravamenes', ['c' => $c['id']]);
    }
    audit('GRAVAMEN_ANOTADO', 'gravamen', $gid, (int)$c['id'], ['tipo' => $tipo, 'a_favor_de' => $fav, 'cantidad' => $cant, 'member' => $mid, 'serie' => $sid]);
    flash('ok', 'Gravamen anotado. Si el accionista tenía certificado vigente de esa serie, quedó reemplazado: emita uno nuevo.');
    redirect('gravamenes', ['c' => $c['id']]);
}

function ctl_gravamenes_alzar(): void
{
    require_perm('gravamenes', 'editar');
    csrf_check();
    $g = one("SELECT * FROM gravamenes WHERE id=? AND estado='VIGENTE'", [(int)post('id')]);
    if (!$g) redirect('gravamenes');
    $c = require_company((int)$g['company_id']);
    $fecha = ndate(post('alzado_fecha'));
    if (!$fecha || $fecha > today() || $fecha < $g['fecha_anotacion']) { flash('error', 'Fecha de alzamiento inválida.'); redirect('gravamenes', ['c' => $c['id']]); }
    if (empty($_FILES['documento']['name'])) { flash('error', 'Adjunte el documento de alzamiento (PDF).'); redirect('gravamenes', ['c' => $c['id']]); }
    try { $doc = store_upload('documento', 'gravamen', (int)$g['id'], 'ALZAMIENTO', ['pdf']); }
    catch (Throwable $e) { flash('error', $e->getMessage()); redirect('gravamenes', ['c' => $c['id']]); }
    q("UPDATE gravamenes SET estado='ALZADO', alzado_fecha=?, alzado_doc_id=?, alzado_obs=?, alzado_por=?, alzado_en=? WHERE id=?", [$fecha, $doc, nn(post('alzado_obs')), current_user()['id'], now(), $g['id']]);
    q('INSERT INTO member_notes (member_id,fecha,texto,automatica,creado_por,creado_en) VALUES (?,?,?,?,?,?)', [$g['member_id'], $fecha,
        'Alzamiento de ' . (TIPOS_GRAVAMEN[$g['tipo']] ?? $g['tipo']) . ' sobre ' . fnum($g['cantidad']) . ' acciones a favor de ' . $g['a_favor_de'] . '.', 1, current_user()['id'], now()]);
    cert_invalidate((int)$g['member_id'], (int)$g['series_id'], 'Alzamiento de gravamen');
    audit('GRAVAMEN_ALZADO', 'gravamen', (int)$g['id'], (int)$c['id'], ['fecha' => $fecha]);
    flash('ok', 'Gravamen alzado. Emita un nuevo certificado si corresponde.');
    redirect('gravamenes', ['c' => $c['id']]);
}
