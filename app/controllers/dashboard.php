<?php
declare(strict_types=1);

function ctl_dashboard_index(): void
{
    $companies = companies_for_user();
    $stats = ['res' => 0, 'trad' => 0, 'accionistas' => 0, 'socios' => 0];
    $alerts = [];
    $cards = [];
    foreach ($companies as $c) {
        $c['regimen'] === 'RES' ? $stats['res']++ : $stats['trad']++;
        $sh = current_shareholders((int)$c['id']);
        $socios = (int)val('SELECT COUNT(*) FROM members WHERE company_id=?', [$c['id']]);
        $stats['accionistas'] += count($sh['rows']);
        $stats['socios'] += $socios;
        $miss = company_missing($c);
        if ($miss['base']) $alerts[] = ['error', $c['razon_social'] . ': falta respaldo documental (' . implode('; ', $miss['base']) . ').', url('sociedades.ver', ['id' => $c['id']])];
        if ($miss['cumplimiento']) $alerts[] = ['warn', $c['razon_social'] . ': cumplimiento Ley 20.393 / 21.595 pendiente (' . count($miss['cumplimiento']) . ' ítems).', url('sociedades.ver', ['id' => $c['id']])];
        $cards[] = ['c' => $c, 'accionistas' => count($sh['rows']), 'acciones' => $sh['total'], 'socios' => $socios, 'ok' => !$miss['base'] && !$miss['cumplimiento']];
    }
    foreach ($companies as $c) {
        try {
            if (can('accionistas') && $c['regimen'] === 'RES' && ($n = (int)val('SELECT COUNT(*) FROM movements WHERE company_id=? AND res_pendiente=1 AND anulado=0', [$c['id']])))
                $alerts[] = ['warn', $c['razon_social'] . ': ' . $n . ' compra(s) con «Registro RES pendiente» (falta CVE).', url('accionistas', ['c' => $c['id'], 'tab' => 'movimientos'])];
            if (can('certificados')) {
                $sf = (int)val("SELECT COUNT(*) FROM certificates WHERE company_id=? AND estado='SIN_FIRMA'", [$c['id']]);
                if ($sf) $alerts[] = ['info', $c['razon_social'] . ': ' . $sf . ' certificado(s) pendiente(s) de firma en FirmaYa.', url('certificados', ['c' => $c['id'], 'tab' => 'emitidos', 'estado' => 'SIN_FIRMA'])];
                $pos = 0; $vig = 0;
                foreach (balances((int)$c['id']) as $mid => $bs) foreach ($bs as $sid => $q) if ($q > 0) { $pos++; if (val("SELECT 1 FROM certificates WHERE member_id=? AND series_id=? AND estado IN ('SIN_FIRMA','FIRMADO')", [$mid, $sid])) $vig++; }
                if ($pos > $vig) $alerts[] = ['info', $c['razon_social'] . ': ' . ($pos - $vig) . ' posición(es) de accionista sin certificado vigente (por emitir).', url('certificados', ['c' => $c['id']])];
            }
        } catch (Throwable $e) {}
    }
    $ddPend = can('socios') ? (int)val("SELECT COUNT(*) FROM persons WHERE dd_estado='PENDIENTE'") : 0;
    if ($ddPend) $alerts[] = ['info', $ddPend . ' persona(s) con debida diligencia pendiente.', url('personas', ['dd' => 'PENDIENTE'])];
    $recent = can('bitacora') ? all('SELECT * FROM audit_log ORDER BY id DESC LIMIT 8') : [];
    $lastBackup = is_master() ? one("SELECT * FROM backups WHERE tipo='DESCARGA' ORDER BY id DESC LIMIT 1") : null;
    render('dashboard', compact('companies', 'stats', 'alerts', 'cards', 'recent', 'lastBackup', 'ddPend') + ['title' => 'Panel']);
}
