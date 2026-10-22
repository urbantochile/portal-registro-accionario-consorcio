<?php
declare(strict_types=1);
require_once __DIR__ . '/members.php';
require_once __DIR__ . '/../libro.php';

function ctl_accionistas_index(): void
{
    require_perm('accionistas');
    $c = pick_company('accionistas', 'accionistas');
    if (!$c) { render('error', ['title' => 'Sin sociedades', 'msg' => 'No hay sociedades registradas o asignadas a su usuario.']); return; }
    $sh = current_shareholders((int)$c['id']);
    $movs = movements_of((int)$c['id'], true);
    $names = [];
    foreach (all('SELECT m.id AS mid, p.tipo, p.nombres, p.apellido_paterno, p.apellido_materno, p.razon_social FROM members m JOIN persons p ON p.id=m.person_id WHERE m.company_id=?', [$c['id']]) as $x) $names[(int)$x['mid']] = person_name($x);
    $issued = issued_by_series((int)$c['id']);
    $deliveries = all('SELECT l.*, u.nombre AS por FROM libro_entregas l JOIN users u ON u.id=l.generado_por WHERE company_id=? ORDER BY l.id DESC LIMIT 5', [$c['id']]);
    render('shares/index', ['c' => $c, 'sh' => $sh, 'movs' => $movs, 'names' => $names, 'issued' => $issued, 'deliveries' => $deliveries, 'companies' => companies_for_user(),
        'tab' => getp('tab', 'vigentes'), 'title' => 'Registro de accionistas · ' . $c['razon_social']]);
}

