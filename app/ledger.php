<?php
declare(strict_types=1);

/** Movimientos vigentes de una sociedad en orden cronológico. */
function movements_of(int $companyId, bool $includeVoided = false): array
{
    return all('SELECT m.*, s.nombre AS serie,
        pc.id AS comprador_person_id, pv.id AS vendedor_person_id,
        mc.numero AS comprador_numero, mv.numero AS vendedor_numero
        FROM movements m
        JOIN series s ON s.id=m.series_id
        JOIN members mc ON mc.id=m.comprador_member_id
        JOIN persons pc ON pc.id=mc.person_id
        LEFT JOIN members mv ON mv.id=m.vendedor_member_id
        LEFT JOIN persons pv ON pv.id=mv.person_id
        WHERE m.company_id=?' . ($includeVoided ? '' : ' AND m.anulado=0') . '
        ORDER BY m.fecha, m.numero', [$companyId]);
}

/** Saldos: [member_id][series_id] => acciones */
function balances(int $companyId): array
{
    $b = [];
    foreach (all('SELECT comprador_member_id AS mid, series_id, SUM(cantidad) q FROM movements WHERE company_id=? AND anulado=0 GROUP BY comprador_member_id, series_id', [$companyId]) as $r) {
        $b[(int)$r['mid']][(int)$r['series_id']] = ($b[(int)$r['mid']][(int)$r['series_id']] ?? 0) + (int)$r['q'];
    }
    foreach (all('SELECT vendedor_member_id AS mid, series_id, SUM(cantidad) q FROM movements WHERE company_id=? AND anulado=0 AND vendedor_member_id IS NOT NULL GROUP BY vendedor_member_id, series_id', [$companyId]) as $r) {
        $b[(int)$r['mid']][(int)$r['series_id']] = ($b[(int)$r['mid']][(int)$r['series_id']] ?? 0) - (int)$r['q'];
    }
    return $b;
}

function member_total(int $companyId, int $memberId): int
{
    $b = balances($companyId);
    return array_sum($b[$memberId] ?? []);
}

/** Acciones emitidas (suscritas) por serie. */
function issued_by_series(int $companyId, ?int $excludeMovement = null): array
{
    $r = [];
    foreach (all("SELECT series_id, SUM(cantidad) q FROM movements WHERE company_id=? AND anulado=0 AND tipo='EMISION'" . ($excludeMovement ? ' AND id<>' . (int)$excludeMovement : '') . ' GROUP BY series_id', [$companyId]) as $x) {
        $r[(int)$x['series_id']] = (int)$x['q'];
    }
    return $r;
}

/**
 * Reproduce la cadena de movimientos (más uno hipotético, menos uno anulado)
 * y verifica que ningún accionista quede con saldo negativo en ningún momento.
 * Devuelve null si es válida o un mensaje de error.
 */
function validate_chain(int $companyId, ?array $extra = null, ?int $withoutId = null): ?string
{
    $movs = all('SELECT id, fecha, numero, series_id, vendedor_member_id, comprador_member_id, cantidad FROM movements WHERE company_id=? AND anulado=0', [$companyId]);
    if ($withoutId) $movs = array_values(array_filter($movs, fn($m) => (int)$m['id'] !== $withoutId));
    if ($extra) $movs[] = $extra;
    usort($movs, fn($a, $b) => [$a['fecha'], (int)$a['numero']] <=> [$b['fecha'], (int)$b['numero']]);
    $bal = [];
    foreach ($movs as $m) {
        $s = (int)$m['series_id'];
        if ($m['vendedor_member_id']) {
            $v = (int)$m['vendedor_member_id'];
            $bal[$v][$s] = ($bal[$v][$s] ?? 0) - (int)$m['cantidad'];
            if ($bal[$v][$s] < 0) {
                $p = one('SELECT p.* FROM members mm JOIN persons p ON p.id=mm.person_id WHERE mm.id=?', [$v]);
                return 'Con esta operación, ' . ($p ? person_name($p) : 'el vendedor') . ' quedaría con saldo negativo al ' . fdate($m['fecha']) . ' (transferencia N° ' . $m['numero'] . '). Revise fechas y cantidades.';
            }
        }
        $c = (int)$m['comprador_member_id'];
        $bal[$c][$s] = ($bal[$c][$s] ?? 0) + (int)$m['cantidad'];
    }
    return null;
}

/** Registra la anotación automática en el registro de socios si un accionista quedó en cero. */
function check_full_sale(int $companyId, int $memberId, int $movementId, string $fecha): void
{
    if (member_total($companyId, $memberId) !== 0) return;
    $c = one('SELECT razon_social FROM companies WHERE id=?', [$companyId]);
    $n = val('SELECT numero FROM movements WHERE id=?', [$movementId]);
    $txt = 'Enajenó la totalidad de sus acciones de la sociedad ' . $c['razon_social'] . ' con fecha ' . fdate($fecha) . ' (transferencia N° ' . $n . '). Queda eliminado del registro de accionistas vigentes.';
    q('INSERT INTO member_notes (member_id,fecha,texto,automatica,movement_id,creado_por,creado_en) VALUES (?,?,?,?,?,?,?)', [$memberId, $fecha, $txt, 1, $movementId, current_user()['id'] ?? null, now()]);
}

/** Accionistas vigentes (saldo > 0) con detalle por serie. */
function current_shareholders(int $companyId): array
{
    $bal = balances($companyId);
    $series = all('SELECT * FROM series WHERE company_id=? ORDER BY id', [$companyId]);
    $total = 0;
    foreach ($bal as $mid => $bySeries) $total += max(0, array_sum($bySeries));
    $votesTotal = 0;
    $votes = [];
    foreach ($series as $s) $votes[(int)$s['id']] = (float)$s['votos_por_accion'];
    foreach ($bal as $mid => $bySeries) foreach ($bySeries as $sid => $q) $votesTotal += $q * ($votes[$sid] ?? 1);
    $rows = [];
    foreach ($bal as $mid => $bySeries) {
        $sum = array_sum($bySeries);
        if ($sum <= 0) continue;
        $m = one('SELECT p.*, mm.id AS member_id, mm.numero AS socio_numero, mm.fecha_ingreso FROM members mm JOIN persons p ON p.id=mm.person_id WHERE mm.id=?', [$mid]);
        $v = 0;
        foreach ($bySeries as $sid => $q) $v += $q * ($votes[$sid] ?? 1);
        $rows[] = ['member' => $m, 'series' => $bySeries, 'total' => $sum, 'pct' => $total ? $sum * 100 / $total : 0, 'votos' => $v, 'pct_votos' => $votesTotal ? $v * 100 / $votesTotal : 0];
    }
    usort($rows, fn($a, $b) => $b['total'] <=> $a['total']);
    return ['rows' => $rows, 'series' => $series, 'total' => $total];
}

/** Hoja del accionista: filas con saldo acumulado (formato del libro físico). */
function shareholder_sheet(int $companyId, int $memberId): array
{
    $rows = [];
    $saldo = 0;
    foreach (movements_of($companyId) as $m) {
        $isBuyer = (int)$m['comprador_member_id'] === $memberId;
        $isSeller = (int)$m['vendedor_member_id'] === $memberId;
        if (!$isBuyer && !$isSeller) continue;
        $contra = '';
        if ($isBuyer) {
            if ($m['vendedor_member_id']) {
                $p = one('SELECT * FROM persons WHERE id=?', [$m['vendedor_person_id']]);
                $contra = person_name($p);
            } else {
                $contra = 'La sociedad (emisión)';
            }
            $saldo += (int)$m['cantidad'];
        } else {
            $p = one('SELECT * FROM persons WHERE id=?', [$m['comprador_person_id']]);
            $contra = person_name($p);
            $saldo -= (int)$m['cantidad'];
        }
        $rows[] = [
            'fecha' => $m['fecha'], 'numero' => $m['numero'], 'serie' => $m['serie'], 'tipo' => $m['tipo'],
            'titulo_inutilizado' => $m['titulo_inutilizado'],
            'comprado_a' => $isBuyer ? $contra : '', 'vendido_a' => $isSeller ? $contra : '',
            'titulo_nuevo_comprador' => $m['titulo_nuevo_comprador'], 'titulo_nuevo_vendedor' => $m['titulo_nuevo_vendedor'],
            'compras' => $isBuyer ? (int)$m['cantidad'] : 0, 'ventas' => $isSeller ? (int)$m['cantidad'] : 0,
            'saldo' => $saldo, 'observaciones' => $m['observaciones'],
            'titulo_emitido' => $isBuyer ? $m['titulo_nuevo_comprador'] : $m['titulo_nuevo_vendedor'],
            'cert' => $isBuyer ? $m['cert_codigo'] : $m['cert_vendedor_codigo'],
        ];
    }
    return $rows;
}

/** Validación del código de certificado según régimen. */
function validate_cert_code(string $regimen, string $code): ?string
{
    $code = trim($code);
    if ($code === '') return 'Debe ingresar el código del certificado.';
    if ($regimen === 'RES') {
        if (!preg_match('/^[A-Za-z0-9]{12}$/', $code)) return 'Para sociedades RES el código debe ser el CVE del Registro de Empresas y Sociedades: 12 caracteres alfanuméricos (ej.: CRpSnTiGLXyA).';
    } elseif (mb_strlen($code) < 4 || mb_strlen($code) > 80) {
        return 'El código del certificado debe tener entre 4 y 80 caracteres.';
    }
    return null;
}

/** Revisión documental y de cumplimiento de una sociedad. */
function company_missing(array $c): array
{
    $present = doc_types_present('company', (int)$c['id']);
    $base = [];
    if (!in_array('ESTATUTO', $present, true)) $base[] = 'Estatuto social';
    if (!in_array('CERT_PODERES', $present, true) && !in_array('ACTA_DIRECTORIO', $present, true)) $base[] = 'Certificado de poderes o acta de directorio con poderes';
    $comp = [];
    if (!$c['mpd_adoptado']) $comp[] = 'Modelo de Prevención de Delitos adoptado';
    foreach (['mpd_fecha_aprobacion' => 'Fecha de aprobación del MPD', 'enc_nombre' => 'Encargado de Prevención', 'enc_rut' => 'RUT del Encargado de Prevención',
                 'enc_fecha_designacion' => 'Fecha de designación del Encargado', 'enc_vigencia_hasta' => 'Vigencia del Encargado', 'canal_denuncias' => 'Canal de denuncias',
                 'matriz_fecha' => 'Fecha de la matriz de riesgos', 'codigo_etica_fecha' => 'Fecha del código de ética', 'capacitacion_fecha' => 'Fecha de la última capacitación'] as $k => $label) {
        if (empty($c[$k])) $comp[] = $label;
    }
    if (!empty($c['enc_vigencia_hasta']) && $c['enc_vigencia_hasta'] < today()) $comp[] = 'Designación del Encargado de Prevención vencida';
    foreach (DOCS_COMPLIANCE as $t) {
        if (!in_array($t, $present, true)) $comp[] = 'Documento: ' . DOCS_SOCIEDAD[$t][0];
    }
    return ['base' => $base, 'cumplimiento' => $comp];
}

/** Da de baja (reemplaza) los certificados vigentes de un accionista en una serie. */
function cert_invalidate(int $memberId, int $seriesId, string $motivo): int
{
    try {
        $st = q("UPDATE certificates SET estado='REEMPLAZADO', baja_motivo=?, baja_en=?, baja_por=? WHERE member_id=? AND series_id=? AND estado IN ('SIN_FIRMA','FIRMADO')",
            [$motivo, now(), current_user()['id'] ?? null, $memberId, $seriesId]);
    } catch (Throwable $e) { return 0; }
    $n = $st->rowCount();
    if ($n) audit('CERTIFICADO_REEMPLAZADO', 'member', $memberId, (int)val('SELECT company_id FROM members WHERE id=?', [$memberId]), ['serie' => $seriesId, 'motivo' => $motivo, 'certificados' => $n]);
    return $n;
}

/** Gravámenes vigentes: [member_id][series_id] => [filas] */
function liens_vigentes(int $companyId): array
{
    $r = [];
    try {
        foreach (all("SELECT * FROM gravamenes WHERE company_id=? AND estado='VIGENTE' ORDER BY fecha_anotacion", [$companyId]) as $g) $r[(int)$g['member_id']][(int)$g['series_id']][] = $g;
    } catch (Throwable $e) {}
    return $r;
}
