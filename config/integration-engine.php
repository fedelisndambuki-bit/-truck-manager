<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Driver discovery
    |--------------------------------------------------------------------------
    |
    | Drivers are discovered by scanning app/IntegrationEngine/Drivers for
    | classes tagged with #[IntegrationDriver], not hand-maintained here.
    | `use_cache` controls whether the boot-time registration reads the
    | cached scan (storage/framework/cache/integration-engine-drivers.php,
    | built by `php artisan integration-engine:driver:discover`) or performs
    | a live scan on every request — mirrors config:cache/route:cache.
    |
    */

    'discovery' => [
        // Config files load before the container is ready for app()->environment()
        // calls, so this reads APP_ENV directly rather than via that helper.
        'use_cache' => env('INTEGRATION_ENGINE_USE_DRIVER_CACHE', env('APP_ENV', 'production') !== 'local'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue
    |--------------------------------------------------------------------------
    */

    'queue' => [
        'connection' => env('INTEGRATION_ENGINE_QUEUE_CONNECTION', 'database'),
        'queue_name' => env('INTEGRATION_ENGINE_QUEUE_NAME', 'device-ingestion'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook / inbound security
    |--------------------------------------------------------------------------
    */

    'webhook' => [
        'verify_signatures' => env('INTEGRATION_ENGINE_VERIFY_SIGNATURES', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Health checks
    |--------------------------------------------------------------------------
    */

    'health' => [
        'offline_threshold_minutes' => env('INTEGRATION_ENGINE_OFFLINE_THRESHOLD_MINUTES', 10),
    ],

];