function ctl_accionistas_nuevo(): void
{
    require_perm('accionistas', 'editar');
    $c = require_company((int)(getp('c') ?: post('company_id')));
    $series = all('SELECT * FROM series WHERE company_id=? ORDER BY id', [$c['id']]);
    $members = all("SELECT m.id AS mid, m.numero AS socio_n, p.* FROM members m JOIN persons p ON p.id=m.person_id WHERE m.company_id=? ORDER BY m.numero", [$c['id']]);
    $bal = balances((int)$c['id']);
    $errors = [];
    $f = ['tipo' => getp('tipo', 'TRANSFERENCIA'), 'fecha' => today()];
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        $f = [
            'tipo' => post('tipo'), 'series_id' => (int)post('series_id'), 'fecha' => ndate(post('fecha')),
            'vendedor' => (int)post('vendedor_member_id') ?: null, 'comprador' => (int)post('comprador_member_id'),
            'cantidad' => nint(post('cantidad')), 'precio_total' => nint(post('precio_total')),
            'titulo_inutilizado' => nn(post('titulo_inutilizado')), 'titulo_nuevo_comprador' => nn(post('titulo_nuevo_comprador')), 'titulo_nuevo_vendedor' => nn(post('titulo_nuevo_vendedor')),
            'cert_codigo' => trim((string)post('cert_codigo')), 'cert_vendedor_codigo' => nn(post('cert_vendedor_codigo')), 'observaciones' => nn(post('observaciones')),
            'res_pendiente' => $c['regimen'] === 'RES' && isset($_POST['res_pendiente']),
        ];
        if ($c['estado'] !== 'ACTIVA') $errors[] = 'La sociedad está inactiva.';
        if (!in_array($f['tipo'], ['EMISION', 'TRANSFERENCIA'], true)) $errors[] = 'Tipo de movimiento inválido.';
        $serie = one('SELECT * FROM series WHERE id=? AND company_id=?', [$f['series_id'], $c['id']]);
        if (!$serie) $errors[] = 'Seleccione la serie.';
        if (!$f['fecha']) $errors[] = 'Fecha inválida.';
        elseif ($f['fecha'] > today()) $errors[] = 'La fecha no puede ser futura.';
        if (!$f['cantidad'] || $f['cantidad'] <= 0) $errors[] = 'La cantidad de acciones debe ser mayor a cero.';
        $comp = one('SELECT m.*, p.dd_estado, p.id AS pid FROM members m JOIN persons p ON p.id=m.person_id WHERE m.id=? AND m.company_id=?', [$f['comprador'], $c['id']]);
        if (!$comp) $errors[] = 'Seleccione el comprador desde el registro de socios de la sociedad.';
        elseif ($comp['dd_estado'] !== 'APROBADA') $errors[] = 'El comprador no tiene la debida diligencia aprobada (fue reabierta o rechazada).';
        elseif ($f['fecha'] && $f['fecha'] < $comp['fecha_ingreso']) $errors[] = 'La fecha del movimiento es anterior al ingreso del comprador al registro de socios (' . fdate($comp['fecha_ingreso']) . ').';
        if ($f['tipo'] === 'TRANSFERENCIA') {
            $vend = one('SELECT * FROM members WHERE id=? AND company_id=?', [$f['vendedor'], $c['id']]);
            if (!$vend) $errors[] = 'Seleccione el vendedor.';
            elseif ($f['vendedor'] === $f['comprador']) $errors[] = 'Vendedor y comprador no pueden ser la misma persona.';
            elseif ($serie && $f['cantidad'] && ($bal[$f['vendedor']][$f['series_id']] ?? 0) < $f['cantidad']) $errors[] = 'El vendedor solo tiene ' . fnum($bal[$f['vendedor']][$f['series_id']] ?? 0) . ' acciones de ' . $serie['nombre'] . '.';
        } else {
            $f['vendedor'] = null;
            $f['cert_vendedor_codigo'] = null;
            if ($serie && $serie['acciones_autorizadas'] !== null && $f['cantidad']) {
                $em = issued_by_series((int)$c['id'])[(int)$serie['id']] ?? 0;
                if ($em + $f['cantidad'] > (int)$serie['acciones_autorizadas']) $errors[] = 'La emisión excede las acciones autorizadas de la serie (disponibles: ' . fnum((int)$serie['acciones_autorizadas'] - $em) . ').';
            }
        }
        if ($f['res_pendiente']) {
            $f['cert_codigo'] = null; // se completará cuando el accionista inscriba su compra en el RES
        } else {
            if ($e = validate_cert_code($c['regimen'], $f['cert_codigo'])) $errors[] = 'Certificado del comprador: ' . $e;
            elseif (val('SELECT 1 FROM movements WHERE cert_codigo=? OR cert_vendedor_codigo=?', [$f['cert_codigo'], $f['cert_codigo']])) $errors[] = 'El código de certificado ' . $f['cert_codigo'] . ' ya fue registrado en otro movimiento.';
            if (empty($_FILES['cert_pdf']['name'])) $errors[] = 'Debe subir el certificado del comprador (PDF) entregado por la sociedad.';
        }
        if ($f['cert_vendedor_codigo']) {
            if ($e = validate_cert_code($c['regimen'], $f['cert_vendedor_codigo'])) $errors[] = 'Certificado del vendedor: ' . $e;
            elseif ($f['cert_vendedor_codigo'] === $f['cert_codigo'] || val('SELECT 1 FROM movements WHERE cert_codigo=? OR cert_vendedor_codigo=?', [$f['cert_vendedor_codigo'], $f['cert_vendedor_codigo']])) $errors[] = 'El código del certificado del vendedor ya está registrado.';
        }
        if (!$errors) {
            db()->beginTransaction();
            try {
                $n = (int)val('SELECT COALESCE(MAX(numero),0)+1 FROM movements WHERE company_id=? FOR UPDATE', [$c['id']]);
                $chainErr = validate_chain((int)$c['id'], ['id' => 0, 'fecha' => $f['fecha'], 'numero' => $n, 'series_id' => $f['series_id'], 'vendedor_member_id' => $f['vendedor'], 'comprador_member_id' => $f['comprador'], 'cantidad' => $f['cantidad']]);
                if ($chainErr) throw new RuntimeException($chainErr);
                q('INSERT INTO movements (company_id,series_id,numero,fecha,tipo,vendedor_member_id,comprador_member_id,cantidad,precio_total,titulo_inutilizado,titulo_nuevo_comprador,titulo_nuevo_vendedor,cert_codigo,cert_vendedor_codigo,observaciones,res_pendiente,creado_por,creado_en) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                    $c['id'], $f['series_id'], $n, $f['fecha'], $f['tipo'], $f['vendedor'], $f['comprador'], $f['cantidad'], $f['precio_total'],
                    $f['titulo_inutilizado'], $f['titulo_nuevo_comprador'], $f['titulo_nuevo_vendedor'], $f['cert_codigo'], $f['cert_vendedor_codigo'], $f['observaciones'], $f['res_pendiente'] ? 1 : 0, current_user()['id'], now()]);
                $mid = (int)db()->lastInsertId();
                $doc = $f['res_pendiente'] ? null : store_upload('cert_pdf', 'movement', $mid, 'CERT_COMPRADOR', ['pdf']);
                if (!$doc && !$f['res_pendiente']) throw new RuntimeException('Debe subir el certificado del comprador (PDF).');
                $docV = $f['cert_vendedor_codigo'] ? store_upload('cert_vendedor_pdf', 'movement', $mid, 'CERT_VENDEDOR', ['pdf']) : null;
                if ($f['cert_vendedor_codigo'] && !$docV) throw new RuntimeException('Si informa el código del certificado del vendedor, adjunte también su PDF.');
                q('UPDATE movements SET cert_doc_id=?, cert_vendedor_doc_id=? WHERE id=?', [$doc, $docV, $mid]);
                if ($f['vendedor']) check_full_sale((int)$c['id'], $f['vendedor'], $mid, $f['fecha']);
                cert_invalidate($f['comprador'], $f['series_id'], 'Movimiento N° ' . $n . ' (compra)');
                if ($f['vendedor']) cert_invalidate($f['vendedor'], $f['series_id'], 'Movimiento N° ' . $n . ' (venta)');
                $gravVend = $f['vendedor'] ? (liens_vigentes((int)$c['id'])[$f['vendedor']][$f['series_id']] ?? []) : [];
                db()->commit();
            } catch (Throwable $ex) {
                db()->rollBack();
                $errors[] = $ex->getMessage();
            }
            if (!$errors) {
                audit($f['tipo'] === 'EMISION' ? 'EMISION_ACCIONES' : 'TRANSFERENCIA_ACCIONES', 'movement', $mid, (int)$c['id'], [
                    'numero' => $n, 'serie' => $serie['nombre'], 'cantidad' => $f['cantidad'], 'vendedor' => $f['vendedor'], 'comprador' => $f['comprador'], 'certificado' => $f['cert_codigo']]);
                flash('ok', 'Movimiento N° ' . $n . ' registrado. Los saldos se actualizaron automáticamente.' . ($f['res_pendiente'] ? ' Quedó con «Registro RES pendiente»: complete el CVE cuando el accionista inscriba su compra.' : ''));
                if ($gravVend) flash('warn', 'Atención: el vendedor tiene ' . count($gravVend) . ' gravamen(es) vigente(s) sobre acciones de esta serie (' . implode('; ', array_map(fn($g) => (TIPOS_GRAVAMEN[$g['tipo']] ?? $g['tipo']) . ' a favor de ' . $g['a_favor_de'] . ' por ' . fnum($g['cantidad']) . ' acc.', $gravVend)) . '). Revise el registro de gravámenes.');
                redirect('accionistas', ['c' => $c['id'], 'tab' => 'movimientos']);
            }
        }
    }
    $liens = liens_vigentes((int)$c['id']);
    render('shares/form', compact('c', 'series', 'members', 'bal', 'errors', 'f', 'liens') + ['title' => 'Nuevo movimiento · ' . $c['razon_social']]);
}

