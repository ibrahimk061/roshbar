<?php
/**
 * Real photo when products.image is set, otherwise the glass illustration.
 * @var array $p product row
 * @var string|null $class
 */
$class = $class ?? '';
if (!empty($p['image'])): ?>
  <img class="product-photo <?= e($class) ?>" src="<?= e(url($p['image'])) ?>" alt="<?= e(tr($p, 'name')) ?>" loading="lazy" decoding="async">
<?php else:
  partial('glass', ['color' => $p['color'], 'garnish' => $p['garnish'] ?? 'orange', 'class' => $class]);
endif;
