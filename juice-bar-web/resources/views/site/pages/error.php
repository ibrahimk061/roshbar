<?php /** @var int $code @var string $message */ ?>
<section class="container error-page">
  <h1><?= num($code) ?></h1>
  <p><?= e($message) ?></p>
  <a class="btn btn-primary" href="<?= url('/') ?>"><?= e(t('go_home')) ?></a>
</section>
