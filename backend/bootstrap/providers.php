<?php

$providers = [
    App\Providers\AppServiceProvider::class,
    App\Providers\EventServiceProvider::class,
];

// Only load Horizon and Telescope in non-testing environments
// Check APP_ENV, or if running artisan test/tinker commands
$isTesting = env('APP_ENV') === 'testing'
    || getenv('APP_ENV') === 'testing'
    || (isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] === 'testing')
    || (isset($_SERVER['argv']) && is_array($_SERVER['argv']) && (
        in_array('test', $_SERVER['argv'], true)
        || in_array('--env=testing', $_SERVER['argv'], true)
    ));

if (! $isTesting) {
    $providers[] = App\Providers\HorizonServiceProvider::class;
    $providers[] = App\Providers\TelescopeServiceProvider::class;
}

return $providers;
