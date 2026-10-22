<?php
declare(strict_types=1);

const MODULOS = [
    'sociedades'  => 'Sociedades',
    'socios'      => 'Registro de socios y debida diligencia',
    'accionistas' => 'Registro de accionistas',
    'libro_pdf'   => 'Entrega del Libro de Accionistas (PDF)',
    'certificados'   => 'Certificados de accionistas (emitir y subir firmados) y acceso de accionistas al portal',
    'cert_sin_firma' => 'Ver certificados sin firma',
    'gravamenes'     => 'Registro de gravámenes',
    'bitacora'    => 'Bitácora de actividad',
];
const MODULOS_SOLO_VER = ['bitacora', 'libro_pdf', 'cert_sin_firma'];
const TIPOS_GRAVAMEN = ['PRENDA' => 'Prenda', 'EMBARGO' => 'Embargo', 'PROHIBICION' => 'Prohibición de gravar y enajenar', 'USUFRUCTO' => 'Usufructo', 'MEDIDA_PRECAUTORIA' => 'Medida precautoria', 'OTRO' => 'Otro'];
const MODULOS_MASTER = ['usuarios' => 'Usuarios y permisos', 'respaldos' => 'Respaldos y feriados'];

const TIPOS_SOCIALES = ['SPA' => 'Sociedad por Acciones (SpA)', 'SA_CERRADA' => 'Sociedad Anónima cerrada', 'SA_ABIERTA' => 'Sociedad Anónima abierta'];
const REGIMENES = ['RES' => 'Registro de Empresas y Sociedades (RES · Ley 20.659)', 'TRADICIONAL' => 'Sistema registral tradicional (Conservador de Bienes Raíces)'];

const DOCS_SOCIEDAD = [
    'ESTATUTO'         => ['Estatuto social vigente', true],
    'CERT_PODERES'     => ['Certificado de poderes / vigencia de poderes', false],
    'ACTA_DIRECTORIO'  => ['Última sesión de directorio que otorga poderes', false],
    'MPD'              => ['Modelo de Prevención de Delitos (Ley 20.393)', true],
    'ACTA_MPD'         => ['Acta de aprobación del MPD', true],
    'DESIG_ENCARGADO'  => ['Designación del Encargado de Prevención', true],
    'MATRIZ_RIESGOS'   => ['Matriz de riesgos de delitos', true],
    'CODIGO_ETICA'     => ['Código de ética / conducta', true],
    'CANAL_DENUNCIAS'  => ['Procedimiento del canal de denuncias', true],
    'CAPACITACION'     => ['Registro de la última capacitación', true],
    'CERT_MPD'         => ['Certificado del MPD (opcional)', false],
    'OTRO'             => ['Otro documento', false],
];
const DOCS_COMPLIANCE = ['MPD', 'ACTA_MPD', 'DESIG_ENCARGADO', 'MATRIZ_RIESGOS', 'CODIGO_ETICA', 'CANAL_DENUNCIAS', 'CAPACITACION'];

/** Lista de verificación de debida diligencia. [clave => [texto, documento requerido, solo jurídica]] */
const DD_ITEMS = [
    'rut'          => ['RUT validado (dígito verificador) y sin duplicados en el sistema', null, false],
    'identidad'    => ['Identidad verificada: cédula de identidad vigente (natural) o escritura de constitución y certificado de vigencia (jurídica)', 'DD_IDENTIDAD', false],
    'pep'          => ['Declaración de Persona Expuesta Políticamente (PEP) firmada', 'DD_PEP', false],
    'listas'       => ['Revisión en listas de sanciones (Consejo de Seguridad ONU, OFAC) con evidencia', 'DD_LISTAS', false],
    'beneficiario' => ['Beneficiarios finales identificados (participación ≥ 10 % o control)', 'DD_BENEFICIARIO', true],
    'fondos'       => ['Declaración de origen de fondos', 'DD_FONDOS', false],
    'ley20393'     => ['Declaración Ley 20.393 / 21.595 (no condenas por delitos económicos y compromiso de cumplimiento)', 'DD_LEY20393', false],
];

