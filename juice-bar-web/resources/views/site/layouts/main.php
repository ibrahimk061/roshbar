<?php
/** @var string $content */

$title = $title ?? 'Roshbar';
$theme = $theme ?? '#B8174E';
$nav = [
    ''        => t('nav_home'),
    'menu'    => t('nav_menu'),
    'about'   => t('nav_about'),
    'contact' => t('nav_contact'),
];
$current = trim(request()->path(), '/');
$isActive = fn(string $p) => $p === '' ? $current === '' : str_starts_with($current, $p);
?>
<!doctype html>
<html lang="<?= e(lang()) ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title) ?></title>
  <meta name="description" content="<?= e($description ?? t('meta_desc')) ?>">
  <meta name="theme-color" content="<?= e($theme) ?>">
  <meta property="og:title" content="<?= e($title) ?>">
  <meta property="og:description" content="<?= e($description ?? t('meta_desc')) ?>">
  <meta property="og:type" content="website">
  <link rel="alternate" hreflang="en" href="<?= e(url($current) . '?lang=en') ?>">
  <link rel="alternate" hreflang="bn" href="<?= e(url($current) . '?lang=bn') ?>">
  <link rel="icon" href="<?= site_asset('img/favicon.svg') ?>" type="image/svg+xml">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&family=Hind+Siliguri:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= site_asset('css/app.css') ?>">
  <script type="application/ld+json"><?= json_encode([
      '@context' => 'https://schema.org',
      '@type' => 'JuiceBar',
      'name' => 'Roshbar',
      'url' => url('/'),
      'telephone' => setting('shop_phone'),
      'email' => setting('shop_email'),
      'address' => ['@type' => 'PostalAddress', 'streetAddress' => setting('shop_address'), 'addressLocality' => 'Dhaka', 'addressCountry' => 'BD'],
      'openingHours' => 'Mo-Su 08:00-23:00',
      'servesCuisine' => 'Juice',
  ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
</head>
<body class="lang-<?= e(lang()) ?>">
  <?php partial('sprite'); ?>
  <a class="skip" href="#main"><?= e(t('skip')) ?></a>

  <header class="site-header" data-header>
    <div class="container header-row">
      <a class="logo" href="<?= url('/') ?>" aria-label="Roshbar">
        <svg class="logo-icon" aria-hidden="true"><use href="#i-orange"/></svg>
        <span class="logo-text"><span>Rosh</span><span>bar</span></span>
      </a>

      <nav class="nav" id="site-nav" aria-label="Main">
        <?php foreach ($nav as $path => $label): ?>
          <a href="<?= url($path) ?>" <?= $isActive($path) ? 'aria-current="page"' : '' ?>><?= e($label) ?></a>
        <?php endforeach; ?>
        <a class="nav-lang-mobile" href="<?= e(lang_switch_url('/')) ?>"><?= e(t('lang_other')) ?></a>
      </nav>

      <div class="header-actions">
        <a class="lang-switch" href="<?= e(lang_switch_url('/')) ?>" hreflang="<?= lang() === 'en' ? 'bn' : 'en' ?>"><?= e(t('lang_other')) ?></a>
        <a class="btn btn-primary btn-sm" href="<?= e(phone_href()) ?>">
          <svg class="ico" aria-hidden="true"><use href="#i-phone"/></svg><span><?= e(t('call_now')) ?></span>
        </a>
        <button class="nav-toggle" type="button" aria-controls="site-nav" aria-expanded="false" aria-label="<?= e(t('open_menu')) ?>" data-nav-toggle>
          <svg class="ico" aria-hidden="true"><use href="#i-menu"/></svg>
        </button>
      </div>
    </div>
  </header>

  <main id="main">
    <?= $content ?>
  </main>

  <footer class="site-footer">
    <div class="container footer-grid">
      <div class="footer-brand">
        <a class="logo logo-light" href="<?= url('/') ?>" aria-label="Roshbar">
          <svg class="logo-icon" aria-hidden="true"><use href="#i-orange"/></svg>
          <span class="logo-text"><span>Rosh</span><span>bar</span></span>
        </a>
        <p><?= e(t('footer_about')) ?></p>
      </div>
      <div>
        <h2 class="footer-h"><?= e(t('footer_links')) ?></h2>
        <ul class="footer-list">
          <?php foreach ($nav as $path => $label): ?>
            <li><a href="<?= url($path) ?>"><?= e($label) ?></a></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <div>
        <h2 class="footer-h"><?= e(t('footer_contact')) ?></h2>
        <ul class="footer-list">
          <li><svg class="ico" aria-hidden="true"><use href="#i-pin"/></svg><?= e(setting_l('shop_address')) ?></li>
          <li><svg class="ico" aria-hidden="true"><use href="#i-phone"/></svg><a href="<?= e(phone_href()) ?>"><?= e(setting('shop_phone')) ?></a></li>
          <li><svg class="ico" aria-hidden="true"><use href="#i-drop"/></svg><a href="mailto:<?= e(setting('shop_email')) ?>"><?= e(setting('shop_email')) ?></a></li>
        </ul>
      </div>
      <div>
        <h2 class="footer-h"><?= e(t('footer_hours')) ?></h2>
        <ul class="footer-list">
          <li><svg class="ico" aria-hidden="true"><use href="#i-clock"/></svg><?= e(setting_l('shop_hours')) ?></li>
        </ul>
      </div>
    </div>
    <div class="container footer-bottom">
      <p>© <?= num(date('Y')) ?> Roshbar.com. <?= e(t('rights')) ?> · <a href="<?= e(route('login')) ?>" rel="nofollow"><?= e(t('staff_login')) ?></a></p>
    </div>
  </footer>

  <script src="<?= site_asset('js/app.js') ?>" defer></script>
</body>
</html>
