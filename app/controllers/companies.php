<?php
declare(strict_types=1);

function ctl_sociedades_index(): void
{
    require_perm('sociedades');
    $rows = companies_for_user();
    foreach ($rows as &$c) { $c['_miss'] = company_missing($c); }
    render('companies/index', ['rows' => $rows, 'title' => 'Sociedades']);
}

const COMPANY_FIELDS = ['razon_social', 'nombre_fantasia', 'rut', 'tipo_social', 'regimen', 'giro', 'fecha_constitucion', 'domicilio', 'comuna', 'ciudad', 'region', 'email', 'telefono',
    'notaria', 'fecha_escritura', 'repertorio', 'cbr', 'fojas', 'numero_inscripcion', 'anio_inscripcion', 'fecha_diario_oficial',
    'res_fecha_inscripcion', 'res_cve', 'res_numero_atencion', 'capital_social', 'total_acciones_estatuto',
    'rep_nombre', 'rep_rut', 'rep_nacionalidad', 'rep_profesion', 'rep_cargo', 'rep_email', 'rep_telefono', 'rep_domicilio', 'poderes_fuente', 'poderes_fecha', 'poderes_detalle',
    'color1_hex', 'color1_pantone', 'color2_hex', 'color2_pantone',
    'mpd_adoptado', 'mpd_fecha_aprobacion', 'mpd_organo', 'enc_nombre', 'enc_rut', 'enc_email', 'enc_fecha_designacion', 'enc_vigencia_hasta', 'canal_denuncias',
    'matriz_fecha', 'codigo_etica_fecha', 'cert_mpd', 'cert_mpd_entidad', 'cert_mpd_vigencia', 'capacitacion_fecha', 'cumplimiento_obs'];
const COMPANY_DATES = ['fecha_constitucion', 'fecha_escritura', 'fecha_diario_oficial', 'res_fecha_inscripcion', 'poderes_fecha', 'mpd_fecha_aprobacion', 'enc_fecha_designacion', 'enc_vigencia_hasta', 'matriz_fecha', 'codigo_etica_fecha', 'cert_mpd_vigencia', 'capacitacion_fecha'];

function company_from_post(): array
{
    $d = [];
    foreach (COMPANY_FIELDS as $f) $d[$f] = nn(is_string($_POST[$f] ?? null) ? $_POST[$f] : null);
    foreach (COMPANY_DATES as $f) $d[$f] = ndate($d[$f]);
    $d['mpd_adoptado'] = isset($_POST['mpd_adoptado']) ? 1 : 0;
    $d['cert_mpd'] = isset($_POST['cert_mpd']) ? 1 : 0;
    $d['capital_social'] = nint($d['capital_social']);
    $d['total_acciones_estatuto'] = nint($d['total_acciones_estatuto']);
    foreach (['rut', 'rep_rut', 'enc_rut'] as $f) if ($d[$f]) $d[$f] = rut_clean($d[$f]);
    foreach (['color1_hex', 'color2_hex'] as $f) if ($d[$f]) $d[$f] = strtoupper('#' . ltrim($d[$f], '#'));
    if ($d['res_cve']) $d['res_cve'] = trim($d['res_cve']);
    return $d;
}

/** Series ingresadas en el formulario de creación (filas dinámicas). */
function series_from_post(): array
{
    $out = [];
    $names = (array)($_POST['serie_nombre'] ?? []);
    foreach ($names as $i => $n) {
        $n = trim((string)$n);
        if ($n === '') continue;
        $v = trim((string)($_POST['serie_votos'][$i] ?? '1'));
        $out[] = ['nombre' => $n, 'votos_raw' => $v, 'votos' => (float)str_replace(',', '.', $v === '' ? '1' : $v),
            'autorizadas' => nint($_POST['serie_autorizadas'][$i] ?? null), 'preferencias' => nn((string)($_POST['serie_preferencias'][$i] ?? ''))];
    }
    return $out;
}

