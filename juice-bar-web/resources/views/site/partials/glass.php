<?php
/**
 * Juice glass with garnish. Stands in for product photos until real ones are uploaded.
 * @var string      $color   drink color
 * @var string|null $garnish orange | lemon | kiwi | strawberry | watermelon | mango | berry | mint
 * @var float|null  $fill    0..1
 * @var string|null $class
 */
$garnish = $garnish ?? 'orange';
$fill    = $fill ?? 0.84;
$class   = $class ?? '';
$uid     = 'g' . substr(md5(uniqid('', true)), 0, 8);
$top     = 228 - 186 * max(0, min(1, $fill));
$glass   = 'M30 42 H170 L154 220 Q153 230 143 230 H57 Q47 230 46 220 Z';

// rim garnish: [symbol, x, y, w, h, rotate]
$rim = [
    'orange'     => ['i-orange', 4, 6, 74, 74, -12],
    'lemon'      => ['i-lemon', 4, 6, 70, 70, -12],
    'kiwi'       => ['i-kiwi', 6, 8, 68, 68, -10],
    'mint'       => ['i-lemon', 6, 8, 64, 64, -12],
    'strawberry' => ['i-strawberry', 10, -6, 62, 68, -18],
    'watermelon' => ['i-watermelon', 0, 18, 90, 52, -24],
    'mango'      => ['i-mango', 8, -2, 66, 66, -14],
    'berry'      => ['i-berry', 14, 10, 40, 40, 0],
][$garnish] ?? ['i-orange', 4, 6, 74, 74, -12];
?>
<svg class="glass <?= e($class) ?>" viewBox="0 0 200 250" aria-hidden="true" focusable="false">
  <defs><clipPath id="<?= $uid ?>"><path d="<?= $glass ?>"/></clipPath></defs>

  <!-- straw -->
  <g transform="rotate(12 130 120)">
    <rect x="124" y="-6" width="11" height="200" rx="5.5" fill="#fff"/>
    <path d="M124 6h11M124 26h11M124 46h11M124 66h11" stroke="<?= e($color) ?>" stroke-width="8" opacity=".9"/>
  </g>

  <!-- glass body -->
  <path d="<?= $glass ?>" fill="#fff" fill-opacity=".55"/>
  <g clip-path="url(#<?= $uid ?>)">
    <rect x="0" y="<?= round($top, 1) ?>" width="200" height="<?= round(250 - $top, 1) ?>" fill="<?= e($color) ?>"/>
    <rect x="0" y="<?= round($top, 1) ?>" width="200" height="<?= round(250 - $top, 1) ?>" fill="url(#gr-liquid-depth)"/>
    <path d="M0 <?= round($top, 1) ?> q25 -7 50 0 t50 0 t50 0 t50 0 V<?= round($top + 10, 1) ?> H0 Z" fill="#fff" opacity=".25"/>
    <!-- ice -->
    <rect x="62" y="<?= round($top + 8, 1) ?>" width="30" height="28" rx="6" fill="#fff" opacity=".35" transform="rotate(-12 77 <?= round($top + 22, 1) ?>)"/>
    <rect x="100" y="<?= round($top + 14, 1) ?>" width="26" height="24" rx="6" fill="#fff" opacity=".28" transform="rotate(14 113 <?= round($top + 26, 1) ?>)"/>
    <circle cx="80" cy="<?= round($top + 92, 1) ?>" r="5" fill="#fff" opacity=".35"/>
    <circle cx="120" cy="<?= round($top + 120, 1) ?>" r="3.5" fill="#fff" opacity=".35"/>
    <circle cx="96" cy="<?= round($top + 150, 1) ?>" r="2.5" fill="#fff" opacity=".35"/>
    <path d="<?= $glass ?>" fill="url(#gr-glass-shine)"/>
  </g>
  <path d="<?= $glass ?>" fill="none" stroke="#fff" stroke-width="3" stroke-opacity=".9"/>
  <ellipse cx="100" cy="42" rx="70" ry="5" fill="#fff" opacity=".6"/>

  <!-- garnish -->
  <use href="#i-mint" x="118" y="14" width="66" height="40" transform="rotate(-28 151 34)"/>
  <use href="#i-mint" x="138" y="30" width="48" height="29" transform="rotate(18 162 44)"/>
  <?php if ($garnish === 'berry'): ?>
    <use href="#i-berry" x="40" y="22" width="34" height="34"/>
    <use href="#i-berry" x="62" y="28" width="30" height="30"/>
  <?php endif; ?>
  <use href="#<?= $rim[0] ?>" x="<?= $rim[1] ?>" y="<?= $rim[2] ?>" width="<?= $rim[3] ?>" height="<?= $rim[4] ?>"
       transform="rotate(<?= $rim[5] ?> <?= $rim[1] + $rim[3] / 2 ?> <?= $rim[2] + $rim[4] / 2 ?>)"/>
</svg>
