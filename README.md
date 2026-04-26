# Calixy

`Calixy` is a Laravel-compatible appointment and availability package that uses the Unified Databases connection layer instead of Laravel's native database manager.

The package now ships with two setup modes:

- `startup` edition: SQLite, auto-bootstrap, no manual install required
- `pro` edition: explicit install flow for SQL Server, MySQL, PostgreSQL, or custom database wiring

It is designed for:

- staff and resource availability
- buffer times and blackout dates
- recurring schedules
- rescheduling and cancellations
- waitlists
- deposits and no-show fees
- timezone-safe booking rules
- multi-location and multi-tenant filtering
- system config storage for the starter app timezone
- company branding with a customizable company name and logo URL or uploaded logo
- starter pages for Services, Locations, Team, Notifications, Google, and Microsoft


It also ships with built-in UI themes:

- `sunrise`
- `atlantic`
- `evergreen`

The package root now also includes a Laravel-style `public/index.php` entry point so there is a clear web start location inside the package source.
When you open that starting page in the startup edition, it boots a fully functional SQLite-backed application with a left-side navigation shell, starter service data, a calendar view, weekly availability, booking, rescheduling, cancellations, blackout dates, and waitlist management.
The startup edition also seeds a timezone catalog into SQLite from a local snapshot derived from Easy!Appointments `Timezones.php` so the selected system timezone is stored in the database instead of only in config.
Locations and Team are now wired into the starter context as live filters, so the calendar, booking flow, appointments, waitlist, and setup forms can all be scoped to the selected location and booking owner.

## Install

Start from an existing Laravel app.

For the default startup edition, just require the package and let it boot:

```bash
composer require calixy/unified-appointments
```

On first boot, the package will create and use:

`database/unified-appointments.sqlite`

No `php artisan unified-appointments:install` step is required for that startup SQLite flow. The startup page is intended to work immediately from `public/index.php`.

If you want Pro edition behavior or another database engine, publish the config and run the explicit install command:

```bash
composer require calixy/unified-appointments
php artisan vendor:publish --tag=unified-appointments-config
php artisan unified-appointments:install
```

Optional:

```bash
php artisan vendor:publish --tag=unified-appointments-views
```

The package also registers Laravel API routes by default under:

`/unified-appointments`

The startup defaults are now:

- default to `startup` edition
- use SQLite at `database/unified-appointments.sqlite`
- auto-create the SQLite file and schema on first boot
- seed the initial application so the starting page is usable immediately
- reserve the install command for Pro and alternate database setups

## Example Config

```php
return [
    'database_library_path' => 'C:\\Apache24\\htdocs\\Unified Databases',
    'edition' => env('UNIFIED_APPOINTMENTS_EDITION', 'startup'),
    'startup' => [
        'auto_bootstrap' => env('UNIFIED_APPOINTMENTS_AUTO_BOOTSTRAP', true),
        'database' => env('UNIFIED_APPOINTMENTS_STARTUP_DATABASE', database_path('unified-appointments.sqlite')),
    ],
    'connection' => env('UNIFIED_APPOINTMENTS_CONNECTION'),
    'driver' => env('UNIFIED_APPOINTMENTS_DRIVER'),
    'host' => env('UNIFIED_APPOINTMENTS_HOST'),
    'username' => env('UNIFIED_APPOINTMENTS_USERNAME'),
    'password' => env('UNIFIED_APPOINTMENTS_PASSWORD'),
    'database' => env('UNIFIED_APPOINTMENTS_DATABASE'),
    'port' => env('UNIFIED_APPOINTMENTS_PORT'),
    'table_prefix' => 'ua_',
    'app_timezone' => 'America/New_York',
    'routes' => [
        'enabled' => true,
        'prefix' => 'unified-appointments',
        'name_prefix' => 'unified-appointments.',
        'middleware' => ['api'],
    ],
    'ui' => [
        'theme' => 'atlantic',
        'allow_theme_switcher' => true,
        'donationware_url' => env('CALIXY_DONATIONWARE_URL', 'https://github.com/sponsors/YOUR_ACCOUNT'),
        'themes' => [
            'studio' => [
                'label' => 'Studio',
                'description' => 'Custom brand theme for a polished booking dashboard.',
                'css_variables' => [
                    '--ua-page-background' => 'linear-gradient(135deg, #f4efe5 0%, #ead9c2 45%, #d2b48c 100%)',
                    '--ua-card-background' => '#fffaf3',
                    '--ua-card-muted-background' => 'rgba(255, 255, 255, 0.58)',
                    '--ua-border' => 'rgba(120, 73, 28, 0.18)',
                    '--ua-text' => '#36210c',
                    '--ua-muted-text' => '#6b4a26',
                    '--ua-accent' => '#9f5f22',
                    '--ua-accent-contrast' => '#fffaf3',
                    '--ua-pill-background' => 'rgba(159, 95, 34, 0.12)',
                    '--ua-shadow' => '0 22px 46px rgba(100, 67, 30, 0.14)',
                ],
            ],
        ],
    ],
];
```

