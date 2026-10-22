<?php
declare(strict_types=1);

const PERSON_FIELDS = ['tipo', 'rut', 'nombres', 'apellido_paterno', 'apellido_materno', 'razon_social', 'nacionalidad', 'fecha_nacimiento', 'estado_civil', 'profesion',
    'calle', 'numero', 'depto', 'comuna', 'ciudad', 'region', 'pais', 'email', 'telefono', 'jur_tipo_sociedad', 'jur_fecha_constitucion', 'jur_rep_nombre', 'jur_rep_rut'];
const ESTADOS_CIVILES = ['Soltero(a)', 'Casado(a) sociedad conyugal', 'Casado(a) separación de bienes', 'Casado(a) participación en los gananciales', 'Conviviente civil', 'Divorciado(a)', 'Viudo(a)'];

function ctl_personas_index(): void
{
    require_perm('socios');
    $where = ['1=1'];
    $p = [];
    if ($t = getp('tipo')) { $where[] = 'tipo=?'; $p[] = $t; }
    if ($dd = getp('dd')) { $where[] = 'dd_estado=?'; $p[] = $dd; }
    if ($s = getp('s')) {
        $where[] = '(rut LIKE ? OR nombres LIKE ? OR apellido_paterno LIKE ? OR apellido_materno LIKE ? OR razon_social LIKE ?)';
        $like = '%' . str_replace(['.', '-'], '', $s) . '%';
        $l2 = '%' . $s . '%';
        array_push($p, $like, $l2, $l2, $l2, $l2);
    }
    $rows = all('SELECT * FROM persons WHERE ' . implode(' AND ', $where) . ' ORDER BY COALESCE(razon_social, apellido_paterno), nombres', $p);
    foreach ($rows as &$r) {
        $r['_soc'] = all('SELECT c.razon_social FROM members m JOIN companies c ON c.id=m.company_id WHERE m.person_id=?', [$r['id']]);
    }
    render('persons/index', ['rows' => $rows, 'title' => 'Personas y debida diligencia']);
}

function person_from_post(bool $isNew): array
{
    $d = [];
    foreach (PERSON_FIELDS as $f) $d[$f] = nn(is_string($_POST[$f] ?? null) ? $_POST[$f] : null);
    $d['fecha_nacimiento'] = ndate($d['fecha_nacimiento']);
    $d['jur_fecha_constitucion'] = ndate($d['jur_fecha_constitucion']);
    if ($d['rut']) $d['rut'] = rut_clean($d['rut']);
    if ($d['jur_rep_rut']) $d['jur_rep_rut'] = rut_clean($d['jur_rep_rut']);
    if ($d['email']) $d['email'] = mb_strtolower($d['email']);
    return $d;
}

function person_validate(array $d, ?int $id): array
{
    $err = [];
    if (!in_array($d['tipo'], ['NATURAL', 'JURIDICA'], true)) $err[] = 'Indique si es persona natural o jurídica.';
    if (!$d['rut'] || !rut_valid($d['rut'])) $err[] = 'RUT inválido: el dígito verificador no corresponde.';
    elseif (val('SELECT id FROM persons WHERE rut=? AND id<>?', [$d['rut'], $id ?? 0])) $err[] = 'Ya existe una persona registrada con ese RUT. Búsquela en el directorio.';
    if ($d['tipo'] === 'NATURAL') {
        foreach (['nombres' => 'Nombres', 'apellido_paterno' => 'Apellido paterno', 'nacionalidad' => 'Nacionalidad', 'fecha_nacimiento' => 'Fecha de nacimiento', 'estado_civil' => 'Estado civil', 'profesion' => 'Profesión u oficio'] as $k => $l) {
            if (!$d[$k]) $err[] = $l . ' es obligatorio.';
        }
        if ($d['fecha_nacimiento'] && ($d['fecha_nacimiento'] > today() || $d['fecha_nacimiento'] > date('Y-m-d', strtotime('-18 years')))) $err[] = 'No pueden registrarse menores de edad: la persona debe tener 18 años cumplidos.';
    } elseif ($d['tipo'] === 'JURIDICA') {
        foreach (['razon_social' => 'Razón social', 'jur_tipo_sociedad' => 'Tipo de sociedad', 'profesion' => 'Giro', 'jur_rep_nombre' => 'Representante legal', 'jur_rep_rut' => 'RUT del representante'] as $k => $l) {
            if (!$d[$k]) $err[] = $l . ' es obligatorio.';
        }
        if ($d['jur_rep_rut'] && !rut_valid($d['jur_rep_rut'])) $err[] = 'RUT del representante legal inválido.';
    }
    foreach (['calle' => 'Calle', 'numero' => 'Número', 'comuna' => 'Comuna', 'ciudad' => 'Ciudad'] as $k => $l) if (!$d[$k]) $err[] = $l . ' del domicilio es obligatorio.';
    if (!$d['email'] || !filter_var($d['email'], FILTER_VALIDATE_EMAIL)) $err[] = 'Correo electrónico inválido.';
    if (!$d['telefono']) $err[] = 'Teléfono es obligatorio.';
    return $err;
}

