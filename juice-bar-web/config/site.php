<?php

// Public website (roshbar.com)
return [
    'name' => env('SITE_NAME', 'Roshbar'),
    'default_lang' => env('SITE_DEFAULT_LANG', 'en'),

    // Read-only connection to the old roshbar.com database, used only by
    // `php artisan roshbar:import-legacy`. Fill these on the server from the
    // old site's .env; never commit real values.
    'legacy' => [
        'driver' => env('LEGACY_DB_DRIVER', 'mysql'),
        'host' => env('LEGACY_DB_HOST', '127.0.0.1'),
        'port' => env('LEGACY_DB_PORT', '3306'),
        'database' => env('LEGACY_DB_DATABASE'),
        'username' => env('LEGACY_DB_USERNAME'),
        'password' => env('LEGACY_DB_PASSWORD'),
        'uploads_path' => env('LEGACY_UPLOADS_PATH'), // e.g. /home/USER/roshbar/public
    ],
];