function series_problems(array $series): array
{
    $err = [];
    if (!$series) $err[] = 'Defina al menos una serie de acciones (por ejemplo «Única», o «Serie A», «Serie B»…).';
    $seen = [];
    foreach ($series as $s) {
        $k = mb_strtolower($s['nombre']);
        if (isset($seen[$k])) $err[] = 'La serie «' . $s['nombre'] . '» está repetida.';
        $seen[$k] = true;
        if (mb_strlen($s['nombre']) > 60) $err[] = 'El nombre de la serie «' . $s['nombre'] . '» es demasiado largo (máx. 60).';
        if ($s['votos_raw'] !== '' && (!is_numeric(str_replace(',', '.', $s['votos_raw'])) || $s['votos'] < 0)) $err[] = 'Votos por acción inválido en la serie «' . $s['nombre'] . '».';
    }
    return $err;
}

function company_validate(array $d, ?int $id, bool $isNew): array
{
    $err = [];
    if (!$d['razon_social']) $err[] = 'Razón social es obligatoria.';
    if (!$d['rut'] || !rut_valid($d['rut'])) $err[] = 'RUT de la sociedad inválido (dígito verificador).';
    elseif (val('SELECT id FROM companies WHERE rut=? AND id<>?', [$d['rut'], $id ?? 0])) $err[] = 'Ya existe una sociedad con ese RUT.';
    if (!isset(TIPOS_SOCIALES[$d['tipo_social'] ?? ''])) $err[] = 'Seleccione el tipo social (SpA o S.A.).';
    if (!isset(REGIMENES[$d['regimen'] ?? ''])) $err[] = 'Seleccione el régimen de constitución (RES o tradicional).';
    if (!$d['fecha_constitucion']) $err[] = 'Fecha de constitución es obligatoria.';
    elseif ($d['fecha_constitucion'] > today()) $err[] = 'La fecha de constitución no puede ser futura.';
    if (!$d['domicilio'] || !$d['comuna']) $err[] = 'Domicilio y comuna son obligatorios.';
    if ($d['email'] && !filter_var($d['email'], FILTER_VALIDATE_EMAIL)) $err[] = 'Correo de la sociedad inválido.';
    if (($d['regimen'] ?? '') === 'TRADICIONAL') {
        foreach (['notaria' => 'Notaría', 'fecha_escritura' => 'Fecha de escritura', 'cbr' => 'Conservador de Bienes Raíces', 'fojas' => 'Fojas', 'numero_inscripcion' => 'Número de inscripción', 'anio_inscripcion' => 'Año de inscripción', 'fecha_diario_oficial' => 'Fecha de publicación en Diario Oficial'] as $k => $l) {
            if (!$d[$k]) $err[] = $l . ' es obligatorio en el sistema tradicional.';
        }
    }
    if (($d['regimen'] ?? '') === 'RES') {
        if (!$d['res_fecha_inscripcion']) $err[] = 'Fecha de inscripción en el RES es obligatoria.';
        if (!$d['res_cve']) $err[] = 'CVE (código de verificación electrónico) de la constitución/estatuto es obligatorio.';
    }
    if (!$d['rep_nombre']) $err[] = 'Nombre del representante legal es obligatorio.';
    if (!$d['rep_rut'] || !rut_valid($d['rep_rut'])) $err[] = 'RUT del representante legal inválido.';
    if (!$d['rep_cargo']) $err[] = 'Cargo del representante legal es obligatorio.';
    if ($d['rep_email'] && !filter_var($d['rep_email'], FILTER_VALIDATE_EMAIL)) $err[] = 'Correo del representante legal inválido.';
    if (!in_array($d['poderes_fuente'], ['CERT_PODERES', 'ACTA_DIRECTORIO'], true)) $err[] = 'Indique si los poderes constan en certificado de poderes o en acta de directorio.';
    if (!$d['poderes_fecha']) $err[] = 'Fecha del certificado de poderes o de la sesión de directorio es obligatoria.';
    foreach (['color1_hex' => 'Color primario', 'color2_hex' => 'Color secundario'] as $k => $l) {
        if ($d[$k] && !valid_hex($d[$k])) $err[] = $l . ': HEX inválido (formato #RRGGBB).';
    }
    if ($d['enc_rut'] && !rut_valid($d['enc_rut'])) $err[] = 'RUT del Encargado de Prevención inválido.';
    if ($d['enc_email'] && !filter_var($d['enc_email'], FILTER_VALIDATE_EMAIL)) $err[] = 'Correo del Encargado de Prevención inválido.';
    if ($isNew) {
        if (empty($_FILES['doc_ESTATUTO']['name'])) $err[] = 'Debe adjuntar el estatuto social (PDF).';
        $fuente = $d['poderes_fuente'] === 'ACTA_DIRECTORIO' ? 'ACTA_DIRECTORIO' : 'CERT_PODERES';
        if (empty($_FILES['doc_' . $fuente]['name'])) $err[] = 'Debe adjuntar el ' . ($fuente === 'CERT_PODERES' ? 'certificado de poderes' : 'acta de la última sesión de directorio con los poderes') . ' (PDF).';
        $err = array_merge($err, series_problems(series_from_post()));
    }
    return $err;
}

