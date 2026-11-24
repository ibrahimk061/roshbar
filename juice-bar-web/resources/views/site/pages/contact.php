<?php
/** @var array $errors @var bool $sent */

partial('banner', [
    'eyebrow' => t('contact_eyebrow'),
    'heading' => t('contact_h'),
    'lead'    => t('contact_p'),
    'crumbs'  => [[t('nav_home'), ''], [t('nav_contact'), null]],
]);

$err = fn(string $k) => isset($errors[$k]) ? '<span class="error" id="err-' . $k . '">' . e($errors[$k]) . '</span>' : '';
$cls = fn(string $k) => isset($errors[$k]) ? 'field has-error' : 'field';
$aria = fn(string $k) => isset($errors[$k]) ? 'aria-invalid="true" aria-describedby="err-' . $k . '"' : '';
$topic = old('topic', 'general');
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
          <span class="feature-ico" ><svg aria-hidden="true"><use href="#i-phone"/></svg></span>
          <div><h2><?= e(t('phone')) ?></h2><a href="<?= e(phone_href()) ?>"><?= e(setting('shop_phone')) ?></a></div>
        </div>
        <div class="contact-card">
          <span class="feature-ico" ><svg aria-hidden="true"><use href="#i-drop"/></svg></span>
          <div><h2><?= e(t('email')) ?></h2><a href="mailto:<?= e(setting('shop_email')) ?>"><?= e(setting('shop_email')) ?></a></div>
        </div>
        <div class="contact-card">
          <span class="feature-ico" ><svg aria-hidden="true"><use href="#i-clock"/></svg></span>
          <div><h2><?= e(t('hours')) ?></h2><p><?= e(setting_l('shop_hours')) ?></p></div>
        </div>
      </div>
      <div class="map">
        <iframe title="Roshbar map" loading="lazy" referrerpolicy="no-referrer-when-downgrade"
                src="https://www.google.com/maps?q=<?= e(rawurlencode(setting('map_query'))) ?>&amp;output=embed"></iframe>
      </div>
    </div>

    <form class="form-card" id="form" method="post" action="<?= url('contact') ?>" novalidate>
      <h2><?= e(t('send')) ?></h2>
      <?php if ($sent): ?><p class="alert alert-ok" role="status"><?= e(t('sent_ok')) ?></p><?php endif; ?>
      <?= csrf_field() ?>
      <label class="hp" aria-hidden="true">Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
      <div class="form-grid">
        <div class="<?= $cls('name') ?>">
          <label for="c-name"><?= e(t('f_name')) ?></label>
          <input id="c-name" name="name" autocomplete="name" value="<?= e(old('name')) ?>" required <?= $aria('name') ?>>
          <?= $err('name') ?>
        </div>
        <div class="<?= $cls('phone') ?>">
          <label for="c-phone"><?= e(t('f_phone')) ?></label>
          <input id="c-phone" name="phone" type="tel" inputmode="tel" autocomplete="tel" placeholder="01XXXXXXXXX" value="<?= e(old('phone')) ?>" required <?= $aria('phone') ?>>
          <?= $err('phone') ?>
        </div>
        <div class="field full">
          <label for="c-topic"><?= e(t('f_topic')) ?></label>
          <select id="c-topic" name="topic">
            <?php foreach (['general', 'catering', 'feedback'] as $tp): ?>
              <option value="<?= $tp ?>" <?= $topic === $tp ? 'selected' : '' ?>><?= e(t('topic_' . $tp)) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="<?= $cls('message') ?> full">
          <label for="c-message"><?= e(t('f_message')) ?></label>
          <textarea id="c-message" name="message" required <?= $aria('message') ?>><?= e(old('message')) ?></textarea>
          <?= $err('message') ?>
        </div>
        <div class="field full">
          <button class="btn btn-primary btn-block" type="submit"><?= e(t('send')) ?><svg class="ico" aria-hidden="true"><use href="#i-arrow"/></svg></button>
        </div>
      </div>
    </form>
  </div>
</section>
