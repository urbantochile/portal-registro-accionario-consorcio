<div class="page-head"><h1>Manual de usuario <small class="muted">· <?= $master ? 'versión Máster (completa)' : 'versión Usuario' ?></small></h1><button class="btn" onclick="window.print()">Imprimir</button></div>
<div class="manual">
  <nav class="manual-nav card">
    <strong>Contenido</strong>
    <a href="<?= e(url('manual')) ?>" class="<?= $m === '' ? 'on' : '' ?>">Manual completo</a>
    <?php foreach ($chapters as $k => $ch): ?><a href="<?= e(url('manual', ['m' => $k])) ?>" class="<?= $m === $k ? 'on' : '' ?>"><?= e($ch['titulo']) ?><?= !empty($ch['master']) ? ' <span class="badge b-res">Máster</span>' : '' ?></a><?php endforeach; ?>
  </nav>
  <div class="manual-body">
  <?php foreach ($chapters as $k => $ch): if ($m !== '' && $m !== $k) continue; ?>
    <article class="card manual-ch" id="<?= e($k) ?>">
      <h2><?= e($ch['titulo']) ?></h2>
      <p class="lead"><?= e($ch['intro']) ?></p>
      <?php foreach ($ch['pasos'] as [$t, $items]): ?>
        <h3><?= e($t) ?></h3><ul class="steps"><?php foreach ($items as $it): ?><li><?= e($it) ?></li><?php endforeach; ?></ul>
      <?php endforeach; ?>
      <?php if ($master && !empty($ch['solo_master'])): ?><div class="master-note"><strong>Solo Máster</strong><?php foreach ($ch['solo_master'] as $t): ?><p><?= e($t) ?></p><?php endforeach; ?></div><?php endif; ?>
      <?php if (!empty($ch['faq'])): ?>
        <h3>Preguntas frecuentes</h3>
        <div class="faq"><?php foreach ($ch['faq'] as [$q, $a]): ?><details><summary><?= e($q) ?></summary><p><?= e($a) ?></p></details><?php endforeach; ?></div>
      <?php endif; ?>
    </article>
  <?php endforeach; ?>
  </div>
</div>
