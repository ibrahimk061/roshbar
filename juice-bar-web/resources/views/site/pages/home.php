<?php

/** @var array $featured @var array $seasonal @var array $categories @var int $month */
$fruitIcon = ['orange' => 'i-orange', 'lemon' => 'i-lemon', 'kiwi' => 'i-kiwi', 'mint' => 'i-mint',
    'strawberry' => 'i-strawberry', 'watermelon' => 'i-watermelon', 'mango' => 'i-mango', 'berry' => 'i-berry'];
?>

<!-- ============ HERO ============ -->
<section class="hero" aria-labelledby="hero-h">
  <div class="container hero-grid">
    <div class="hero-copy">
      <p class="eyebrow"><?= e(t('hero_eyebrow')) ?></p>
      <h1 id="hero-h"><?= accent(t('hero_h1')) ?></h1>
      <p class="hero-lead"><?= e(t('hero_p')) ?></p>
      <div class="btn-row">
        <a class="btn btn-primary" href="<?= url('menu') ?>"><?= e(t('hero_cta')) ?><svg class="ico" aria-hidden="true"><use href="#i-arrow"/></svg></a>
        <a class="btn btn-outline" href="#visit"><?= e(t('hero_cta2')) ?><svg class="ico" aria-hidden="true"><use href="#i-arrow"/></svg></a>
      </div>
      <ul class="trust">
        <?php foreach (['trust_1', 'trust_2', 'trust_3'] as $k): ?>
          <li><svg class="ico" aria-hidden="true"><use href="#i-check"/></svg><?= e(t($k)) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>

    <div class="hero-art" aria-hidden="true">
      <svg class="hero-splash"><use href="#i-splash"/></svg>
      <?php partial('glass', ['color' => '#D6264F', 'garnish' => 'strawberry', 'class' => 'hero-glass', 'fill' => .86]); ?>
      <svg class="fruit f-orange"><use href="#i-orange"/></svg>
      <svg class="fruit f-orange2"><use href="#i-orange"/></svg>
      <svg class="fruit f-mango"><use href="#i-mango"/></svg>
      <svg class="fruit f-kiwi"><use href="#i-kiwi"/></svg>
      <svg class="fruit f-straw"><use href="#i-strawberry"/></svg>
      <svg class="fruit f-berry1"><use href="#i-berry"/></svg>
      <svg class="fruit f-berry2"><use href="#i-berry"/></svg>
      <svg class="fruit f-berry3"><use href="#i-berry"/></svg>
      <svg class="fruit f-lemon"><use href="#i-lemon"/></svg>
      <svg class="leaf l1"><use href="#i-mint"/></svg>
      <svg class="leaf l2"><use href="#i-mint"/></svg>
      <svg class="leaf l3"><use href="#i-mint"/></svg>
    </div>
  </div>
  <svg class="wave" viewBox="0 0 1440 120" preserveAspectRatio="none" aria-hidden="true"><path d="M0 70 C240 120 480 120 720 80 C960 40 1200 20 1440 60 V120 H0 Z"/></svg>
</section>

<!-- ============ BEST JUICES ============ -->
<section class="section" aria-labelledby="best-h">
  <svg class="deco deco-berries" aria-hidden="true"><use href="#i-berry"/></svg>
  <div class="container">
    <header class="section-head center">
      <p class="eyebrow"><?= e(t('best_eyebrow')) ?></p>
      <h2 id="best-h"><?= accent(t('best_h')) ?></h2>
      <p><?= e(t('best_p')) ?></p>
    </header>
    <div class="card-grid">
      <?php foreach ($featured as $p) { partial('product-card', ['p' => $p]); } ?>
    </div>
    <div class="center mt-lg">
      <a class="btn btn-primary" href="<?= url('menu') ?>"><?= e(t('view_menu')) ?><svg class="ico" aria-hidden="true"><use href="#i-arrow"/></svg></a>
    </div>
  </div>
</section>

<!-- ============ WHY US ============ -->
<section class="section section-tint" aria-labelledby="why-h">
  <div class="container why-grid">
    <div class="why-art" aria-hidden="true">
      <div class="why-circle"></div>
      <?php partial('glass', ['color' => '#FFB000', 'garnish' => 'mango', 'class' => 'why-g1']); ?>
      <?php partial('glass', ['color' => '#4CB963', 'garnish' => 'kiwi', 'class' => 'why-g2']); ?>
      <?php partial('glass', ['color' => '#FF4D6D', 'garnish' => 'watermelon', 'class' => 'why-g3']); ?>
      <svg class="fruit w-orange"><use href="#i-orange"/></svg>
      <svg class="leaf w-leaf"><use href="#i-mint"/></svg>
    </div>
    <div>
      <header class="section-head">
        <p class="eyebrow"><?= e(t('why_eyebrow')) ?></p>
        <h2 id="why-h"><?= accent(t('why_h')) ?></h2>
      </header>
      <div class="features">
        <?php foreach ([['i-leaf', 1], ['i-bolt', 2], ['i-pin', 3], ['i-drop', 4]] as [$icon, $n]): ?>
          <div class="feature">
            <span class="feature-ico"><svg aria-hidden="true"><use href="#<?= $icon ?>"/></svg></span>
            <div>
              <h3><?= e(t("why_{$n}_h")) ?></h3>
              <p><?= e(t("why_{$n}_p")) ?></p>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>

