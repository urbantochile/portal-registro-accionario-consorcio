<?php
declare(strict_types=1);

function pick_company(string $route, string $mod): ?array
{
    $cid = (int)getp('c', (int)($_SESSION['last_company'] ?? 0));
    $list = companies_for_user();
    if (!$cid || !in_array($cid, array_map('intval', array_column($list, 'id')), true)) {
        $cid = $list ? (int)$list[0]['id'] : 0;
    }
    if (!$cid) return null;
    $_SESSION['last_company'] = $cid;
    return require_company($cid);
}

function members_of(int $cid): array
{
    $rows = all('SELECT m.*, p.*, m.id AS member_id, m.numero AS socio_numero, p.numero AS dom_numero, m.observaciones AS m_obs
        FROM members m JOIN persons p ON p.id=m.person_id WHERE m.company_id=? ORDER BY m.numero', [$cid]);
    $bal = balances($cid);
    foreach ($rows as &$r) {
        $r['numero'] = $r['dom_numero'];
        $r['_saldo'] = array_sum($bal[(int)$r['member_id']] ?? []);
        $r['_notas'] = all('SELECT * FROM member_notes WHERE member_id=? ORDER BY fecha, id', [$r['member_id']]);
        $r['_firma'] = one("SELECT * FROM documents WHERE owner_type='member' AND owner_id=? AND tipo='FIRMA' AND vigente=1 ORDER BY id DESC LIMIT 1", [$r['member_id']]);
        $r['_hist'] = (bool)val('SELECT 1 FROM movements WHERE anulado=0 AND (comprador_member_id=? OR vendedor_member_id=?)', [$r['member_id'], $r['member_id']]);
    }
    return $rows;
}

function ctl_socios_index(): void
{
    require_perm('socios');
    $c = pick_company('socios', 'socios');
    if (!$c) { render('error', ['title' => 'Sin sociedades', 'msg' => 'No hay sociedades registradas o asignadas a su usuario.']); return; }
    $rows = members_of((int)$c['id']);
    $eligible = all("SELECT * FROM persons WHERE dd_estado='APROBADA' AND id NOT IN (SELECT person_id FROM members WHERE company_id=?) ORDER BY COALESCE(razon_social, apellido_paterno)", [$c['id']]);
    render('members/index', ['c' => $c, 'rows' => $rows, 'eligible' => $eligible, 'companies' => companies_for_user(), 'title' => 'Registro de socios · ' . $c['razon_social']]);
}

function ctl_socios_agregar(): void
{
    require_perm('socios', 'editar');
    csrf_check();
    $c = require_company((int)post('company_id'));
    $p = one('SELECT * FROM persons WHERE id=?', [(int)post('person_id')]);
    $back = post('volver') === 'persona' && $p ? ['personas.ver', ['id' => $p['id']]] : ['socios', ['c' => $c['id']]];
    $fecha = ndate(post('fecha_ingreso'));
    $err = null;
    if ($c['estado'] !== 'ACTIVA') $err = 'La sociedad está inactiva.';
    elseif (!$p) $err = 'Seleccione una persona.';
    elseif ($p['dd_estado'] !== 'APROBADA') $err = 'La persona no tiene la debida diligencia aprobada.';
    elseif (!$fecha) $err = 'Fecha de ingreso inválida.';
    elseif ($fecha > today()) $err = 'La fecha de ingreso no puede ser futura.';
    elseif (val('SELECT 1 FROM members WHERE company_id=? AND person_id=?', [$c['id'], $p['id']])) $err = 'La persona ya está en el registro de socios de esta sociedad.';
    if ($err) { flash('error', $err); redirect(...$back); }
    db()->beginTransaction();
    try {
        $n = (int)val('SELECT COALESCE(MAX(numero),0)+1 FROM members WHERE company_id=? FOR UPDATE', [$c['id']]);
        q('INSERT INTO members (company_id,person_id,numero,fecha_ingreso,observaciones,creado_en,creado_por) VALUES (?,?,?,?,?,?,?)', [$c['id'], $p['id'], $n, $fecha, nn(post('observaciones')), now(), current_user()['id']]);
        $mid = (int)db()->lastInsertId();
        store_upload('firma', 'member', $mid, 'FIRMA', ['pdf', 'image'], 5);
        db()->commit();
    } catch (Throwable $ex) {
        db()->rollBack();
        flash('error', $ex->getMessage());
        redirect(...$back);
    }
    audit('SOCIO_INCORPORADO', 'member', $mid, (int)$c['id'], ['persona' => person_name($p), 'rut' => $p['rut'], 'numero' => $n]);
    flash('ok', person_name($p) . ' fue incorporado(a) como socio N° ' . str_pad((string)$n, 2, '0', STR_PAD_LEFT) . ' de ' . rtrim($c['razon_social'], '.') . '. Ya puede ser ingresado(a) al registro de accionistas.');
    redirect(...$back);
}

function ctl_socios_nota(): void
{
    require_perm('socios', 'editar');
    csrf_check();
    $m = one('SELECT * FROM members WHERE id=?', [(int)post('member_id')]);
    if (!$m) redirect('socios');
    require_company((int)$m['company_id']);
    $txt = nn(post('texto'));
    $f = ndate(post('fecha')) ?? today();
    if ($txt) {
        q('INSERT INTO member_notes (member_id,fecha,texto,automatica,creado_por,creado_en) VALUES (?,?,?,?,?,?)', [$m['id'], $f, $txt, 0, current_user()['id'], now()]);
        audit('SOCIO_ANOTACION', 'member', (int)$m['id'], (int)$m['company_id'], ['texto' => $txt]);
        flash('ok', 'Anotación registrada.');
    }
    try {
        if (store_upload('firma', 'member', (int)$m['id'], 'FIRMA', ['pdf', 'image'], 5)) {
            audit('SOCIO_FIRMA', 'member', (int)$m['id'], (int)$m['company_id']);
            flash('ok', 'Firma adjuntada.');
        }
    } catch (Throwable $ex) { flash('error', $ex->getMessage()); }
    redirect('socios', ['c' => $m['company_id']]);
}

function ctl_socios_excel(): void
{
    require_perm('socios');
    $c = require_company((int)getp('c'));
    $rows = [['N°', 'Nombres / Razón social', 'Tipo', 'Edad', 'Estado civil', 'Fecha ingreso', 'Profesión / giro', 'Domicilio', 'Carnet (RUT)', 'Nacionalidad', 'Correo', 'Teléfono', 'Firma registrada', 'Diligencia', 'Acciones vigentes', 'Condición', 'Observaciones']];
    foreach (members_of((int)$c['id']) as $r) {
        $obs = array_map(fn($n) => fdate($n['fecha']) . ': ' . $n['texto'], $r['_notas']);
        if ($r['m_obs']) array_unshift($obs, $r['m_obs']);
        $rows[] = [str_pad((string)$r['socio_numero'], 2, '0', STR_PAD_LEFT), person_name($r), $r['tipo'] === 'NATURAL' ? 'Natural' : 'Jurídica',
            $r['tipo'] === 'NATURAL' ? edad($r['fecha_nacimiento']) : '', $r['estado_civil'] ?? '', fdate($r['fecha_ingreso']), $r['profesion'], person_address($r), rut_format($r['rut']),
            $r['nacionalidad'], $r['email'], $r['telefono'], $r['_firma'] ? 'Sí' : 'No', $r['dd_estado'], $r['_saldo'],
            $r['_saldo'] > 0 ? 'Accionista vigente' : ($r['_hist'] ? 'Enajenó la totalidad' : 'Socio sin acciones'), implode(' | ', $obs)];
    }
    $x = new SimpleXlsx();
    $x->addSheet('Registro de socios', $rows, [5, 34, 9, 6, 22, 12, 22, 40, 14, 13, 26, 14, 9, 11, 13, 20, 60], 'REGISTRO DE SOCIOS — ' . $c['razon_social'] . ' (RUT ' . rut_format($c['rut']) . ') — ' . date('d-m-Y H:i'));
    $tmp = tempnam(sys_get_temp_dir(), 'xl');
    $x->save($tmp);
    audit('EXCEL_REGISTRO_SOCIOS', 'company', (int)$c['id'], (int)$c['id']);
    send_download($tmp, 'registro-socios-' . rut_clean($c['rut']) . '-' . date('Ymd') . '.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
}
