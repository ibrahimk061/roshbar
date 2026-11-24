<?php
/** @var array $categories @var ?string $active @var array $products */

partial('banner', [
    'eyebrow' => t('menu_eyebrow'),
    'heading' => t('menu_h'),
    'lead'    => t('menu_p'),
    'crumbs'  => [[t('nav_home'), ''], [t('nav_menu'), null]],
]);
?>
<section class="section pt-0">
  <div class="container">
    <nav class="filters" aria-label="<?= e(t('menu_eyebrow')) ?>">
      <a class="filter" href="<?= url('menu') ?>" <?= $active ? '' : 'aria-current="page"' ?>><?= e(t('all')) ?></a>
      <?php foreach ($categories as $c): ?>
        <a class="filter" href="<?= url('menu?c=' . urlencode($c['slug'])) ?>" <?= $active === $c['slug'] ? 'aria-current="page"' : '' ?>><?= e(tr($c, 'name')) ?></a>
      <?php endforeach; ?>
    </nav>

    <?php if ($products): ?>
      <div class="card-grid">
        <?php foreach ($products as $p) { partial('product-card', ['p' => $p]); } ?>
      </div>
    <?php else: ?>
      <p class="empty-state"><?= e(t('menu_empty')) ?></p>
    <?php endif; ?>
  </div>
</section>
