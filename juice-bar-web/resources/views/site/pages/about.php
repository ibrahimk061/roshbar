<?php
/** @var array $featured */

partial('banner', [
    'eyebrow' => t('about_eyebrow'),
    'heading' => t('about_h'),
    'crumbs'  => [[t('nav_home'), ''], [t('nav_about'), null]],
]);
?>
<section class="section pt-0">
  <div class="container about-grid">
    <div class="about-art" aria-hidden="true">
      <div class="why-circle"></div>
      <?php partial('glass', ['color' => '#FFB000', 'garnish' => 'orange', 'class' => 'why-g1']); ?>
      <?php partial('glass', ['color' => '#4CB963', 'garnish' => 'kiwi', 'class' => 'why-g2']); ?>
      <?php partial('glass', ['color' => '#C9E37A', 'garnish' => 'lemon', 'class' => 'why-g3']); ?>
      <svg class="fruit w-orange"><use href="#i-mango"/></svg>
      <svg class="leaf w-leaf"><use href="#i-mint"/></svg>
    </div>
    <div class="about-copy">
      <p class="eyebrow"><?= e(t('about_eyebrow')) ?></p>
      <h2 style="margin-bottom:20px"><?= accent(t('about_h')) ?></h2>
      <p><?= e(t('about_p1')) ?></p>
      <p><?= e(t('about_p2')) ?></p>
      <div class="btn-row" style="margin-top:28px">
        <a class="btn btn-primary" href="<?= url('menu') ?>"><?= e(t('hero_cta')) ?><svg class="ico" aria-hidden="true"><use href="#i-arrow"/></svg></a>
      </div>
    </div>
  </div>
</section>

<section class="section section-tint">
  <div class="container">
    <header class="section-head center">
      <h2><?= accent(t('about_values_h')) ?></h2>
    </header>
    <div class="values">
      <?php foreach ([['i-leaf', 'about_v1'], ['i-drop', 'about_v2'], ['i-check', 'about_v3'], ['i-bolt', 'about_v4']] as $i => [$icon, $k]): ?>
        <div class="value feature-<?= $i + 1 ?>">
          <span class="feature-ico"><svg aria-hidden="true"><use href="#<?= $icon ?>"/></svg></span>
          <p><?= e(t($k)) ?></p>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php if ($featured): ?>
<section class="section">
  <div class="container">
    <header class="section-head center">
      <p class="eyebrow"><?= e(t('best_eyebrow')) ?></p>
      <h2><?= accent(t('best_h')) ?></h2>
    </header>
    <div class="card-grid" style="grid-template-columns:repeat(auto-fit,minmax(220px,1fr))">
      <?php foreach ($featured as $p) { partial('product-card', ['p' => $p]); } ?>
    </div>
  </div>
</section>
<?php endif; ?>