function ctl_sociedades_form(): void
{
    $id = (int)getp('id', 0);
    require_perm('sociedades', 'editar');
    if ($id && !is_master()) require_company($id);
    $c = $id ? one('SELECT * FROM companies WHERE id=?', [$id]) : null;
    if ($id && !$c) redirect('sociedades');
    $errors = [];
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        $d = company_from_post();
        $errors = company_validate($d, $id ?: null, !$id);
        if (!empty($_FILES['logo']['name']) && ($_FILES['logo']['size'] ?? 0) > 2 * 1024 * 1024) $errors[] = 'El logotipo no puede superar 2 MB.';
        if (!$errors) {
            $origId = $id;
            db()->beginTransaction();
            try {
                if ($id) {
                    $sets = implode(',', array_map(fn($f) => "$f=?", COMPANY_FIELDS));
                    q("UPDATE companies SET $sets, actualizado_en=? WHERE id=?", array_merge(array_map(fn($f) => $d[$f], COMPANY_FIELDS), [now(), $id]));
                    $before = $c;
                } else {
                    $cols = implode(',', COMPANY_FIELDS);
                    $ph = implode(',', array_fill(0, count(COMPANY_FIELDS), '?'));
                    q("INSERT INTO companies ($cols, creado_en) VALUES ($ph, ?)", array_merge(array_map(fn($f) => $d[$f], COMPANY_FIELDS), [now()]));
                    $id = (int)db()->lastInsertId();
                    foreach (series_from_post() as $sr) {
                        q('INSERT INTO series (company_id,nombre,votos_por_accion,acciones_autorizadas,preferencias,creado_en) VALUES (?,?,?,?,?,?)', [
                            $id, $sr['nombre'], $sr['votos'], $sr['autorizadas'], $sr['preferencias'], now()]);
                    }
                    if (!is_master()) q('INSERT IGNORE INTO user_companies (user_id,company_id) VALUES (?,?)', [current_user()['id'], $id]);
                }
                foreach (DOCS_SOCIEDAD as $tipo => $_) {
                    if ($tipo === 'OTRO') continue;
                    $docId = store_upload('doc_' . $tipo, 'company', $id, $tipo, ['pdf']);
                    if ($docId) audit('DOC_SOCIEDAD_SUBIDO', 'document', $docId, $id, ['tipo' => $tipo]);
                }
                if (!empty($_FILES['logo']['name'])) {
                    $docId = store_upload('logo', 'company', $id, 'LOGO', ['image'], 2);
                    $ruta = val('SELECT ruta FROM documents WHERE id=?', [$docId]);
                    q("UPDATE documents SET vigente=0 WHERE owner_type='company' AND owner_id=? AND tipo='LOGO' AND id<>?", [$id, $docId]);
                    q('UPDATE companies SET logo_path=? WHERE id=?', [$ruta, $id]);
                }
                db()->commit();
            } catch (Throwable $ex) {
                db()->rollBack();
                $id = $origId;
                $errors[] = $ex->getMessage();
            }
            if (!$errors) {
                if (isset($before)) {
                    $diff = [];
                    foreach (COMPANY_FIELDS as $f) if ((string)$before[$f] !== (string)$d[$f]) $diff[$f] = [$before[$f], $d[$f]];
                    audit('SOCIEDAD_EDITADA', 'company', $id, $id, $diff);
                    flash('ok', 'Sociedad actualizada.');
                } else {
                    audit('SOCIEDAD_CREADA', 'company', $id, $id, ['razon_social' => $d['razon_social'], 'regimen' => $d['regimen']]);
                    flash('ok', 'Sociedad registrada. Se abrió su registro de socios y su registro de accionistas.');
                }
                redirect('sociedades.ver', ['id' => $id]);
            }
        }
        $c = array_merge($c ?? [], $d);
    }
    render('companies/form', ['c' => $c, 'id' => $id, 'errors' => $errors, 'title' => $id ? 'Editar sociedad' : 'Nueva sociedad']);
}

