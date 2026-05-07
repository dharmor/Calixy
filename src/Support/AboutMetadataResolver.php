<?php

declare(strict_types=1);

namespace UnifiedAppointments\Support;

use Composer\InstalledVersions;
use Throwable;

final class AboutMetadataResolver
{
    public static function resolveName(?string $configuredName = null, ?string $fallbackName = null): string
    {
        foreach ([$configuredName, $fallbackName, 'calixy'] as $candidate) {
            $candidate = self::stringOrNull($candidate);

            if ($candidate !== null) {
                return $candidate;
            }
        }

        return 'calixy';
    }

    public static function resolveVersion(
        ?string $configuredVersion,
        string $packageRoot,
        ?string $packageName = null,
    ): string {
        $configuredVersion = self::stringOrNull($configuredVersion);

        if ($configuredVersion !== null) {
            return $configuredVersion;
        }

        foreach (self::installedVersions($packageName) as $version) {
            return $version;
        }

        return self::composerJsonVersion($packageRoot) ?? 'unknown';
    }

    /**
     * @return array<int, string>
     */
    private static function installedVersions(?string $packageName): array
    {
        if (!class_exists(InstalledVersions::class)) {
            return [];
        }

        $versions = [];

        $packageName = self::stringOrNull($packageName);

        if ($packageName !== null) {
            try {
                if (InstalledVersions::isInstalled($packageName)) {
                    $packageVersion = self::stringOrNull(InstalledVersions::getPrettyVersion($packageName));

                    if ($packageVersion !== null) {
                        $versions[] = $packageVersion;
                    }
                }
            } catch (Throwable) {
                // Fall back to composer.json.
            }
        }

        try {
            $root = InstalledVersions::getRootPackage();
            $prettyVersion = self::stringOrNull($root['pretty_version'] ?? null);

            if ($prettyVersion !== null) {
                $versions[] = $prettyVersion;
            }
        } catch (Throwable) {
            // Fall back to composer.json.
        }

        return array_values(array_unique($versions));
    }

    private static function composerJsonVersion(string $packageRoot): ?string
    {
        $composerPath = rtrim($packageRoot, '\\/') . DIRECTORY_SEPARATOR . 'composer.json';

        if (!is_file($composerPath) || !is_readable($composerPath)) {
            return null;
        }

        $decoded = json_decode((string) file_get_contents($composerPath), true);

        if (!is_array($decoded)) {
            return null;
        }

        return self::stringOrNull($decoded['version'] ?? null);
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