function e(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function q(string $sql, array $p = []): PDOStatement { $st = db()->prepare($sql); $st->execute($p); return $st; }
function one(string $sql, array $p = []): ?array { $r = q($sql, $p)->fetch(); return $r === false ? null : $r; }
function all(string $sql, array $p = []): array { return q($sql, $p)->fetchAll(); }
function val(string $sql, array $p = []): mixed { $r = q($sql, $p)->fetchColumn(); return $r === false ? null : $r; }
function now(): string { return date('Y-m-d H:i:s'); }
function today(): string { return date('Y-m-d'); }

function url(string $r = 'dashboard', array $params = []): string
{
    $params = array_merge(['r' => $r], $params);
    return 'index.php?' . http_build_query($params);
}
function redirect(string $r, array $params = []): never { header('Location: ' . url($r, $params)); exit; }
function flash(string $type, string $msg): void { $_SESSION['flash'][] = [$type, $msg]; }
function post(string $k, mixed $default = ''): mixed { $v = $_POST[$k] ?? $default; return is_string($v) ? trim($v) : $v; }
function getp(string $k, mixed $default = ''): mixed { $v = $_GET[$k] ?? $default; return is_string($v) ? trim($v) : $v; }
function nn(?string $v): ?string { $v = $v === null ? null : trim($v); return $v === '' ? null : $v; }
function ndate(?string $v): ?string { $v = nn($v); if ($v === null) return null; $d = DateTime::createFromFormat('Y-m-d', $v); return $d && $d->format('Y-m-d') === $v ? $v : null; }
function nint(mixed $v): ?int { $v = is_string($v) ? str_replace(['.', ' ', '$'], '', $v) : $v; return ($v === '' || $v === null || !is_numeric($v)) ? null : (int)$v; }

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="_csrf" value="' . csrf_token() . '">'; }
function csrf_check(): void
{
    if (!hash_equals($_SESSION['csrf'] ?? '', (string)($_POST['_csrf'] ?? ''))) {
        http_response_code(400);
        exit('Solicitud inválida (token de seguridad). Vuelva atrás y recargue la página.');
    }
}

// ---------- RUT ----------
function rut_clean(string $rut): string { return strtoupper(preg_replace('/[^0-9kK]/', '', $rut)); }
function rut_dv(int $num): string
{
    $s = 1; $m = 0;
    for (; $num; $num = intdiv($num, 10)) $s = ($s + $num % 10 * (9 - $m++ % 6)) % 11;
    return $s ? (string)($s - 1) : 'K';
}
function rut_valid(string $rut): bool
{
    $c = rut_clean($rut);
    if (strlen($c) < 2) return false;
    $num = substr($c, 0, -1); $dv = substr($c, -1);
    if (!ctype_digit($num) || (int)$num < 100000) return false;
    return rut_dv((int)$num) === $dv;
}
function rut_format(?string $rut): string
{
    if (!$rut) return '';
    $c = rut_clean($rut);
    if (strlen($c) < 2) return $rut;
    $num = substr($c, 0, -1); $dv = substr($c, -1);
    return number_format((int)$num, 0, '', '.') . '-' . $dv;
}

// ---------- Formatos ----------
function fnum(mixed $n): string { return $n === null || $n === '' ? '' : number_format((float)$n, 0, ',', '.'); }
function fpct(float $n): string { return number_format($n, 2, ',', '.') . ' %'; }
function fdate(?string $d): string { if (!$d) return ''; $t = strtotime($d); return $t ? date('d-m-Y', $t) : ''; }
function fdatetime(?string $d): string { if (!$d) return ''; $t = strtotime($d); return $t ? date('d-m-Y H:i', $t) : ''; }
function edad(?string $fnac): string
{
    if (!$fnac) return '';
    return (string)(new DateTime($fnac))->diff(new DateTime())->y;
}
function person_name(array $p): string
{
    if (($p['tipo'] ?? '') === 'JURIDICA') return (string)$p['razon_social'];
    return trim(($p['nombres'] ?? '') . ' ' . ($p['apellido_paterno'] ?? '') . ' ' . ($p['apellido_materno'] ?? ''));
}
function person_address(array $p): string
{
    $a = trim(($p['calle'] ?? '') . ' ' . ($p['numero'] ?? ''));
    if (!empty($p['depto'])) $a .= ctype_digit((string)$p['depto']) ? ', depto. ' . $p['depto'] : ', ' . $p['depto'];
    $parts = array_filter([$a, $p['comuna'] ?? '', $p['ciudad'] ?? '', $p['region'] ?? '']);
    return implode(', ', $parts);
}
function valid_hex(?string $h): bool { return (bool)preg_match('/^#[0-9A-Fa-f]{6}$/', (string)$h); }

// ---------- Usuario / permisos ----------
function current_user(): ?array
{
    static $u = false;
    if ($u === false) {
        $u = isset($_SESSION['uid']) ? one('SELECT * FROM users WHERE id=? AND activo=1', [$_SESSION['uid']]) : null;
        if ($u) {
            $u['perms'] = [];
            foreach (all('SELECT * FROM user_permissions WHERE user_id=?', [$u['id']]) as $p) {
                $u['perms'][$p['modulo']] = ['ver' => (bool)$p['ver'], 'editar' => (bool)$p['editar']];
            }
        }
    }
    return $u;
}
function is_master(): bool { $u = current_user(); return $u && (int)$u['is_master'] === 1; }
function can(string $mod, string $lvl = 'ver'): bool
{
    $u = current_user();
    if (!$u) return false;
    if ((int)$u['is_master'] === 1) return true;
    if (isset(MODULOS_MASTER[$mod])) return false;
    $p = $u['perms'][$mod] ?? null;
    if (!$p) return false;
    return $lvl === 'editar' ? $p['editar'] : ($p['ver'] || $p['editar']);
}
function require_login(): array
{
    $u = current_user();
    if (!$u) { redirect('login'); }
    return $u;
}
function require_perm(string $mod, string $lvl = 'ver'): void
{
    require_login();
    if (!can($mod, $lvl)) {
        http_response_code(403);
        render('error', ['title' => 'Acceso denegado', 'msg' => 'Su usuario no tiene permiso para ' . ($lvl === 'editar' ? 'modificar' : 'ver') . ' este módulo. Solicite acceso al usuario Máster.']);
        exit;
    }
}
function allowed_company_ids(): ?array
{
    if (is_master()) return null; // todas
    $u = current_user();
    return array_map('intval', array_column(all('SELECT company_id FROM user_companies WHERE user_id=?', [$u['id']]), 'company_id'));
}
function companies_for_user(bool $onlyActive = false): array
{
    $ids = allowed_company_ids();
    $where = $onlyActive ? " WHERE estado='ACTIVA'" : '';
    $rows = all('SELECT * FROM companies' . $where . ' ORDER BY razon_social');
    if ($ids === null) return $rows;
    return array_values(array_filter($rows, fn($c) => in_array((int)$c['id'], $ids, true)));
}
function require_company(int $id): array
{
    $c = one('SELECT * FROM companies WHERE id=?', [$id]);
    $ids = allowed_company_ids();
    if (!$c || ($ids !== null && !in_array($id, $ids, true))) {
        http_response_code(403);
        render('error', ['title' => 'Sociedad no disponible', 'msg' => 'La sociedad no existe o su usuario no tiene acceso a ella.']);
        exit;
    }
    return $c;
}

// ---------- Bitácora ----------
function audit(string $accion, ?string $entidad = null, ?int $id = null, ?int $company = null, mixed $detalle = null): void
{
    $u = current_user();
    q('INSERT INTO audit_log (user_id,user_email,accion,entidad,entidad_id,company_id,detalle,ip,user_agent,creado_en) VALUES (?,?,?,?,?,?,?,?,?,?)', [
        $u['id'] ?? null, $u['email'] ?? ($_SESSION['audit_email'] ?? null), $accion, $entidad, $id, $company,
        $detalle === null ? null : (is_string($detalle) ? $detalle : json_encode($detalle, JSON_UNESCAPED_UNICODE)),
        $_SERVER['REMOTE_ADDR'] ?? 'cli', mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 250), now(),
    ]);
}

