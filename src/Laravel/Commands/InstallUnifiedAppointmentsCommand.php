<?php

namespace UnifiedAppointments\Laravel\Commands;

use UnifiedAppointments\Config\UnifiedAppointmentsConfig;
use UnifiedAppointments\Services\AppointmentScheduler;

class InstallUnifiedAppointmentsCommand extends \Illuminate\Console\Command
{
    protected $signature = 'unified-appointments:install
        {--publish-config : Publish the package configuration file}
        {--publish-views : Publish the package views for customization}';

    protected $description = 'Explicitly create Unified Appointments tables for Pro or non-SQLite deployments.';

    public function handle(AppointmentScheduler $scheduler, UnifiedAppointmentsConfig $config): int
    {
        if ($this->option('publish-config')) {
            $this->callSilently('vendor:publish', [
                '--tag' => 'unified-appointments-config',
                '--force' => true,
            ]);
        }

        if ($this->option('publish-views')) {
            $this->callSilently('vendor:publish', [
                '--tag' => 'unified-appointments-views',
                '--force' => true,
            ]);
        }

        if ($config->shouldAutoBootstrap()) {
            $this->components->info('Startup edition is using SQLite auto-bootstrap. This install command is optional for the starter setup and remains available for Pro and alternate database deployments.');
        }

        if (!is_dir($config->databaseLibraryPath)) {
            $this->components->error(sprintf(
                'Unified Databases library not found at "%s".',
                $config->databaseLibraryPath,
            ));

            return self::FAILURE;
        }

        $this->prepareSqliteDatabaseIfNeeded($config);
        $scheduler->install();

        $this->components->info('Unified Appointments tables are ready.');
        $this->components->twoColumnDetail('Edition', $config->edition);
        $this->components->twoColumnDetail('Laravel connection', (string) ($config->connection ?? 'default'));
        $this->components->twoColumnDetail('Driver', $config->driver);
        $this->components->twoColumnDetail('Bootstrap mode', $config->shouldAutoBootstrap() ? 'automatic SQLite startup' : 'manual / Pro');
        $this->components->twoColumnDetail('Database library', $config->databaseLibraryPath);
        $this->components->twoColumnDetail('Database target', (string) ($config->database ?: $config->host));
        $this->components->twoColumnDetail('Route prefix', (string) config('unified-appointments.routes.prefix', 'unified-appointments'));
        $this->components->twoColumnDetail('Theme', (string) config('unified-appointments.ui.theme', 'sunrise'));
        $this->components->twoColumnDetail('Config file', config_path('unified-appointments.php'));

        if (class_exists(\Filament\Panel::class)) {
            $this->newLine();
            $this->components->info('Filament is installed. Register UnifiedAppointmentsPlugin::make() in your panel to expose the package page.');
        }

        return self::SUCCESS;
    }

    private function prepareSqliteDatabaseIfNeeded(UnifiedAppointmentsConfig $config): void
    {
        if ($config->driver !== 'sqlite') {
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
    }
}