<!-- ============ SEASONAL ============ -->
<section class="section season" aria-labelledby="season-h">
  <svg class="wave wave-top" viewBox="0 0 1440 120" preserveAspectRatio="none" aria-hidden="true"><path d="M0 50 C240 0 480 0 720 40 C960 80 1200 100 1440 60 V0 H0 Z"/></svg>
  <div class="container">
    <header class="section-head center">
      <p class="eyebrow eyebrow-light"><?= e(t('season_eyebrow')) ?></p>
      <h2 id="season-h"><?= accent(t('season_h', ['month' => month_name($month)])) ?></h2>
      <p><?= e(t('season_p')) ?></p>
    </header>

    <?php if ($seasonal): ?>
      <div class="season-list">
        <?php foreach ($seasonal as $p): $sizes = $p['sizes']; ?>
          <article class="season-item">
            <div class="season-media" style="--c: <?= e($p['color']) ?>">
              <?php partial('product-visual', ['p' => $p]); ?>
            </div>
            <div class="season-body">
              <h3><?= e(tr($p, 'name')) ?></h3>
              <p><?= e(tr($p, 'description')) ?></p>
              <dl class="season-facts">
                <div><dt><?= e(t('calories')) ?></dt><dd><?= num($p['calories']) ?></dd></div>
                <div><dt><?= e(t('sizes')) ?></dt><dd><?= e(num(implode(' / ', array_column($sizes, 'label')))) ?></dd></div>
                <div><dt><?= e(t('from')) ?></dt><dd><?= money($p['from_price']) ?></dd></div>
              </dl>
              <a class="btn btn-light btn-sm" href="<?= url('menu/' . $p['slug']) ?>"><?= e(t('see_details')) ?><svg class="ico" aria-hidden="true"><use href="#i-arrow"/></svg></a>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <p class="center"><?= e(t('season_none')) ?></p>
    <?php endif; ?>
  </div>
  <svg class="wave" viewBox="0 0 1440 120" preserveAspectRatio="none" aria-hidden="true"><path d="M0 70 C240 120 480 120 720 80 C960 40 1200 20 1440 60 V120 H0 Z"/></svg>
</section>

<!-- ============ CATEGORIES ============ -->
<section class="section" aria-labelledby="cat-h">
  <div class="container">
    <header class="section-head center">
      <p class="eyebrow"><?= e(t('cat_eyebrow')) ?></p>
      <h2 id="cat-h"><?= accent(t('cat_h')) ?></h2>
    </header>
    <div class="cat-grid">
      <?php foreach ($categories as $c): ?>
        <a class="cat" href="<?= url('menu?c=' . urlencode($c['slug'])) ?>" style="--c: <?= e($c['color']) ?>">
          <span class="cat-ico"><svg aria-hidden="true"><use href="#<?= $fruitIcon[$c['garnish']] ?? 'i-orange' ?>"/></svg></span>
          <span class="cat-name"><?= e(tr($c, 'name')) ?></span>
          <span class="cat-count"><?= e(t('items', ['n' => num($c['total'])])) ?></span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ============ VISIT ============ -->
<section class="section pt-0" id="visit" aria-labelledby="visit-h">
  <div class="container">
    <div class="visit">
      <svg class="fruit v-lemon" aria-hidden="true"><use href="#i-lemon"/></svg>
      <svg class="leaf v-leaf" aria-hidden="true"><use href="#i-mint"/></svg>
      <div class="visit-copy">
        <p class="eyebrow"><?= e(t('visit_eyebrow')) ?></p>
        <h2 id="visit-h"><?= accent(t('visit_h')) ?></h2>
        <p><?= e(t('visit_p')) ?></p>
        <div class="btn-row">
          <a class="btn btn-primary" href="https://www.google.com/maps/search/?api=1&amp;query=<?= e(rawurlencode(setting('map_query'))) ?>" target="_blank" rel="noopener"><?= e(t('directions')) ?><svg class="ico" aria-hidden="true"><use href="#i-arrow"/></svg></a>
          <a class="btn btn-outline" href="<?= e(phone_href()) ?>"><svg class="ico" aria-hidden="true"><use href="#i-phone"/></svg><?= e(setting('shop_phone')) ?></a>
        </div>
      </div>
      <dl class="visit-info">
        <div><dt><svg class="ico" aria-hidden="true"><use href="#i-pin"/></svg><?= e(t('address')) ?></dt><dd><?= e(setting_l('shop_address')) ?></dd></div>
        <div><dt><svg class="ico" aria-hidden="true"><use href="#i-clock"/></svg><?= e(t('hours')) ?></dt><dd><?= e(setting_l('shop_hours')) ?></dd></div>
        <div><dt><svg class="ico" aria-hidden="true"><use href="#i-drop"/></svg><?= e(t('email')) ?></dt><dd><a href="mailto:<?= e(setting('shop_email')) ?>"><?= e(setting('shop_email')) ?></a></dd></div>
      </dl>
    </div>
  </div>
</section>
