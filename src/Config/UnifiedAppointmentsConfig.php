<?php

namespace UnifiedAppointments\Config;

final readonly class UnifiedAppointmentsConfig
{
    /**
     * Create a new instance.
     */
    public function __construct(
        public string $databaseLibraryPath,
        public string $edition = 'startup',
        public bool $autoBootstrap = true,
        public ?string $connection = null,
        public string $driver = 'sqlite',
        public string $host = '',
        public string $username = '',
        public string $password = '',
        public ?string $database = null,
        public ?int $port = null,
        public string $tablePrefix = 'ua_',
        public string $appTimezone = 'UTC',
    ) {
    }

    /**
     * @param array<string, mixed> $config
     */
    public static function fromArray(array $config): self
    {
        $database = isset($config['database']) ? self::resolveFilesystemPath((string) $config['database']) : null;
        $driver = self::normalizeDriver((string) ($config['driver'] ?? 'sqlite'));
        $host = (string) ($config['host'] ?? '');

        if ($driver === 'sqlite' && $host === '' && $database !== null) {
            $host = $database;
        } elseif ($driver === 'sqlite' && $host !== '') {
            $host = (string) self::resolveFilesystemPath($host);
        }

        return new self(
            databaseLibraryPath: (string) self::resolveFilesystemPath(
                (string) ($config['database_library_path'] ?? 'C:\\Apache24\\htdocs\\Unified Databases'),
            ),
            edition: strtolower((string) ($config['edition'] ?? 'startup')),
            autoBootstrap: self::boolValue($config['auto_bootstrap'] ?? true),
            connection: isset($config['connection']) && $config['connection'] !== '' ? (string) $config['connection'] : null,
            driver: $driver,
            host: $host,
            username: (string) ($config['username'] ?? ''),
            password: (string) ($config['password'] ?? ''),
            database: $database,
            port: isset($config['port']) && $config['port'] !== '' ? (int) $config['port'] : null,
            tablePrefix: (string) ($config['table_prefix'] ?? 'ua_'),
            appTimezone: (string) ($config['app_timezone'] ?? 'UTC'),
        );
    }

    /**
     * @param array<string, mixed> $config
     * @param array<string, mixed> $laravelConnection
     */
    public static function fromLaravelConfig(
        array $config,
        array $laravelConnection,
        ?string $connectionName = null,
    ): self {
        /** @var array<string, mixed> $startup */
        $startup = is_array($config['startup'] ?? null) ? $config['startup'] : [];
        $edition = strtolower((string) ($config['edition'] ?? 'startup'));
        $explicitConnection = self::stringOrNull($config['connection'] ?? null);
        $explicitDriver = self::normalizeNullableDriver($config['driver'] ?? null);
        $explicitHost = self::stringOrNull($config['host'] ?? null);
        $explicitUsername = self::stringOrNull($config['username'] ?? null);
        $explicitPassword = self::stringOrNull($config['password'] ?? null);
        $explicitDatabase = self::stringOrNull($config['database'] ?? null);
        $explicitPort = self::intOrNull($config['port'] ?? null);
        $startupDatabase = self::resolveFilesystemPath(self::stringOrNull($startup['database'] ?? null));
        $autoBootstrap = self::boolValue($startup['auto_bootstrap'] ?? true);
        $useStartupDefaults = $edition === 'startup'
            && $explicitConnection === null
            && $explicitDriver === null
            && $explicitHost === null
            && $explicitUsername === null
            && $explicitPassword === null
            && $explicitDatabase === null
            && $explicitPort === null;

        if ($useStartupDefaults) {
            return new self(
                databaseLibraryPath: (string) self::resolveFilesystemPath(
                    (string) ($config['database_library_path'] ?? 'C:\\Apache24\\htdocs\\Unified Databases'),
                ),
                edition: $edition,
                autoBootstrap: $autoBootstrap,
                connection: null,
                driver: 'sqlite',
                host: (string) $startupDatabase,
                username: '',
                password: '',
                database: $startupDatabase,
                port: null,
                tablePrefix: (string) ($config['table_prefix'] ?? 'ua_'),
                appTimezone: (string) ($config['app_timezone'] ?? 'UTC'),
            );
        }

        $resolvedConnection = $explicitConnection ?? self::stringOrNull($connectionName);
        $resolvedDriver = $explicitDriver
            ?? self::mapLaravelDriver(self::stringOrNull($laravelConnection['driver'] ?? $connectionName) ?? 'sqlite');

        $resolvedDatabase = $explicitDatabase ?? self::stringOrNull($laravelConnection['database'] ?? null);

        if ($resolvedDriver === 'sqlite') {
            $resolvedDatabase = self::resolveFilesystemPath($resolvedDatabase);
        }

        if ($resolvedDriver === 'sqlite' && $resolvedDatabase === null) {
            $resolvedDatabase = $startupDatabase;
        }

        $resolvedHost = $explicitHost
            ?? self::stringOrNull($laravelConnection['host'] ?? null)
            ?? ($resolvedDriver === 'sqlite' ? (string) $resolvedDatabase : '');

        if ($resolvedDriver === 'sqlite' && $resolvedHost !== null && $resolvedHost !== '') {
            $resolvedHost = self::resolveFilesystemPath($resolvedHost);
        }

        return new self(
            databaseLibraryPath: (string) self::resolveFilesystemPath(
                (string) ($config['database_library_path'] ?? 'C:\\Apache24\\htdocs\\Unified Databases'),
            ),
            edition: $edition,
            autoBootstrap: $edition === 'startup' && $resolvedDriver === 'sqlite' ? $autoBootstrap : false,
            connection: $resolvedConnection,
            driver: $resolvedDriver,
            host: $resolvedHost,
            username: $explicitUsername
                ?? self::stringOrNull($laravelConnection['username'] ?? null)
                ?? '',
            password: $explicitPassword
                ?? self::stringOrNull($laravelConnection['password'] ?? null)
                ?? '',
            database: $resolvedDatabase,
            port: $explicitPort
                ?? self::intOrNull($laravelConnection['port'] ?? null),
            tablePrefix: (string) ($config['table_prefix'] ?? 'ua_'),
            appTimezone: (string) ($config['app_timezone'] ?? 'UTC'),
        );
    }

    /**
     * Table.
     */
    public function table(string $name): string
    {
        return $this->tablePrefix . $name;
    }

    /**
     * Should Auto Bootstrap.
     */
    public function shouldAutoBootstrap(): bool
    {
        return $this->edition === 'startup' && $this->driver === 'sqlite' && $this->autoBootstrap;
    }

    /**
     * Map Laravel Driver.
     */
    private static function mapLaravelDriver(string $driver): string
    {
        return self::normalizeDriver($driver);
    }

    /**
     * String Or Null.
     */
    private static function stringOrNull(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (string) $value;
    }

    /**
     * Int Or Null.
     */
    private static function intOrNull(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    /**
     * Bool Value.
     */
    private static function boolValue(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            return !in_array(strtolower($value), ['0', 'false', 'off', 'no'], true);
        }

        return (bool) $value;
    }

    /**
     * Normalize Nullable Driver.
     */
    private static function normalizeNullableDriver(mixed $value): ?string
    {
        $driver = self::stringOrNull($value);

        if ($driver === null) {
            return null;
        }

        return self::normalizeDriver($driver);
    }

    /**
     * Normalize Driver.
     */
    private static function normalizeDriver(string $driver): string
    {
        return match (strtolower(trim($driver))) {
            '', 'sqlite' => 'sqlite',
            'mysql', 'mariadb' => 'mysql',
            'pgsql', 'postgres', 'postgresql', 'postgesql' => 'postgres',
            'sqlsrv', 'sqlserver', 'mssql' => 'mssql',
            default => strtolower(trim($driver)),
        };
    }

    /**
     * Resolve Filesystem Path.
     */
    private static function resolveFilesystemPath(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return $path;
        }

        if ($path === ':memory:' || str_starts_with($path, 'file:')) {
            return $path;
        }

        if (self::isAbsolutePath($path) || !function_exists('base_path')) {
            return $path;
        }

        return base_path($path);
    }

    /**
     * Is Absolute Path.
     */
    private static function isAbsolutePath(string $path): bool
    {
        return preg_match('/^(?:[A-Za-z]:[\\\\\\/]|\\\\\\\\|\\/)/', $path) === 1;
    }
}