## Themes

Theme support is config-driven. Set a default theme:

```env
UNIFIED_APPOINTMENTS_THEME=evergreen
CALIXY_DONATIONWARE_URL=https://github.com/sponsors/YOUR_ACCOUNT
```

Or add custom named themes in `config/unified-appointments.php`.

## Laravel Components

The package now includes:

- auto-discovered service provider
- `Calixy` facade alias
- `UnifiedAppointments` facade alias
- startup SQLite auto-bootstrap
- explicit install command for alternate databases
- package-level overrides for custom connections
- configurable API route registration
- JSON controllers for services, availability, appointments, and waitlists
- publishable views
- `php artisan about` integration

## Override Example

If you want Calixy to use Pro mode or a different database, override it explicitly:

```env
UNIFIED_APPOINTMENTS_CONNECTION=sqlsrv
UNIFIED_APPOINTMENTS_DRIVER=mssql
UNIFIED_APPOINTMENTS_HOST=127.0.0.1
UNIFIED_APPOINTMENTS_PORT=1433
UNIFIED_APPOINTMENTS_DATABASE=unified_appointments
UNIFIED_APPOINTMENTS_USERNAME=unified_user
UNIFIED_APPOINTMENTS_PASSWORD=ChangeMeNow!
```

Then run:

```bash
php artisan unified-appointments:install
```

### Route Endpoints

- `GET /unified-appointments/slots`
- `POST /unified-appointments/services`
- `POST /unified-appointments/availability-rules`
- `POST /unified-appointments/availability-exceptions`
- `POST /unified-appointments/appointments`
- `POST /unified-appointments/appointments/{appointment}/reschedule`
- `POST /unified-appointments/appointments/{appointment}/cancel`
- `POST /unified-appointments/waitlist`

### Facade Example

```php
use UnifiedAppointments\DTO\SlotSearchData;
use UnifiedAppointments\Laravel\Facades\Calixy;

$slots = Calixy::findAvailableSlots(new SlotSearchData(
    serviceId: 1,
    windowStart: new DateTimeImmutable('2026-04-27 00:00:00', new DateTimeZone('America/New_York')),
    windowEnd: new DateTimeImmutable('2026-04-27 23:59:59', new DateTimeZone('America/New_York')),
    staffId: 'staff-1',
    timezone: 'America/New_York',
));
```

## Core Usage

```php
use UnifiedAppointments\Config\UnifiedAppointmentsConfig;
use UnifiedAppointments\Database\SchemaManager;
use UnifiedAppointments\Database\UnifiedDatabaseConnector;
use UnifiedAppointments\Repositories\AppointmentRepository;
use UnifiedAppointments\Services\AppointmentScheduler;

$config = UnifiedAppointmentsConfig::fromArray([
    'database_library_path' => 'Unified Databases',
    'driver' => 'sqlite',
    'host' => __DIR__ . '/storage/appointments.sqlite',
    'database' => __DIR__ . '/storage/appointments.sqlite',
]);

$connector = new UnifiedDatabaseConnector($config);
$schema = new SchemaManager($connector, $config);
$repository = new AppointmentRepository($connector, $config);
$scheduler = new AppointmentScheduler($repository, $schema);

$scheduler->install();
```

## Smoke Test

Run the included smoke test from the package root:

```bash
php tests/smoke.php
```

