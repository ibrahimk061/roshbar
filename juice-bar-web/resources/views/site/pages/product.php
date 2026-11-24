<?php
/** @var array $product @var array $sizes @var array $related */

$other = lang() === 'en' ? 'bn' : 'en';
$name = tr($product, 'name');
$sideFruit = ['mango' => 'i-mango', 'watermelon' => 'i-watermelon', 'strawberry' => 'i-strawberry', 'lemon' => 'i-lemon',
    'kiwi' => 'i-kiwi', 'berry' => 'i-berry', 'mint' => 'i-lemon'][$product['garnish']] ?? 'i-orange';
?>
<section class="section">
  <div class="container product">
    <div class="product-stage" style="--c: <?= e($product['color']) ?>">
      <?php if (empty($product['image'])): ?>
        <svg class="fruit ps-leaf" aria-hidden="true"><use href="#i-mint"/></svg>
        <svg class="fruit ps-fruit" aria-hidden="true"><use href="#<?= $sideFruit ?>"/></svg>
      <?php endif; ?>
      <?php partial('product-visual', ['p' => $product]); ?>
    </div>

    <div class="product-info">
      <ol class="crumbs" style="justify-content:flex-start;margin:0 0 14px" aria-label="Breadcrumb">
        <li><a href="<?= url('menu') ?>"><?= e(t('nav_menu')) ?></a></li>
        <li><a href="<?= url('menu?c=' . urlencode($product['category_slug'])) ?>"><?= e(tr($product, 'category')) ?></a></li>
      </ol>
      <h1><?= e($name) ?></h1>
      <p class="product-alt" lang="<?= $other ?>"><?= e($product['name_' . $other]) ?></p>
      <p class="product-desc"><?= e(tr($product, 'description')) ?></p>

      <div class="badges">
        <span class="chip chip-green"><svg class="ico" aria-hidden="true"><use href="#i-bolt"/></svg><?= e(t('kcal', ['n' => num($product['calories'])])) ?></span>
        <?php if ($product['is_seasonal']): ?><span class="chip chip-berry"><?= e(t('seasonal_badge')) ?></span><?php endif; ?>
        <span class="chip"><?= e(tr($product, 'category')) ?></span>
      </div>

      <div class="info-block">
        <h2><?= e(t('in_it')) ?></h2>
        <p><?= e(tr($product, 'ingredients')) ?></p>
      </div>

      <div class="info-block">
        <h2><?= e(t('price_list')) ?></h2>
        <table class="price-table">
          <tbody>
            <?php foreach ($sizes as $s): ?>
              <tr><th scope="row"><?= e(num($s['label'])) ?></th><td><?= money($s['price']) ?></td></tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <div class="order-box">
        <p><?= e(t('order_how')) ?></p>
        <div class="btn-row">
          <a class="btn btn-primary" href="<?= e(phone_href()) ?>"><svg class="ico" aria-hidden="true"><use href="#i-phone"/></svg><?= e(t('call_now')) ?></a>
          <a class="btn btn-green" href="<?= e(whatsapp_href(t('wa_text', ['name' => $name]))) ?>" target="_blank" rel="noopener"><?= e(t('whatsapp')) ?><svg class="ico" aria-hidden="true"><use href="#i-arrow"/></svg></a>
        </div>
      </div>
    </div>
  </div>
</section>

<?php if ($related): ?>
<section class="section section-tint">
  <div class="container">
    <header class="section-head center">
      <h2><?= accent(t('also_like')) ?></h2>
    </header>
    <div class="card-grid">
      <?php foreach ($related as $p) { partial('product-card', ['p' => $p]); } ?>
    </div>
  </div>
</section>
<?php endif; ?>
