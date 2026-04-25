<?php

$defaultStartupDatabase = function_exists('database_path')
    ? database_path('unified-appointments.sqlite')
    : dirname(__DIR__) . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'unified-appointments.sqlite';
$defaultDatabaseLibraryPath = function_exists('base_path')
    ? base_path('src' . DIRECTORY_SEPARATOR . 'Database' . DIRECTORY_SEPARATOR . 'Unified Databases')
    : dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Database' . DIRECTORY_SEPARATOR . 'Unified Databases';

return [
    'database_library_path' => env('UNIFIED_APPOINTMENTS_DATABASE_LIBRARY_PATH', $defaultDatabaseLibraryPath),
    'edition' => env('UNIFIED_APPOINTMENTS_EDITION', 'startup'),
    'startup' => [
        'auto_bootstrap' => env('UNIFIED_APPOINTMENTS_AUTO_BOOTSTRAP', true),
        'database' => env('UNIFIED_APPOINTMENTS_STARTUP_DATABASE', $defaultStartupDatabase),
    ],
    'connection' => env('UNIFIED_APPOINTMENTS_CONNECTION'),
    'driver' => env('UNIFIED_APPOINTMENTS_DRIVER'),
    'host' => env('UNIFIED_APPOINTMENTS_HOST'),
    'username' => env('UNIFIED_APPOINTMENTS_USERNAME'),
    'password' => env('UNIFIED_APPOINTMENTS_PASSWORD'),
    'database' => env('UNIFIED_APPOINTMENTS_DATABASE'),
    'port' => env('UNIFIED_APPOINTMENTS_PORT'),
    'table_prefix' => env('UNIFIED_APPOINTMENTS_TABLE_PREFIX', 'ua_'),
    'app_timezone' => env('UNIFIED_APPOINTMENTS_TIMEZONE', 'America/New_York'),
    'routes' => [
        'enabled' => true,
        'prefix' => 'unified-appointments',
        'name_prefix' => 'unified-appointments.',
        'middleware' => ['api'],
        'web_middleware' => ['web'],
    ],
    'supported_databases' => [
        'mssql' => 'MS SQL Server',
        'mysql' => 'MySQL',
        'postgres' => 'PostgreSQL',
        'sqlite' => 'SQLite',
    ],
    'timezones' => [
        'America/New_York' => 'Eastern (GMT-5)',
        'America/Denver' => 'Mountain Daylight Time (GMT-6)',
        'America/Phoenix' => 'Mountain Standard Time (GMT-7)',
        'America/Los_Angeles' => 'Pacific Daylight Time (GMT-7)',
        'America/Anchorage' => 'Alaska Daylight Time (GMT-8)',
        'Pacific/Honolulu' => 'Hawaii-Aleutian Standard Time (GMT-10)',
    ],
    'ui' => [
        'theme' => 'atlantic',
        'theme_query_parameter' => 'theme',
        'allow_theme_switcher' => true,
        'application_name' => env(
            'CALIXY_APPLICATION_NAME',
            env('UNIFIED_APPOINTMENTS_APPLICATION_NAME', 'Calixy')
        ),
        'version' => env(
            'CALIXY_VERSION',
            env('UNIFIED_APPOINTMENTS_VERSION', 'dev-634ec54')
        ),
        'donationware_url' => env(
            'CALIXY_DONATIONWARE_URL',
            env('UNIFIED_APPOINTMENTS_DONATIONWARE_URL', 'https://github.com/sponsors')
        ),
        'themes' => [
            'sunrise' => [
                'label' => 'Sunrise',
                'description' => 'Warm gold and sand tones for a welcoming booking experience.',
                'css_variables' => [
                    '--ua-page-background' => 'linear-gradient(135deg, #fff3d6 0%, #ffe7c2 35%, #f8d9a0 100%)',
                    '--ua-card-background' => '#fffaf0',
                    '--ua-card-muted-background' => 'rgba(255, 255, 255, 0.54)',
                    '--ua-border' => 'rgba(180, 83, 9, 0.18)',
                    '--ua-text' => '#4a2706',
                    '--ua-muted-text' => '#7c4a12',
                    '--ua-accent' => '#c76b12',
                    '--ua-accent-contrast' => '#fffaf0',
                    '--ua-pill-background' => 'rgba(199, 107, 18, 0.12)',
                    '--ua-shadow' => '0 22px 46px rgba(122, 67, 18, 0.16)',
                ],
            ],
            'atlantic' => [
                'label' => 'Atlantic',
                'description' => 'Deep ocean blues with bright panels for calm scheduling dashboards.',
                'css_variables' => [
                    '--ua-page-background' => 'linear-gradient(135deg, #dceefd 0%, #bddbf3 45%, #86b7d8 100%)',
                    '--ua-card-background' => '#f8fcff',
                    '--ua-card-muted-background' => 'rgba(255, 255, 255, 0.58)',
                    '--ua-border' => 'rgba(8, 76, 117, 0.18)',
                    '--ua-text' => '#08253a',
                    '--ua-muted-text' => '#27506b',
                    '--ua-accent' => '#0b6ea8',
                    '--ua-accent-contrast' => '#f8fcff',
                    '--ua-pill-background' => 'rgba(11, 110, 168, 0.12)',
                    '--ua-shadow' => '0 22px 46px rgba(10, 74, 112, 0.16)',
                ],
            ],
            'evergreen' => [
                'label' => 'Evergreen',
                'description' => 'Forest greens and cream panels for a grounded, premium feel.',
                'css_variables' => [
                    '--ua-page-background' => 'linear-gradient(135deg, #edf6e3 0%, #dbeccb 45%, #b4d1a6 100%)',
                    '--ua-card-background' => '#fbfdf8',
                    '--ua-card-muted-background' => 'rgba(255, 255, 255, 0.56)',
                    '--ua-border' => 'rgba(35, 86, 46, 0.18)',
                    '--ua-text' => '#17331d',
                    '--ua-muted-text' => '#406347',
                    '--ua-accent' => '#2f6b3c',
                    '--ua-accent-contrast' => '#fbfdf8',
                    '--ua-pill-background' => 'rgba(47, 107, 60, 0.12)',
                    '--ua-shadow' => '0 22px 46px rgba(35, 78, 44, 0.15)',
                ],
            ],
        ],
    ],
];