function ctl_accionistas_ficha(): void
{
    require_perm('accionistas');
    $c = require_company((int)getp('c'));
    $m = one('SELECT m.id AS mid, m.numero AS socio_n, m.fecha_ingreso, p.* FROM members m JOIN persons p ON p.id=m.person_id WHERE m.id=? AND m.company_id=?', [(int)getp('m'), $c['id']]);
    if (!$m) redirect('accionistas', ['c' => $c['id']]);
    $rows = shareholder_sheet((int)$c['id'], (int)$m['mid']);
    $bySeries = balances((int)$c['id'])[(int)$m['mid']] ?? [];
    $series = [];
    foreach (all('SELECT * FROM series WHERE company_id=?', [$c['id']]) as $s) $series[(int)$s['id']] = $s['nombre'];
    $firma = one("SELECT * FROM documents WHERE owner_type='member' AND owner_id=? AND tipo='FIRMA' AND vigente=1 ORDER BY id DESC LIMIT 1", [$m['mid']]);
    $certs = all("SELECT d.*, mv.numero FROM documents d JOIN movements mv ON mv.id=d.owner_id WHERE d.owner_type='movement' AND mv.anulado=0 AND ((d.tipo='CERT_COMPRADOR' AND mv.comprador_member_id=?) OR (d.tipo='CERT_VENDEDOR' AND mv.vendedor_member_id=?)) ORDER BY mv.fecha, mv.numero", [$m['mid'], $m['mid']]);
    $grav = [];
    try { $grav = all('SELECT g.*, s.nombre AS serie FROM gravamenes g JOIN series s ON s.id=g.series_id WHERE g.member_id=? ORDER BY g.fecha_anotacion', [$m['mid']]); } catch (Throwable $e) {}
    $ccs = [];
    try { $ccs = all("SELECT ce.*, s.nombre AS serie FROM certificates ce JOIN series s ON s.id=ce.series_id WHERE ce.member_id=? AND ce.estado IN ('SIN_FIRMA','FIRMADO') ORDER BY s.id", [$m['mid']]); } catch (Throwable $e) {}
    render('shares/sheet', compact('c', 'm', 'rows', 'bySeries', 'series', 'firma', 'certs', 'grav', 'ccs') + ['title' => 'Hoja de accionista · ' . person_name($m)]);
}

