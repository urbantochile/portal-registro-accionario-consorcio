<?php
declare(strict_types=1);

function ctl_actualizar_index(): void
{
    require_login();
    if (!is_master()) redirect('dashboard');
    $pending = schema_pending(db());
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        if (!isset($_POST['respaldo_ok'])) { flash('error', 'Confirme que descargó un respaldo antes de actualizar.'); redirect('actualizar'); }
        try {
            $done = schema_migrate(db());
            audit('BASE_ACTUALIZADA', 'settings', null, null, ['migraciones' => $done, 'version_app' => APP_VERSION]);
            flash('ok', 'Base de datos actualizada: ' . implode(' · ', $done) . '.');
        } catch (Throwable $e) {
            flash('error', 'No se pudo completar la actualización: ' . $e->getMessage() . '. Restaure el respaldo si fuera necesario y contacte a soporte.');
            redirect('actualizar');
        }
        redirect('dashboard');
    }
    if (!$pending) redirect('dashboard');
    render('upgrade', ['pending' => $pending, 'title' => 'Actualización de la base de datos']);
}
