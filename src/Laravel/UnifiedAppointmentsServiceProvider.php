<?php

namespace UnifiedAppointments\Laravel;

use Illuminate\Foundation\Console\AboutCommand;
use Illuminate\Support\Facades\Route;
use UnifiedAppointments\Config\UnifiedAppointmentsConfig;
use UnifiedAppointments\Database\SchemaManager;
use UnifiedAppointments\Database\UnifiedDatabaseConnector;
use UnifiedAppointments\Laravel\Commands\InstallUnifiedAppointmentsCommand;
use UnifiedAppointments\Repositories\AppointmentRepository;
use UnifiedAppointments\Services\AppointmentScheduler;
use UnifiedAppointments\Support\AboutMetadataResolver;
use UnifiedAppointments\Themes\ThemeManager;

/**
 * UnifiedAppointmentsServiceProvider.
 */
class UnifiedAppointmentsServiceProvider extends \Illuminate\Support\ServiceProvider
{
    private static bool $startupBootstrapped = false;

    /**
     * Register.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/unified-appointments.php', 'unified-appointments');

        $this->app->singleton(UnifiedAppointmentsConfig::class, function ($app): UnifiedAppointmentsConfig {
            /** @var array<string, mixed> $packageConfig */
            $packageConfig = $app['config']->get('unified-appointments', []);
            $configuredConnection = $packageConfig['connection'] ?? null;
            $connectionName = is_string($configuredConnection) && $configuredConnection !== ''
                ? $configuredConnection
                : (string) $app['config']->get('database.default', 'sqlite');
            /** @var array<string, mixed> $laravelConnection */
            $laravelConnection = $app['config']->get('database.connections.' . $connectionName, []);

            return UnifiedAppointmentsConfig::fromLaravelConfig(
                $packageConfig,
                $laravelConnection,
                $connectionName,
            );
        });

        $this->app->singleton(ThemeManager::class, function ($app): ThemeManager {
            /** @var array<string, mixed> $config */
            $config = $app['config']->get('unified-appointments', []);

            return new ThemeManager($config);
        });

        $this->app->singleton(UnifiedDatabaseConnector::class, fn ($app): UnifiedDatabaseConnector => new UnifiedDatabaseConnector(
            $app->make(UnifiedAppointmentsConfig::class),
        ));

        $this->app->singleton(SchemaManager::class, fn ($app): SchemaManager => new SchemaManager(
            $app->make(UnifiedDatabaseConnector::class),
            $app->make(UnifiedAppointmentsConfig::class),
        ));

        $this->app->singleton(AppointmentRepository::class, fn ($app): AppointmentRepository => new AppointmentRepository(
            $app->make(UnifiedDatabaseConnector::class),
            $app->make(UnifiedAppointmentsConfig::class),
        ));

        $this->app->singleton(AppointmentScheduler::class, fn ($app): AppointmentScheduler => new AppointmentScheduler(
            $app->make(AppointmentRepository::class),
            $app->make(SchemaManager::class),
        ));

        $this->app->alias(AppointmentScheduler::class, 'unified-appointments');
    }

    /**
     * Boot.
     */
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'unified-appointments');
        $this->bootstrapStartupEdition();
        $this->registerAboutDetails();
        $this->bootRoutes();

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../config/unified-appointments.php' => config_path('unified-appointments.php'),
            ], 'unified-appointments-config');

            $this->publishes([
                __DIR__ . '/../../resources/views' => resource_path('views/vendor/unified-appointments'),
            ], 'unified-appointments-views');

            $this->commands([
                InstallUnifiedAppointmentsCommand::class,
            ]);
        }
    }

    /**
     * Bootstrap Startup Edition.
     */
    private function bootstrapStartupEdition(): void
    {
        if (self::$startupBootstrapped) {
            return;
        }

        $config = $this->app->make(UnifiedAppointmentsConfig::class);

        if (!$config->shouldAutoBootstrap()) {
            return;
        }

        $databasePath = $config->database ?: $config->host;

        if ($databasePath === '') {
            return;
        }

        $directory = dirname($databasePath);

        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        if (!is_file($databasePath)) {
            touch($databasePath);
        }

        $schema = $this->app->make(SchemaManager::class);

        if (!$schema->isInstalled()) {
            $schema->install();
        }

        self::$startupBootstrapped = true;
    }

    /**
     * Boot Routes.
     */
    private function bootRoutes(): void
    {
        if ($this->app->routesAreCached()) {
            return;
        }

        /** @var array<string, mixed> $routes */
        $routes = $this->app['config']->get('unified-appointments.routes', []);

        if (!(bool) ($routes['enabled'] ?? true)) {
            return;
        }

        $middleware = $routes['middleware'] ?? ['api'];

        if (!is_array($middleware)) {
            $middleware = [$middleware];
        }

        Route::middleware($middleware)
            ->prefix((string) ($routes['prefix'] ?? 'unified-appointments'))
            ->as((string) ($routes['name_prefix'] ?? 'unified-appointments.'))
            ->group(function (): void {
                require __DIR__ . '/../../routes/api.php';
            });

        $webMiddleware = $routes['web_middleware'] ?? ['web'];

        if (!is_array($webMiddleware)) {
            $webMiddleware = [$webMiddleware];
        }

        Route::middleware($webMiddleware)
            ->prefix((string) ($routes['prefix'] ?? 'unified-appointments'))
            ->as((string) ($routes['name_prefix'] ?? 'unified-appointments.'))
            ->group(function (): void {
                require __DIR__ . '/../../routes/web.php';
            });
    }

    /**
     * Register About Details.
     */
    private function registerAboutDetails(): void
    {
        if (!class_exists(AboutCommand::class)) {
            return;
        }

        $config = $this->app->make(UnifiedAppointmentsConfig::class);
        $configuredName = config('unified-appointments.ui.application_name');
        $aboutName = AboutMetadataResolver::resolveName(
            is_string($configuredName) ? $configuredName : null,
            is_string(config('app.name')) ? config('app.name') : 'calixy',
        );

        AboutCommand::add($aboutName, fn (): array => [
            'Edition' => $config->edition,
            'Laravel Connection' => (string) ($config->connection ?? config('database.default', 'sqlite')),
            'Driver' => $config->driver,
            'Auto Bootstrap' => $config->shouldAutoBootstrap() ? 'enabled' : 'manual',
            'Route Prefix' => (string) config('unified-appointments.routes.prefix', 'unified-appointments'),
            'Theme' => (string) config('unified-appointments.ui.theme', 'sunrise'),
            'Database Library' => $config->databaseLibraryPath,
        ]);
    }

    /**
     * Package Version.
     */
    public static function packageVersion(): string
    {
        $configuredVersion = function_exists('config') ? config('unified-appointments.ui.version') : null;

        return AboutMetadataResolver::resolveVersion(
            is_string($configuredVersion) ? $configuredVersion : null,
            dirname(__DIR__, 2),
            'calixy/unified-appointments',
        );
    }
}