function ctl_accionistas_anular(): void
{
    if (!is_master()) { flash('error', 'Solo el Máster puede anular movimientos.'); redirect('accionistas'); }
    csrf_check();
    $mv = one('SELECT * FROM movements WHERE id=? AND anulado=0', [(int)post('id')]);
    if (!$mv) redirect('accionistas');
    $c = require_company((int)$mv['company_id']);
    $motivo = nn(post('motivo'));
    if (!$motivo) { flash('error', 'Indique el motivo de la anulación.'); redirect('accionistas', ['c' => $c['id'], 'tab' => 'movimientos']); }
    if ($err = validate_chain((int)$c['id'], null, (int)$mv['id'])) {
        flash('error', 'No se puede anular: ' . $err . ' Anule primero los movimientos posteriores que dependen de este.');
        redirect('accionistas', ['c' => $c['id'], 'tab' => 'movimientos']);
    }
    q('UPDATE movements SET anulado=1, anulado_motivo=?, anulado_por=?, anulado_en=? WHERE id=?', [$motivo, current_user()['id'], now(), $mv['id']]);
    cert_invalidate((int)$mv['comprador_member_id'], (int)$mv['series_id'], 'Anulación del movimiento N° ' . $mv['numero']);
    if ($mv['vendedor_member_id']) cert_invalidate((int)$mv['vendedor_member_id'], (int)$mv['series_id'], 'Anulación del movimiento N° ' . $mv['numero']);
    $txt = 'Se anuló la transferencia N° ' . $mv['numero'] . ' del ' . fdate($mv['fecha']) . '. Motivo: ' . $motivo;
    foreach (array_filter([$mv['vendedor_member_id'], $mv['comprador_member_id']]) as $mid) {
        q('INSERT INTO member_notes (member_id,fecha,texto,automatica,movement_id,creado_por,creado_en) VALUES (?,?,?,?,?,?,?)', [$mid, today(), $txt, 1, $mv['id'], current_user()['id'], now()]);
    }
    audit('MOVIMIENTO_ANULADO', 'movement', (int)$mv['id'], (int)$c['id'], ['numero' => $mv['numero'], 'motivo' => $motivo]);
    flash('ok', 'Movimiento N° ' . $mv['numero'] . ' anulado. Los saldos fueron recalculados.');
    redirect('accionistas', ['c' => $c['id'], 'tab' => 'movimientos']);
}