function ctl_personas_form(): void
{
    require_perm('socios', 'editar');
    $id = (int)getp('id', 0);
    $p = $id ? one('SELECT * FROM persons WHERE id=?', [$id]) : null;
    if ($id && !$p) redirect('personas');
    $errors = [];
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        $d = person_from_post(!$id);
        if ($id) { $d['rut'] = $p['rut']; $d['tipo'] = $p['tipo']; } // RUT y tipo no se modifican
        $errors = person_validate($d, $id ?: null);
        if (!$errors) {
            if ($id) {
                $sets = implode(',', array_map(fn($f) => "$f=?", PERSON_FIELDS));
                q("UPDATE persons SET $sets, actualizado_en=? WHERE id=?", array_merge(array_map(fn($f) => $d[$f], PERSON_FIELDS), [now(), $id]));
                $diff = [];
                foreach (PERSON_FIELDS as $f) if ((string)$p[$f] !== (string)$d[$f]) $diff[$f] = [$p[$f], $d[$f]];
                audit('PERSONA_EDITADA', 'person', $id, null, $diff);
                flash('ok', 'Datos actualizados.');
            } else {
                $cols = implode(',', PERSON_FIELDS);
                $ph = implode(',', array_fill(0, count(PERSON_FIELDS), '?'));
                q("INSERT INTO persons ($cols, dd_checks, creado_en, creado_por) VALUES ($ph, ?, ?, ?)", array_merge(array_map(fn($f) => $d[$f], PERSON_FIELDS), [json_encode(['rut' => true]), now(), current_user()['id']]));
                $id = (int)db()->lastInsertId();
                audit('PERSONA_CREADA', 'person', $id, null, ['rut' => $d['rut'], 'nombre' => person_name($d)]);
                flash('ok', 'Persona registrada. Complete ahora la debida diligencia para habilitarla como socio.');
            }
            redirect('personas.ver', ['id' => $id]);
        }
        $p = array_merge($p ?? [], $d);
    }
    render('persons/form', ['p' => $p, 'id' => $id, 'errors' => $errors, 'title' => $id ? 'Editar persona' : 'Nueva persona']);
}

function ctl_personas_ver(): void
{
    require_perm('socios');
    $p = one('SELECT * FROM persons WHERE id=?', [(int)getp('id')]);
    if (!$p) redirect('personas');
    $docs = docs_of('person', (int)$p['id']);
    $bens = all('SELECT * FROM beneficiarios WHERE person_id=? ORDER BY porcentaje DESC', [$p['id']]);
    $memberships = all('SELECT m.*, c.razon_social, c.regimen FROM members m JOIN companies c ON c.id=m.company_id WHERE m.person_id=? ORDER BY c.razon_social', [$p['id']]);
    $ids = allowed_company_ids();
    foreach ($memberships as &$m) {
        $m['_saldo'] = member_total((int)$m['company_id'], (int)$m['id']);
        $m['_visible'] = $ids === null || in_array((int)$m['company_id'], $ids, true);
    }
    unset($m);
    $inCompanies = array_column($memberships, 'company_id');
    $available = array_filter(companies_for_user(true), fn($c) => !in_array($c['id'], $inCompanies));
    $revisor = $p['dd_revisor'] ? one('SELECT nombre, email FROM users WHERE id=?', [$p['dd_revisor']]) : null;
    $portal = null;
    try { $portal = one('SELECT * FROM portal_users WHERE person_id=?', [$p['id']]); } catch (Throwable $e) {}
    render('persons/view', compact('p', 'docs', 'bens', 'memberships', 'available', 'revisor', 'portal') + ['title' => person_name($p)]);
}