function ctl_sociedades_ver(): void
{
    require_perm('sociedades');
    $c = require_company((int)getp('id'));
    $docs = docs_of('company', (int)$c['id']);
    $series = all('SELECT * FROM series WHERE company_id=? ORDER BY id', [$c['id']]);
    $issued = issued_by_series((int)$c['id']);
    $miss = company_missing($c);
    render('companies/view', compact('c', 'docs', 'series', 'issued', 'miss') + ['title' => $c['razon_social']]);
}

function ctl_sociedades_doc(): void
{
    require_perm('sociedades', 'editar');
    csrf_check();
    $c = require_company((int)post('id'));
    $tipo = post('tipo');
    if (!isset(DOCS_SOCIEDAD[$tipo])) { flash('error', 'Tipo de documento inválido.'); redirect('sociedades.ver', ['id' => $c['id']]); }
    try {
        $docId = store_upload('archivo', 'company', (int)$c['id'], $tipo, ['pdf']);
        if (!$docId) throw new RuntimeException('Seleccione un archivo PDF.');
        audit('DOC_SOCIEDAD_SUBIDO', 'document', $docId, (int)$c['id'], ['tipo' => $tipo]);
        flash('ok', 'Documento agregado.');
    } catch (Throwable $ex) {
        flash('error', $ex->getMessage());
    }
    redirect('sociedades.ver', ['id' => $c['id']]);
}

function ctl_sociedades_docbaja(): void
{
    require_perm('sociedades', 'editar');
    csrf_check();
    $d = one("SELECT * FROM documents WHERE id=? AND owner_type='company'", [(int)post('doc')]);
    if (!$d) redirect('sociedades');
    require_company((int)$d['owner_id']);
    q('UPDATE documents SET vigente=0 WHERE id=?', [$d['id']]);
    audit('DOC_SOCIEDAD_REEMPLAZADO', 'document', (int)$d['id'], (int)$d['owner_id'], ['tipo' => $d['tipo'], 'archivo' => $d['nombre_original']]);
    flash('ok', 'Documento marcado como no vigente (se conserva en el historial y en los respaldos).');
    redirect('sociedades.ver', ['id' => $d['owner_id']]);
}

