<div class="page-head"><h1>Usuarios y permisos</h1><div><?= help_button('usuarios') ?> <a class="btn primary" href="<?= e(url('usuarios.form')) ?>">+ Nuevo usuario</a></div></div>
<div class="card">
<table class="tbl"><thead><tr><th>Usuario</th><th>Rol</th><th>Módulos</th><th>Sociedades</th><th>Último acceso</th><th>Estado</th><th></th></tr></thead><tbody>
<?php foreach ($rows as $u): ?>
<tr><td><strong><?= e($u['nombre']) ?></strong><br><small class="muted"><?= e($u['email']) ?></small></td>
<td><?= $u['is_master'] ? badge('Máster', 'res') : badge('Usuario', 'neutral') ?></td>
<td class="small"><?php if ($u['is_master']): ?>Todos<?php else: foreach ($u['_perms'] as $p): ?><div><?= e(MODULOS[$p['modulo']] ?? $p['modulo']) ?> · <?= $p['editar'] ? '<strong>ver y editar</strong>' : 'solo ver' ?></div><?php endforeach; endif; ?></td>
<td class="small"><?= $u['is_master'] ? 'Todas' : e(implode(', ', array_column($u['_comp'], 'razon_social'))) ?></td>
<td class="small nowrap"><?= e(fdatetime($u['ultimo_acceso'])) ?></td>
<td><?= $u['activo'] ? badge('Activo', 'ok') : badge('Inactivo', 'neutral') ?><?= $u['bloqueado_hasta'] && strtotime($u['bloqueado_hasta']) > time() ? ' ' . badge('Bloqueado', 'error') : '' ?></td>
<td><?php if (!$u['is_master']): ?><a class="btn sm" href="<?= e(url('usuarios.form', ['id' => $u['id']])) ?>">Editar</a><?php endif; ?></td></tr>
<?php endforeach; ?></tbody></table></div>