/** Estado de la lista de verificación: [clave => ['ok'=>bool,'doc'=>bool]] */
function dd_status(array $p): array
{
    $checks = json_decode((string)$p['dd_checks'], true) ?: [];
    $present = doc_types_present('person', (int)$p['id']);
    $res = [];
    foreach (DD_ITEMS as $k => [$label, $docType, $onlyJur]) {
        if ($onlyJur && $p['tipo'] !== 'JURIDICA') continue;
        $res[$k] = ['label' => $label, 'checked' => !empty($checks[$k]), 'docType' => $docType, 'doc' => $docType === null || in_array($docType, $present, true)];
    }
    $res['rut']['checked'] = rut_valid($p['rut']);
    return $res;
}

function dd_problems(array $p): array
{
    $pr = [];
    foreach (dd_status($p) as $k => $s) {
        if (!$s['checked']) $pr[] = 'Falta marcar: ' . $s['label'];
        if (!$s['doc']) $pr[] = 'Falta documento de respaldo: ' . $s['label'];
    }
    if (!in_array($p['dd_pep'], ['SI', 'NO'], true)) $pr[] = 'Indique si es Persona Expuesta Políticamente (PEP).';
    if ($p['dd_pep'] === 'SI' && !$p['dd_pep_detalle']) $pr[] = 'Detalle el cargo o vínculo PEP (requiere diligencia reforzada).';
    if (!$p['dd_listas_resultado'] || !$p['dd_listas_fecha']) $pr[] = 'Registre el resultado y la fecha de la revisión en listas de sanciones.';
    if ($p['dd_listas_resultado'] === 'CON_COINCIDENCIAS') $pr[] = 'La revisión en listas de sanciones arrojó coincidencias: no se puede aprobar.';
    if (!$p['dd_origen_fondos']) $pr[] = 'Describa el origen de los fondos.';
    if ($p['tipo'] === 'JURIDICA') {
        $bens = all('SELECT * FROM beneficiarios WHERE person_id=?', [$p['id']]);
        if (!$bens) $pr[] = 'Registre al menos un beneficiario final.';
    }
    return $pr;
}

function ctl_personas_dd(): void
{
    require_perm('socios', 'editar');
    csrf_check();
    $p = one('SELECT * FROM persons WHERE id=?', [(int)post('id')]);
    if (!$p) redirect('personas');
    $id = (int)$p['id'];
    if ($p['dd_estado'] === 'APROBADA' && post('accion') !== 'reabrir') {
        flash('error', 'La diligencia está aprobada. Solo el Máster puede reabrirla.');
        redirect('personas.ver', ['id' => $id]);
    }
    $accion = post('accion');
    if ($accion === 'reabrir') {
        if (!is_master()) redirect('personas.ver', ['id' => $id]);
        q("UPDATE persons SET dd_estado='PENDIENTE' WHERE id=?", [$id]);
        audit('DD_REABIERTA', 'person', $id, null, ['motivo' => post('motivo')]);
        flash('ok', 'Diligencia reabierta.');
        redirect('personas.ver', ['id' => $id]);
    }
    // Guardar casillas y datos
    $checks = [];
    foreach (DD_ITEMS as $k => $_) $checks[$k] = isset($_POST['chk_' . $k]);
    $checks['rut'] = rut_valid($p['rut']);
    try {
        foreach (DD_ITEMS as $k => [$label, $docType]) {
            if ($docType && ($docId = store_upload('doc_' . $k, 'person', $id, $docType, ['pdf', 'image']))) {
                audit('DD_DOC_SUBIDO', 'document', $docId, null, ['persona' => $id, 'tipo' => $docType]);
            }
        }
    } catch (Throwable $ex) {
        flash('error', $ex->getMessage());
    }
    q('UPDATE persons SET dd_checks=?, dd_pep=?, dd_pep_detalle=?, dd_listas_resultado=?, dd_listas_fecha=?, dd_origen_fondos=?, dd_obs=?, actualizado_en=? WHERE id=?', [
        json_encode($checks), nn(post('dd_pep')), nn(post('dd_pep_detalle')), nn(post('dd_listas_resultado')), ndate(post('dd_listas_fecha')), nn(post('dd_origen_fondos')), nn(post('dd_obs')), now(), $id]);
    // Beneficiarios finales
    if ($p['tipo'] === 'JURIDICA' && isset($_POST['ben_nombre']) && is_array($_POST['ben_nombre'])) {
        q('DELETE FROM beneficiarios WHERE person_id=?', [$id]);
        foreach ($_POST['ben_nombre'] as $i => $n) {
            $n = trim((string)$n);
            if ($n === '') continue;
            $pct = (float)str_replace(',', '.', (string)($_POST['ben_pct'][$i] ?? 0));
            $brut = trim((string)($_POST['ben_rut'][$i] ?? ''));
            q('INSERT INTO beneficiarios (person_id,nombre,rut,porcentaje,es_pep) VALUES (?,?,?,?,?)', [$id, $n, $brut ? rut_clean($brut) : null, $pct, !empty($_POST['ben_pep'][$i]) ? 1 : 0]);
        }
    }
    audit('DD_GUARDADA', 'person', $id, null, ['checks' => $checks]);
    $p = one('SELECT * FROM persons WHERE id=?', [$id]);
    if ($accion === 'aprobar') {
        $pr = dd_problems($p);
        if ($pr) {
            flash('error', 'No se puede aprobar la diligencia: ' . implode(' · ', $pr));
        } else {
            q("UPDATE persons SET dd_estado='APROBADA', dd_revisor=?, dd_fecha=? WHERE id=?", [current_user()['id'], now(), $id]);
            audit('DD_APROBADA', 'person', $id, null, ['rut' => $p['rut']]);
            flash('ok', 'Debida diligencia aprobada. La persona queda habilitada para ingresar al registro de socios.');
        }
    } elseif ($accion === 'rechazar') {
        if (!nn(post('dd_obs'))) {
            flash('error', 'Para rechazar indique el motivo en observaciones.');
        } else {
            q("UPDATE persons SET dd_estado='RECHAZADA', dd_revisor=?, dd_fecha=? WHERE id=?", [current_user()['id'], now(), $id]);
            audit('DD_RECHAZADA', 'person', $id, null, ['motivo' => post('dd_obs')]);
            flash('warn', 'Debida diligencia rechazada. La persona no podrá ingresar como socio.');
        }
    } else {
        flash('ok', 'Avance de la diligencia guardado.');
    }
    redirect('personas.ver', ['id' => $id]);
}

