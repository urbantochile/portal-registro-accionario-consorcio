<?php
declare(strict_types=1);

function audit_query(): array
{
    $w = ['1=1']; $p = [];
    if ($u = getp('u')) { $w[] = 'user_id=?'; $p[] = (int)$u; }
    if ($a = getp('a')) { $w[] = 'accion=?'; $p[] = $a; }
    if ($d = ndate(getp('desde'))) { $w[] = 'creado_en>=?'; $p[] = $d . ' 00:00:00'; }
    if ($d = ndate(getp('hasta'))) { $w[] = 'creado_en<=?'; $p[] = $d . ' 23:59:59'; }
    if ($s = getp('s')) { $w[] = 'detalle LIKE ?'; $p[] = '%' . $s . '%'; }
    return [implode(' AND ', $w), $p];
}

function ctl_bitacora_index(): void
{
    require_perm('bitacora');
    [$w, $p] = audit_query();
    $page = max(1, (int)getp('p', 1));
    $total = (int)val("SELECT COUNT(*) FROM audit_log WHERE $w", $p);
    $rows = all("SELECT a.*, c.razon_social FROM audit_log a LEFT JOIN companies c ON c.id=a.company_id WHERE $w ORDER BY a.id DESC LIMIT 50 OFFSET " . (($page - 1) * 50), $p);
    render('audit', ['rows' => $rows, 'total' => $total, 'page' => $page, 'users' => all('SELECT id, nombre, email FROM users ORDER BY nombre'),
        'acciones' => array_column(all('SELECT DISTINCT accion FROM audit_log ORDER BY accion'), 'accion'), 'title' => 'Bitácora']);
}

function ctl_bitacora_excel(): void
{
    require_perm('bitacora');
    [$w, $p] = audit_query();
    $rows = [['Fecha y hora', 'Usuario', 'Acción', 'Entidad', 'ID', 'Sociedad', 'Detalle', 'IP']];
    foreach (all("SELECT a.*, c.razon_social FROM audit_log a LEFT JOIN companies c ON c.id=a.company_id WHERE $w ORDER BY a.id DESC LIMIT 20000", $p) as $r) {
        $rows[] = [fdatetime($r['creado_en']), $r['user_email'], $r['accion'], $r['entidad'], $r['entidad_id'], $r['razon_social'], $r['detalle'], $r['ip']];
    }
    $x = new SimpleXlsx();
    $x->addSheet('Bitácora', $rows, [17, 26, 26, 12, 7, 30, 80, 15], 'Bitácora de actividad — ' . date('d-m-Y H:i'));
    $tmp = tempnam(sys_get_temp_dir(), 'xl');
    $x->save($tmp);
    audit('EXCEL_BITACORA');
    send_download($tmp, 'bitacora-' . date('Ymd-His') . '.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
}