function ctl_accionistas_excel(): void
{
    require_perm('accionistas');
    $c = require_company((int)getp('c'));
    if ($c['regimen'] !== 'RES') { flash('error', 'Las sociedades del sistema tradicional emiten el Libro de Accionistas en PDF.'); redirect('accionistas', ['c' => $c['id']]); }
    $sh = current_shareholders((int)$c['id']);
    $head = ['N° socio', 'Accionista', 'RUT', 'Nacionalidad', 'Domicilio', 'Correo'];
    foreach ($sh['series'] as $s) $head[] = serie_label($s['nombre']);
    array_push($head, 'Total acciones', '% del capital', 'Votos', '% de votos');
    $rows = [$head];
    foreach ($sh['rows'] as $r) {
        $m = $r['member'];
        $line = [str_pad((string)$m['socio_numero'], 2, '0', STR_PAD_LEFT), person_name($m), rut_format($m['rut']), $m['nacionalidad'], person_address($m), $m['email']];
        foreach ($sh['series'] as $s) $line[] = (int)($r['series'][(int)$s['id']] ?? 0);
        array_push($line, $r['total'], round($r['pct'], 4), $r['votos'], round($r['pct_votos'], 4));
        $rows[] = $line;
    }
    $tot = ['', 'TOTAL', '', '', '', ''];
    foreach ($sh['series'] as $s) $tot[] = array_sum(array_map(fn($r) => (int)($r['series'][(int)$s['id']] ?? 0), $sh['rows']));
    array_push($tot, $sh['total'], 100, array_sum(array_column($sh['rows'], 'votos')), 100);
    $rows[] = $tot;

    $mov = [['N° transferencia', 'Fecha', 'Tipo', 'Serie', 'Vendido por', 'Comprado por', 'Cantidad', 'Precio total', 'Título inutilizado', 'Título nuevo comprador', 'Título nuevo vendedor', 'Código certificado comprador', 'Código certificado vendedor', 'Observaciones', 'Estado']];
    $names = [];
    foreach (all('SELECT m.id AS mid, p.tipo, p.nombres, p.apellido_paterno, p.apellido_materno, p.razon_social, p.rut FROM members m JOIN persons p ON p.id=m.person_id WHERE m.company_id=?', [$c['id']]) as $x) $names[(int)$x['mid']] = person_name($x) . ' (' . rut_format($x['rut']) . ')';
    foreach (movements_of((int)$c['id'], true) as $m) {
        $mov[] = [(int)$m['numero'], fdate($m['fecha']), $m['tipo'] === 'EMISION' ? 'Emisión' : 'Transferencia', $m['serie'], $m['vendedor_member_id'] ? $names[(int)$m['vendedor_member_id']] : 'La sociedad (emisión)',
            $names[(int)$m['comprador_member_id']], (int)$m['cantidad'], $m['precio_total'] !== null ? (int)$m['precio_total'] : '', $m['titulo_inutilizado'], $m['titulo_nuevo_comprador'], $m['titulo_nuevo_vendedor'],
            $m['cert_codigo'] ?? (!empty($m['res_pendiente']) ? 'Registro RES pendiente' : ''), $m['cert_vendedor_codigo'], $m['observaciones'], $m['anulado'] ? 'ANULADO: ' . $m['anulado_motivo'] : 'Vigente'];
    }
    $hojas = [['Accionista', 'RUT', 'Fecha', 'N° transferencia', 'Serie', 'Título inutilizado', 'Comprado a', 'Vendido a', 'Título nuevo comprador', 'Título nuevo vendedor', 'Compras', 'Ventas', 'Saldo', 'Observaciones', 'N° título emitido']];
    foreach (all('SELECT m.id AS mid, p.* FROM members m JOIN persons p ON p.id=m.person_id WHERE m.company_id=? ORDER BY m.numero', [$c['id']]) as $p) {
        foreach (shareholder_sheet((int)$c['id'], (int)$p['mid']) as $r) {
            $hojas[] = [person_name($p), rut_format($p['rut']), fdate($r['fecha']), (int)$r['numero'], $r['serie'], $r['titulo_inutilizado'], $r['comprado_a'], $r['vendido_a'], $r['titulo_nuevo_comprador'], $r['titulo_nuevo_vendedor'], $r['compras'], $r['ventas'], $r['saldo'], $r['observaciones'], $r['titulo_emitido']];
        }
    }
    $x = new SimpleXlsx();
    $t = 'REGISTRO DE ACCIONISTAS (RES · registro de administración) — ' . $c['razon_social'] . ' RUT ' . rut_format($c['rut']) . ' — ' . date('d-m-Y H:i');
    $x->addSheet('Accionistas vigentes', $rows, array_merge([8, 34, 13, 13, 40, 26], array_fill(0, count($sh['series']), 12), [14, 12, 12, 11]), $t);
    $x->addSheet('Movimientos', $mov, [10, 11, 12, 10, 36, 36, 12, 13, 12, 12, 12, 18, 18, 40, 22], $t);
    $x->addSheet('Hojas por accionista', $hojas, [32, 13, 11, 10, 9, 11, 30, 30, 12, 12, 11, 11, 12, 36, 11], $t);
    $tmp = tempnam(sys_get_temp_dir(), 'xl');
    $x->save($tmp);
    audit('EXCEL_REGISTRO_ACCIONISTAS', 'company', (int)$c['id'], (int)$c['id']);
    send_download($tmp, 'registro-accionistas-' . rut_clean($c['rut']) . '-' . date('Ymd') . '.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
}

function ctl_accionistas_libro(): void
{
    require_perm('libro_pdf');
    $c = require_company((int)(getp('c') ?: post('c')));
    if ($c['regimen'] !== 'TRADICIONAL') { flash('error', 'El Libro de Accionistas en PDF corresponde solo a sociedades del sistema registral tradicional.'); redirect('accionistas', ['c' => $c['id']]); }
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('accionistas', ['c' => $c['id']]);
    csrf_check();
    if (!isset($_POST['confirmo'])) { flash('error', 'Debe confirmar que el libro se entrega al representante legal.'); redirect('accionistas', ['c' => $c['id']]); }
    $seq = (int)val('SELECT COUNT(*) FROM libro_entregas WHERE company_id=? AND YEAR(generado_en)=?', [$c['id'], date('Y')]) + 1;
    $folio = 'LA-' . rut_clean($c['rut']) . '-' . date('Y') . '-' . str_pad((string)$seq, 3, '0', STR_PAD_LEFT);
    $pdf = build_libro_pdf($c, $folio);
    $sha = hash('sha256', $pdf);
    q('INSERT INTO libro_entregas (company_id,folio,entregado_a_nombre,entregado_a_rut,generado_por,generado_en,sha256) VALUES (?,?,?,?,?,?,?)', [$c['id'], $folio, $c['rep_nombre'], $c['rep_rut'], current_user()['id'], now(), $sha]);
    audit('LIBRO_ACCIONISTAS_ENTREGADO', 'company', (int)$c['id'], (int)$c['id'], ['folio' => $folio, 'entregado_a' => $c['rep_nombre'], 'rut' => $c['rep_rut'], 'sha256' => $sha]);
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="LIBRO-DE-ACCIONISTAS-' . $folio . '.pdf"');
    header('Content-Length: ' . strlen($pdf));
    header('Cache-Control: private, no-store');
    echo $pdf;
    exit;
}

/** Completa el CVE de una compra RES registrada como «Registro RES pendiente». */
function ctl_accionistas_cve(): void
{
    require_perm('accionistas', 'editar');
    csrf_check();
    $mv = one('SELECT * FROM movements WHERE id=? AND res_pendiente=1 AND anulado=0', [(int)post('id')]);
    if (!$mv) redirect('accionistas');
    $c = require_company((int)$mv['company_id']);
    $code = trim((string)post('cve'));
    $back = ['accionistas', ['c' => $c['id'], 'tab' => 'movimientos']];
    if ($e = validate_cert_code('RES', $code)) { flash('error', $e); redirect(...$back); }
    if (val('SELECT 1 FROM movements WHERE cert_codigo=? OR cert_vendedor_codigo=?', [$code, $code])) { flash('error', 'Ese CVE ya está registrado en otro movimiento.'); redirect(...$back); }
    if (empty($_FILES['cert_pdf']['name'])) { flash('error', 'Adjunte el certificado del RES (PDF).'); redirect(...$back); }
    try { $doc = store_upload('cert_pdf', 'movement', (int)$mv['id'], 'CERT_COMPRADOR', ['pdf']); }
    catch (Throwable $e) { flash('error', $e->getMessage()); redirect(...$back); }
    q('UPDATE movements SET cert_codigo=?, cert_doc_id=?, res_pendiente=0 WHERE id=?', [$code, $doc, $mv['id']]);
    cert_invalidate((int)$mv['comprador_member_id'], (int)$mv['series_id'], 'Se registró el CVE ' . $code . ' de la compra N° ' . $mv['numero']);
    audit('CVE_REGISTRADO', 'movement', (int)$mv['id'], (int)$c['id'], ['numero' => $mv['numero'], 'cve' => $code]);
    flash('ok', 'CVE registrado en el movimiento N° ' . $mv['numero'] . '. Emita el nuevo certificado del accionista con el CVE.');
    redirect(...$back);
}
