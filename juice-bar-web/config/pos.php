<?php

return [
    // Defaults; the settings table overrides business_name / address / phone / order_prefix.
    'business_name' => env('POS_BUSINESS_NAME', 'Roshbar'),
    'order_prefix' => env('POS_ORDER_PREFIX', 'RB'),
    'currency' => 'BDT',
    'currency_symbol' => '৳',

    // Timestamps are stored in UTC (config/app.php). Business dates and
    // receipt times are calculated in this zone.
    'timezone' => env('POS_TIMEZONE', 'Asia/Dhaka'),

    // Orders per letter block: A0001..A0100, then B0001..B0100, ... Z, AA, AB...
    'orders_per_block' => 100,

    // Hour (0-23, business timezone) at which a new business day starts.
    // 0 = midnight. Open question: late-night shops may want e.g. 4.
    'business_day_starts_at_hour' => (int) env('POS_DAY_START_HOUR', 0),

    'sugar_levels' => ['0', '25', '50', '75', '100'],
    'default_sugar' => '100',
    'ice_levels' => [
        'no_ice' => 'No Ice',
        'less_ice' => 'Less Ice',
        'regular' => 'Regular Ice',
    ],
    'default_ice' => 'regular',
    'order_types' => [
        'takeaway' => 'Takeaway',
        'dine_in' => 'Dine-in',
    ],
    'max_quantity_per_line' => 99,
];
