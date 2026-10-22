<?php
declare(strict_types=1);

function manual_visible(bool $master): array
{
    $all = require __DIR__ . '/../manual_content.php';
    return array_filter($all, function ($ch) use ($master) {
        if ($master) return true;
        if (!empty($ch['master'])) return false;
        return $ch['perm'] === null || can($ch['perm']);
    });
}

function ctl_manual_index(): void
{
    require_login();
    $chapters = manual_visible(is_master());
    $m = getp('m');
    if ($m && !isset($chapters[$m])) $m = '';
    render('manual', ['chapters' => $chapters, 'm' => $m, 'master' => is_master(), 'title' => 'Manual de usuario']);
}
