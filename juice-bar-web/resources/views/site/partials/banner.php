<?php
/**
 * Pink page banner for inner pages.
 * @var string $eyebrow @var string $heading (may contain *accent*) @var string|null $lead
 * @var array  $crumbs  [[label, path|null], ...]
 */
?>
<section class="banner">
  <svg class="fruit b-orange" aria-hidden="true"><use href="#i-orange"/></svg>
  <svg class="fruit b-leaf" aria-hidden="true"><use href="#i-mint"/></svg>
  <svg class="fruit b-straw" aria-hidden="true"><use href="#i-strawberry"/></svg>
  <div class="container banner-inner">
    <p class="eyebrow"><?= e($eyebrow) ?></p>
    <h1><?= accent($heading) ?></h1>
    <?php if (!empty($lead)): ?><p><?= e($lead) ?></p><?php endif; ?>
    <ol class="crumbs" aria-label="Breadcrumb">
      <?php foreach ($crumbs as [$label, $path]): ?>
        <li><?php if ($path !== null): ?><a href="<?= url($path) ?>"><?= e($label) ?></a><?php else: ?><span aria-current="page"><?= e($label) ?></span><?php endif; ?></li>
      <?php endforeach; ?>
    </ol>
  </div>
  <svg class="wave" viewBox="0 0 1440 120" preserveAspectRatio="none" aria-hidden="true"><path d="M0 70 C240 120 480 120 720 80 C960 40 1200 20 1440 60 V120 H0 Z"/></svg>
</section>