// ---------- Archivos ----------
/**
 * Guarda un archivo subido. $kinds: ['pdf'] | ['image'] | ['pdf','image'].
 * Devuelve id del documento o null si no se subió archivo. Lanza excepción si es inválido.
 */
function store_upload(string $field, string $ownerType, int $ownerId, string $tipo, array $kinds = ['pdf'], int $maxMb = 15): ?int
{
    if (empty($_FILES[$field]) || ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
    $f = $_FILES[$field];
    if ($f['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('Error al subir el archivo (código ' . $f['error'] . '). Verifique el tamaño máximo.');
    if ($f['size'] > $maxMb * 1024 * 1024) throw new RuntimeException('El archivo supera ' . $maxMb . ' MB.');
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    $ok = [];
    if (in_array('pdf', $kinds, true)) $ok['application/pdf'] = 'pdf';
    if (in_array('image', $kinds, true)) { $ok['image/png'] = 'png'; $ok['image/jpeg'] = 'jpg'; }
    if (!isset($ok[$mime])) throw new RuntimeException('Formato no permitido (' . $mime . '). Se acepta: ' . implode(', ', array_unique($ok)) . '.');
    $sub = date('Y/m');
    $dir = STORAGE . '/docs/' . $sub;
    if (!is_dir($dir) && !mkdir($dir, 0750, true)) throw new RuntimeException('No se pudo crear la carpeta de documentos.');
    $name = bin2hex(random_bytes(16)) . '.' . $ok[$mime];
    if (!move_uploaded_file($f['tmp_name'], $dir . '/' . $name)) {
        if (!rename($f['tmp_name'], $dir . '/' . $name)) throw new RuntimeException('No se pudo guardar el archivo.');
    }
    $u = current_user();
    q('INSERT INTO documents (owner_type,owner_id,tipo,nombre_original,ruta,mime,bytes,sha256,subido_por,subido_en) VALUES (?,?,?,?,?,?,?,?,?,?)', [
        $ownerType, $ownerId, $tipo, mb_substr(basename($f['name']), 0, 250), 'docs/' . $sub . '/' . $name, $mime, (int)$f['size'],
        hash_file('sha256', $dir . '/' . $name), $u['id'] ?? null, now(),
    ]);
    return (int)db()->lastInsertId();
}
function docs_of(string $type, int $id): array
{
    return all('SELECT * FROM documents WHERE owner_type=? AND owner_id=? AND vigente=1 ORDER BY subido_en DESC', [$type, $id]);
}
function doc_types_present(string $type, int $id): array
{
    return array_unique(array_column(docs_of($type, $id), 'tipo'));
}

// ---------- Feriados y respaldo ----------
function easter(int $y): DateTime
{
    $a = $y % 19; $b = intdiv($y, 100); $c = $y % 100; $d = intdiv($b, 4); $e = $b % 4;
    $f = intdiv($b + 8, 25); $g = intdiv($b - $f + 1, 3); $h = (19 * $a + $b - $d - $g + 15) % 30;
    $i = intdiv($c, 4); $k = $c % 4; $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7; $m = intdiv($a + 11 * $h + 22 * $l, 451);
    $month = intdiv($h + $l - 7 * $m + 114, 31); $day = (($h + $l - 7 * $m + 114) % 31) + 1;
    return new DateTime(sprintf('%04d-%02d-%02d', $y, $month, $day));
}
/** Ley 19.668: feriado que cae martes/miércoles → lunes anterior; jueves/viernes → lunes siguiente. */
function ley19668(string $date): string
{
    $d = new DateTime($date);
    $w = (int)$d->format('N');
    if ($w === 2) $d->modify('-1 day');
    elseif ($w === 3) $d->modify('-2 day');
    elseif ($w === 4) $d->modify('+4 day');
    elseif ($w === 5) $d->modify('+3 day');
    return $d->format('Y-m-d');
}
/** Feriados nacionales de Chile (legales). Revise cada año: pueden agregarse feriados por ley o elecciones. */
function chile_holidays(int $y): array
{
    $h = [];
    $h["$y-01-01"] = 'Año Nuevo';
    $e = easter($y);
    $h[(clone $e)->modify('-2 day')->format('Y-m-d')] = 'Viernes Santo';
    $h[(clone $e)->modify('-1 day')->format('Y-m-d')] = 'Sábado Santo';
    $h["$y-05-01"] = 'Día Nacional del Trabajo';
    $h["$y-05-21"] = 'Día de las Glorias Navales';
    $solsticio = [2026 => 21, 2027 => 21, 2028 => 20, 2029 => 20, 2030 => 21];
    $h[sprintf('%d-06-%02d', $y, $solsticio[$y] ?? 21)] = 'Día Nacional de los Pueblos Indígenas';
    $h[ley19668("$y-06-29")] = 'San Pedro y San Pablo';
    $h["$y-07-16"] = 'Día de la Virgen del Carmen';
    $h["$y-08-15"] = 'Asunción de la Virgen';
    if ((int)date('N', strtotime("$y-09-18")) === 2) $h["$y-09-17"] = 'Feriado adicional Fiestas Patrias';
    $h["$y-09-18"] = 'Independencia Nacional';
    $h["$y-09-19"] = 'Día de las Glorias del Ejército';
    if ((int)date('N', strtotime("$y-09-19")) === 4) $h["$y-09-20"] = 'Feriado adicional Fiestas Patrias';
    $h[ley19668("$y-10-12")] = 'Encuentro de Dos Mundos';
    $w = (int)date('N', strtotime("$y-10-31"));
    $ev = $w === 2 ? date('Y-m-d', strtotime("$y-10-27")) : ($w === 3 ? date('Y-m-d', strtotime("$y-11-02")) : "$y-10-31");
    $h[$ev] = 'Día de las Iglesias Evangélicas y Protestantes';
    $h["$y-11-01"] = 'Día de Todos los Santos';
    $h["$y-12-08"] = 'Inmaculada Concepción';
    $h["$y-12-25"] = 'Navidad';
    ksort($h);
    return $h;
}
function is_business_day(string $date): bool
{
    $w = (int)date('N', strtotime($date));
    if ($w >= 6) return false;
    return !val('SELECT 1 FROM holidays WHERE fecha=?', [$date]);
}
function backup_pending_today(): bool
{
    if (!is_business_day(today())) return false;
    return !val("SELECT 1 FROM backups WHERE tipo='DESCARGA' AND DATE(creado_en)=?", [today()]);
}

// ---------- Vistas ----------
function render(string $view, array $vars = []): void
{
    extract($vars);
    $user = current_user();
    ob_start();
    require __DIR__ . '/views/' . $view . '.php';
    $content = ob_get_clean();
    if (!empty($bare)) { echo $content; return; }
    require __DIR__ . '/views/layout.php';
}
function help_button(string $modulo): string
{
    return '<a class="btn-help" href="' . e(url('manual', ['m' => $modulo])) . '" title="Manual de usuario y preguntas frecuentes">? Manual</a>';
}
function badge(string $text, string $kind = 'neutral'): string { return '<span class="badge b-' . $kind . '">' . e($text) . '</span>'; }
function sel(mixed $a, mixed $b): string { return (string)$a === (string)$b ? ' selected' : ''; }
function chk(mixed $v): string { return $v ? ' checked' : ''; }

function send_download(string $tmp, string $name, string $mime): never
{
    header('Content-Type: ' . $mime);
    header('Content-Disposition: attachment; filename="' . $name . '"');
    header('Content-Length: ' . filesize($tmp));
    header('Cache-Control: private, no-store');
    readfile($tmp);
    @unlink($tmp);
    exit;
}

function serie_label(string $n): string { return trim($n) === 'Única' ? 'Serie Única' : $n; }
