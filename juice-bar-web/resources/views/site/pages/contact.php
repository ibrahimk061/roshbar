<?php
/** Contact details only: the website is view-only (no forms). */

partial('banner', [
    'eyebrow' => t('contact_eyebrow'),
    'heading' => t('contact_h'),
    'lead'    => t('contact_p'),
    'crumbs'  => [[t('nav_home'), ''], [t('nav_contact'), null]],
]);

?>
<section class="section pt-0">
  <div class="container contact-grid">
    <div>
      <div class="contact-cards">
        <div class="contact-card">
          <span class="feature-ico"><svg aria-hidden="true"><use href="#i-pin"/></svg></span>
          <div><h2><?= e(t('address')) ?></h2><p><?= e(setting_l('shop_address')) ?></p></div>
        </div>
        <div class="contact-card">
          <span class="feature-ico"><svg aria-hidden="true"><use href="#i-phone"/></svg></span>
          <div><h2><?= e(t('phone')) ?></h2><a href="<?= e(phone_href()) ?>"><?= e(setting('shop_phone')) ?></a></div>
        </div>
        <div class="contact-card">
          <span class="feature-ico"><svg aria-hidden="true"><use href="#i-drop"/></svg></span>
          <div><h2><?= e(t('email')) ?></h2><a href="mailto:<?= e(setting('shop_email')) ?>"><?= e(setting('shop_email')) ?></a></div>
        </div>
        <div class="contact-card">
          <span class="feature-ico"><svg aria-hidden="true"><use href="#i-clock"/></svg></span>
          <div><h2><?= e(t('hours')) ?></h2><p><?= e(setting_l('shop_hours')) ?></p></div>
        </div>
      </div>
      <div class="btn-row" style="margin-top:24px">
        <a class="btn btn-primary" href="<?= e(phone_href()) ?>"><svg class="ico" aria-hidden="true"><use href="#i-phone"/></svg><?= e(t('call_now')) ?></a>
        <?php if (setting('whatsapp')): ?>
          <a class="btn btn-green" href="<?= e(whatsapp_href()) ?>" target="_blank" rel="noopener"><?= e(t('whatsapp')) ?><svg class="ico" aria-hidden="true"><use href="#i-arrow"/></svg></a>
        <?php endif; ?>
      </div>
    </div>
    <div class="map map-tall">
      <iframe title="Roshbar map" loading="lazy" referrerpolicy="no-referrer-when-downgrade"
              src="https://www.google.com/maps?q=<?= e(rawurlencode(setting('map_query'))) ?>&amp;output=embed"></iframe>
    </div>
  </div>
</section>