function ctl_sociedades_serie(): void
{
    require_perm('sociedades', 'editar');
    csrf_check();
    $c = require_company((int)post('id'));
    $sid = (int)post('serie_id', 0);
    $nombre = nn(post('nombre'));
    $aut = nint(post('acciones_autorizadas'));
    $votos = (float)str_replace(',', '.', (string)post('votos_por_accion', '1'));
    if (!$nombre) { flash('error', 'El nombre de la serie es obligatorio.'); redirect('sociedades.ver', ['id' => $c['id']]); }
    if ($votos < 0) { flash('error', 'Votos por acción inválido.'); redirect('sociedades.ver', ['id' => $c['id']]); }
    if ($sid && val('SELECT 1 FROM series WHERE company_id=? AND LOWER(nombre)=LOWER(?) AND id<>?', [$c['id'], $nombre, $sid])) { flash('error', 'Ya existe otra serie con ese nombre.'); redirect('sociedades.ver', ['id' => $c['id']]); }
    if ($sid) {
        $issued = issued_by_series((int)$c['id'])[$sid] ?? 0;
        if ($aut !== null && $aut < $issued) { flash('error', 'Las acciones autorizadas no pueden ser menores que las ya emitidas (' . fnum($issued) . ').'); redirect('sociedades.ver', ['id' => $c['id']]); }
        q('UPDATE series SET nombre=?, votos_por_accion=?, acciones_autorizadas=?, preferencias=? WHERE id=? AND company_id=?', [$nombre, $votos, $aut, nn(post('preferencias')), $sid, $c['id']]);
        audit('SERIE_EDITADA', 'series', $sid, (int)$c['id'], ['nombre' => $nombre, 'autorizadas' => $aut, 'votos' => $votos]);
    } else {
        if (val('SELECT 1 FROM series WHERE company_id=? AND LOWER(nombre)=LOWER(?)', [$c['id'], $nombre])) { flash('error', 'Ya existe una serie con ese nombre.'); redirect('sociedades.ver', ['id' => $c['id']]); }
        q('INSERT INTO series (company_id,nombre,votos_por_accion,acciones_autorizadas,preferencias,creado_en) VALUES (?,?,?,?,?,?)', [$c['id'], $nombre, $votos, $aut, nn(post('preferencias')), now()]);
        audit('SERIE_CREADA', 'series', (int)db()->lastInsertId(), (int)$c['id'], ['nombre' => $nombre]);
    }
    flash('ok', 'Serie guardada.');
    redirect('sociedades.ver', ['id' => $c['id']]);
}

function ctl_sociedades_estado(): void
{
    if (!is_master()) redirect('sociedades');
    csrf_check();
    $c = require_company((int)post('id'));
    $nuevo = $c['estado'] === 'ACTIVA' ? 'INACTIVA' : 'ACTIVA';
    q('UPDATE companies SET estado=? WHERE id=?', [$nuevo, $c['id']]);
    audit('SOCIEDAD_' . $nuevo, 'company', (int)$c['id'], (int)$c['id']);
    flash('ok', 'La sociedad quedó ' . strtolower($nuevo) . '.');
    redirect('sociedades.ver', ['id' => $c['id']]);
}

function ctl_sociedades_seriebaja(): void
{
    require_perm('sociedades', 'editar');
    csrf_check();
    $c = require_company((int)post('id'));
    $s = one('SELECT * FROM series WHERE id=? AND company_id=?', [(int)post('serie_id'), $c['id']]);
    if (!$s) redirect('sociedades.ver', ['id' => $c['id']]);
    if (val('SELECT 1 FROM movements WHERE series_id=?', [$s['id']])) {
        flash('error', 'La serie «' . $s['nombre'] . '» tiene movimientos registrados y no puede eliminarse.');
    } elseif ((int)val('SELECT COUNT(*) FROM series WHERE company_id=?', [$c['id']]) <= 1) {
        flash('error', 'La sociedad debe tener al menos una serie.');
    } else {
        q('DELETE FROM series WHERE id=?', [$s['id']]);
        audit('SERIE_ELIMINADA', 'series', (int)$s['id'], (int)$c['id'], ['nombre' => $s['nombre']]);
        flash('ok', 'Serie «' . $s['nombre'] . '» eliminada.');
    }
    redirect('sociedades.ver', ['id' => $c['id']]);
}
