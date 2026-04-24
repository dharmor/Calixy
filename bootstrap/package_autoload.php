<?php

require_once __DIR__ . '/standalone_helpers.php';

spl_autoload_register(static function (string $class): void {
    $prefix = 'UnifiedAppointments\\';

    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = str_replace('\\', DIRECTORY_SEPARATOR, substr($class, strlen($prefix)));
    $path = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . $relative . '.php';

    if (is_file($path)) {
        require_once $path;
    }
});
