<?php

if (!function_exists('env')) {
    /**
     * Env.
     */
    function env(string $key, mixed $default = null): mixed
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

        if ($value === false || $value === null) {
            return $default;
        }

        if (is_string($value)) {
            return match (strtolower($value)) {
                'true', '(true)' => true,
                'false', '(false)' => false,
                'null', '(null)' => null,
                'empty', '(empty)' => '',
                default => $value,
            };
        }

        return $value;
    }
}

if (!function_exists('config')) {
    /**
     * Config.
     */
    function config(string $key, mixed $default = null): mixed
    {
        return $default;
    }
}

if (!function_exists('database_path')) {
    /**
     * Database_path.
     */
    function database_path(string $path = ''): string
    {
        $base = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'database';

        if ($path === '') {
            return $base;
        }

        return $base . DIRECTORY_SEPARATOR . ltrim($path, '\\/');
    }
}