function ctl_personas_excel(): void
{
    require_perm('socios');
    $rows = [['RUT', 'Tipo', 'Nombre / Razón social', 'Nacionalidad', 'Fecha nacimiento / constitución', 'Edad', 'Estado civil', 'Profesión / giro', 'Domicilio', 'Correo', 'Teléfono', 'Representante (jurídica)', 'PEP', 'Listas sanciones', 'Estado diligencia', 'Fecha diligencia', 'Sociedades']];
    foreach (all('SELECT * FROM persons ORDER BY COALESCE(razon_social, apellido_paterno)') as $p) {
        $soc = implode('; ', array_column(all('SELECT c.razon_social FROM members m JOIN companies c ON c.id=m.company_id WHERE m.person_id=?', [$p['id']]), 'razon_social'));
        $rows[] = [rut_format($p['rut']), $p['tipo'] === 'NATURAL' ? 'Natural' : 'Jurídica', person_name($p), $p['nacionalidad'], fdate($p['tipo'] === 'NATURAL' ? $p['fecha_nacimiento'] : $p['jur_fecha_constitucion']),
            $p['tipo'] === 'NATURAL' ? edad($p['fecha_nacimiento']) : '', $p['estado_civil'], $p['profesion'], person_address($p), $p['email'], $p['telefono'],
            $p['jur_rep_nombre'] ? $p['jur_rep_nombre'] . ' ' . rut_format($p['jur_rep_rut']) : '', $p['dd_pep'], $p['dd_listas_resultado'], $p['dd_estado'], fdatetime($p['dd_fecha']), $soc];
    }
    $x = new SimpleXlsx();
    $x->addSheet('Personas', $rows, [14, 10, 34, 14, 14, 7, 18, 22, 40, 26, 14, 30, 6, 18, 14, 16, 40], 'Directorio de personas y debida diligencia — ' . date('d-m-Y H:i'));
    $tmp = tempnam(sys_get_temp_dir(), 'xl');
    $x->save($tmp);
    audit('EXCEL_PERSONAS', 'person', null);
    send_download($tmp, 'personas-debida-diligencia-' . date('Ymd') . '.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
}

