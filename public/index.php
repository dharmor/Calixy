<?php

declare(strict_types=1);

$autoloadPath = dirname(__DIR__) . '/vendor/autoload.php';
$bootstrapPath = dirname(__DIR__) . '/bootstrap/app.php';
$packageAutoloadPath = dirname(__DIR__) . '/bootstrap/package_autoload.php';

if (is_file($autoloadPath) && is_file($bootstrapPath)) {
    require $autoloadPath;

    $app = require $bootstrapPath;

    if ($app instanceof \Illuminate\Foundation\Application && class_exists(\Illuminate\Http\Request::class)) {
        $app->handleRequest(\Illuminate\Http\Request::capture());

        return;
    }
}

if (!is_file($packageAutoloadPath)) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Calixy package autoload file is missing.';

    return;
}

require $packageAutoloadPath;

$app = new \UnifiedAppointments\Starter\StartupApplication(dirname(__DIR__));
$app->run();

