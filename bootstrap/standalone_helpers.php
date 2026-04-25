<?php

if (!function_exists('load_standalone_env')) {
    /**
     * Load_standalone_env.
     */
    function load_standalone_env(string $path): void
    {
        if (!is_file($path) || !is_readable($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES);

        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            if (str_starts_with($line, 'export ')) {
                $line = trim(substr($line, 7));
            }

            if (!str_contains($line, '=')) {
                continue;
            }

            [$name, $value] = explode('=', $line, 2);
            $name = trim($name);

            if ($name === '' || str_contains($name, ' ')) {
                continue;
            }

            $value = trim($value);

            if ($value !== '' && (
                (str_starts_with($value, '"') && str_ends_with($value, '"'))
                || (str_starts_with($value, "'") && str_ends_with($value, "'"))
            )) {
                $quote = $value[0];
                $value = substr($value, 1, -1);

                if ($quote === '"') {
                    $value = strtr($value, [
                        '\\"' => '"',
                        '\\n' => "\n",
                        '\\r' => "\r",
                        '\\t' => "\t",
                        '\\\\' => '\\',
                    ]);
                }
            }

            if (array_key_exists($name, $_ENV) || array_key_exists($name, $_SERVER) || getenv($name) !== false) {
                continue;
            }

            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
            putenv($name . '=' . $value);
        }
    }
}

load_standalone_env(dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env');

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

if (!function_exists('base_path')) {
    /**
     * Base_path.
     */
    function base_path(string $path = ''): string
    {
        $base = dirname(__DIR__);

        if ($path === '') {
            return $base;
        }

        return $base . DIRECTORY_SEPARATOR . ltrim($path, '\\/');
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

