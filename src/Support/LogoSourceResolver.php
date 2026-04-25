<?php

declare(strict_types=1);

namespace UnifiedAppointments\Support;

final class LogoSourceResolver
{
    /**
     * @param array<int, string> $searchRoots
     */
    public static function resolve(?string $source, array $searchRoots = []): ?string
    {
        $source = self::stringOrNull($source);

        if ($source === null) {
            return null;
        }

        if (str_starts_with($source, 'data:image/') || preg_match('#^(https?:)?//#i', $source) === 1) {
            return $source;
        }

        $resolvedPath = self::resolveLocalPath($source, $searchRoots);

        if ($resolvedPath === null) {
            return $source;
        }

        return self::fileToDataUri($resolvedPath) ?? $source;
    }

    /**
     * @param array<int, array{0:string, 1:string}> $candidates
     */
    public static function firstAvailableDataUri(array $candidates): ?string
    {
        foreach ($candidates as [$path, $mimeType]) {
            if (!is_string($path) || $path === '' || !is_file($path) || !is_readable($path)) {
                continue;
            }

            $contents = @file_get_contents($path);

            if ($contents === false) {
                continue;
            }

            return 'data:' . $mimeType . ';base64,' . base64_encode($contents);
        }

        return null;
    }

    /**
     * @param array<int, string> $searchRoots
     */
    private static function resolveLocalPath(string $source, array $searchRoots): ?string
    {
        $normalizedSource = self::normalizedSourcePath($source);
        $candidates = [];

        if (preg_match('/^[A-Za-z]:[\\\\\\/]/', $normalizedSource) === 1 || str_starts_with($normalizedSource, '\\\\')) {
            $candidates[] = $normalizedSource;
        }

        $trimmedSource = ltrim($normalizedSource, '\\/');

        foreach ($searchRoots as $root) {
            $root = self::stringOrNull($root);

            if ($root === null) {
                continue;
            }

            $candidates[] = rtrim($root, '\\/') . DIRECTORY_SEPARATOR . $trimmedSource;
        }

        foreach (array_values(array_unique($candidates)) as $candidate) {
            if (is_file($candidate) && is_readable($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private static function normalizedSourcePath(string $source): string
    {
        if (preg_match('#^(https?:)?//#i', $source) === 1) {
            $parsedPath = parse_url($source, PHP_URL_PATH);

            if (is_string($parsedPath) && $parsedPath !== '') {
                $source = $parsedPath;
            }
        }

        return str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $source);
    }

    private static function fileToDataUri(string $path): ?string
    {
        $mimeType = self::mimeTypeFor($path);

        if ($mimeType === null) {
            return null;
        }

        $contents = @file_get_contents($path);

        if ($contents === false) {
            return null;
        }

        return 'data:' . $mimeType . ';base64,' . base64_encode($contents);
    }

    private static function mimeTypeFor(string $path): ?string
    {
        return match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'gif' => 'image/gif',
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'svg' => 'image/svg+xml',
            'webp' => 'image/webp',
            default => null,
        };
    }

    private static function stringOrNull(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
