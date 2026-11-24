<?php /** @var array $p product row */ ?>
<article class="card" style="--c: <?= e($p['color']) ?>">
  <a class="card-link" href="<?= url('menu/' . $p['slug']) ?>">
    <div class="card-media">
      <?php if ($p['is_seasonal']): ?><span class="card-badge"><?= e(t('seasonal_badge')) ?></span><?php endif; ?>
      <?php partial('product-visual', ['p' => $p, 'class' => 'card-glass']); ?>
    </div>
    <div class="card-body">
      <h3><?= e(tr($p, 'name')) ?></h3>
      <p><?= e(tr($p, 'ingredients')) ?></p>
      <div class="card-foot">
        <span class="price"><small><?= e(t('from')) ?></small> <?= money($p['from_price']) ?></span>
        <span class="card-go" aria-hidden="true"><svg><use href="#i-arrow"/></svg></span>
      </div>
    </div>
  </a>
</article>
