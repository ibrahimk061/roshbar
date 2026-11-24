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

    // POS tile icon when an item has no photo: first keyword found in the
    // item's English name, then its category slug, wins.
    'item_icons' => [
        'lassi' => '🥛',
        'mango' => '🥭', 'watermelon' => '🍉', 'daab' => '🥥', 'coconut' => '🥥',
        'lychee' => '🌹', 'rose' => '🌹', 'bel' => '🍈', 'banana' => '🍌',
        'beet' => '🥕', 'carrot' => '🥕', 'green' => '🥬', 'detox' => '🥬',
        'jambura' => '🍊', 'malta' => '🍊', 'orange' => '🍊', 'amra' => '🫒',
        'lemon' => '🍋', 'pineapple' => '🍍', 'strawberry' => '🍓', 'grape' => '🍇',
        'apple' => '🍎', 'guava' => '🍐', 'papaya' => '🍈', 'pomegranate' => '🍎',
        'shake' => '🥤', 'smoothie' => '🥤', 'smoothies' => '🥤',
    ],
    'default_item_icon' => '🧃',

    // Ask sugar / ice for each drink? Off: the POS and the app skip both.
    'ask_sugar_ice' => (bool) env('POS_ASK_SUGAR_ICE', false),
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

    // Language of drink names on the POS, orders, reports and receipts:
    // 'bn' = Bangla name (falls back to English when a drink has none), 'en' = English.
    'item_language' => env('POS_ITEM_LANG', 'bn'),

    // Language of the words printed on receipts (web print and SUNMI).
    'receipt_language' => env('POS_RECEIPT_LANG', 'bn'),

    'receipt_labels' => [
        'en' => [
            'phone' => 'Phone', 'type' => 'Type', 'served_by' => 'Served by', 'sugar' => 'Sugar',
            'subtotal' => 'Subtotal', 'discount' => 'Discount', 'vat' => 'VAT', 'incl' => 'incl.',
            'total' => 'TOTAL', 'cash_received' => 'Cash received', 'change' => 'CHANGE', 'due' => 'DUE',
            'paid' => 'PAID', 'cancelled' => '*** CANCELLED ***', 'items' => 'Items', 'currency' => 'Tk',
            'order_types' => ['takeaway' => 'Takeaway', 'dine_in' => 'Dine-in'],
            'ice_levels' => ['no_ice' => 'No Ice', 'less_ice' => 'Less Ice', 'regular' => 'Regular Ice'],
        ],
        'bn' => [
            'phone' => 'ফোন', 'type' => 'ধরন', 'served_by' => 'সার্ভ করেছেন', 'sugar' => 'চিনি',
            'subtotal' => 'মোট দাম', 'discount' => 'ছাড়', 'vat' => 'ভ্যাট', 'incl' => 'সহ',
            'total' => 'সর্বমোট', 'cash_received' => 'নগদ গ্রহণ', 'change' => 'ফেরত', 'due' => 'বাকি',
            'paid' => 'পরিশোধিত', 'cancelled' => '*** বাতিল ***', 'items' => 'আইটেম', 'currency' => '৳',
            'order_types' => ['takeaway' => 'পার্সেল', 'dine_in' => 'দোকানে বসে'],
            'ice_levels' => ['no_ice' => 'বরফ ছাড়া', 'less_ice' => 'কম বরফ', 'regular' => 'স্বাভাবিক বরফ'],
        ],
    ],
];
