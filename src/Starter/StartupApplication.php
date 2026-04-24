<?php

declare(strict_types=1);

namespace UnifiedAppointments\Starter;

use DateTimeImmutable;
use DateTimeZone;
use RuntimeException;
use Throwable;
use UnifiedAppointments\Config\UnifiedAppointmentsConfig;
use UnifiedAppointments\Database\SchemaManager;
use UnifiedAppointments\Database\UnifiedDatabaseConnector;
use UnifiedAppointments\DTO\AvailabilityExceptionData;
use UnifiedAppointments\DTO\AvailabilityRuleData;
use UnifiedAppointments\DTO\BookAppointmentData;
use UnifiedAppointments\DTO\ServiceData;
use UnifiedAppointments\DTO\SlotSearchData;
use UnifiedAppointments\DTO\WaitlistEntryData;
use UnifiedAppointments\Notifications\SmtpEmailDispatcher;
use UnifiedAppointments\Repositories\AppointmentRepository;
use UnifiedAppointments\Services\AppointmentScheduler;
use UnifiedAppointments\Themes\ThemeManager;

final class StartupApplication
{
    private const DEFAULT_OWNER_TYPE = 'staff';
    private const DEFAULT_OWNER_ID = 'main-team';
    private const DEFAULT_LOCATION_KEY = 'main-office';
    private const SYSTEM_OWNER_TYPE = 'system';
    private const SYSTEM_OWNER_ID = 'starter-app';
    private const STARTUP_PAGES = [
        'dashboard',
        'calendar',
        'booking',
        'appointments',
        'waitlist',
        'services',
        'locations',
        'team',
        'notifications',
        'google',
        'microsoft',
    ];

    private array $theme = [];

    private array $rawConfig = [];

    private string $themeKey = 'sunrise';

    private string $appTimezone = 'UTC';

    private string $companyName = 'Your Company';

    private ?string $companyLogoUrl = null;

    private ThemeManager $themeManager;

    private UnifiedAppointmentsConfig $config;

    private AppointmentRepository $repository;

    private AppointmentScheduler $scheduler;

    private SmtpEmailDispatcher $emailDispatcher;

    private SchemaManager $schema;

    public function __construct(private readonly string $packageRoot)
    {
    }

    public function run(): void
    {
        $this->startSession();

        try {
            $this->loadConfig();
            $this->bootTheme();
            $this->bootRuntime();
            $this->seedStarterReferenceData();
            $this->loadRuntimeTimezone();
            $this->loadRuntimeBranding();
            $this->seedDefaults();
            $this->processDueEmailReminders();

            if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'POST') {
                $this->handlePost();

                return;
            }

            $this->render($this->buildState());
        } catch (Throwable $exception) {
            $this->renderError($exception);
        }
    }

    private function loadConfig(): void
    {
        $configPath = $this->packageRoot . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'unified-appointments.php';
        $config = require $configPath;

        if (!is_array($config)) {
            throw new RuntimeException('The startup configuration file is invalid.');
        }

        $this->rawConfig = $config;
    }

    private function bootTheme(): void
    {
        $this->themeManager = new ThemeManager($this->rawConfig);
        $requestedTheme = $this->stringOrNull($_GET[$this->themeManager->themeQueryParameter()] ?? null);
        $this->theme = $this->themeManager->resolve($requestedTheme);
        $this->themeKey = (string) ($this->theme['key'] ?? $this->themeManager->defaultThemeKey());
    }

    private function bootRuntime(): void
    {
        $this->config = UnifiedAppointmentsConfig::fromLaravelConfig($this->rawConfig, []);

        if (!$this->config->shouldAutoBootstrap()) {
            throw new RuntimeException(
                'The starter page runs only in the startup SQLite edition. Switch back to startup mode or use the Pro install workflow.',
            );
        }

        if (!is_dir($this->config->databaseLibraryPath)) {
            throw new RuntimeException(sprintf(
                'Unified Databases library not found at "%s".',
                $this->config->databaseLibraryPath,
            ));
        }

        $databasePath = $this->config->database ?: $this->config->host;

        if ($databasePath === null || $databasePath === '') {
            throw new RuntimeException('Startup SQLite database path is not configured.');
        }

        $directory = dirname($databasePath);

        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        if (!is_file($databasePath)) {
            touch($databasePath);
        }

        $connector = new UnifiedDatabaseConnector($this->config);
        $this->schema = new SchemaManager($connector, $this->config);
        $this->schema->install();

        $this->repository = new AppointmentRepository($connector, $this->config);
        $this->scheduler = new AppointmentScheduler($this->repository, $this->schema);
        $this->emailDispatcher = new SmtpEmailDispatcher();
    }

    private function seedStarterReferenceData(): void
    {
        $this->seedTimezoneCatalog();
        $this->seedSystemConfig();
        $this->seedLocations();
        $this->seedTeamMembers();
        $this->seedNotificationSettings();
    }

    private function loadRuntimeTimezone(): void
    {
        $timezone = $this->repository->getSystemConfig('app_timezone') ?? $this->config->appTimezone;
        $this->appTimezone = $this->validatedTimezone($timezone);
    }

    private function loadRuntimeBranding(): void
    {
        $this->companyName = $this->repository->getSystemConfig('company_name') ?? 'Your Company';
        $this->companyLogoUrl = $this->stringOrNull($this->repository->getSystemConfig('company_logo_url'));
    }

    private function seedTimezoneCatalog(): void
    {
        if ($this->repository->timezoneCount() > 0) {
            return;
        }

        $timezonesPath = $this->packageRoot . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'timezones' . DIRECTORY_SEPARATOR . 'easyappointments.php';
        $rows = require $timezonesPath;

        if (!is_array($rows)) {
            throw new RuntimeException('The timezone catalog is invalid.');
        }

        $this->repository->replaceTimezones($rows);
    }

    private function seedSystemConfig(): void
    {
        if (!$this->repository->hasSystemConfig('app_timezone')) {
            $this->repository->upsertSystemConfig(
                'app_timezone',
                $this->validatedTimezone($this->config->appTimezone),
                'system',
            );
        }

        if (!$this->repository->hasSystemConfig('company_name')) {
            $this->repository->upsertSystemConfig(
                'company_name',
                'Your Company',
                'system',
            );
        }

        if (!$this->repository->hasSystemConfig('company_logo_url')) {
            $this->repository->upsertSystemConfig(
                'company_logo_url',
                null,
                'system',
            );
        }

        foreach ([
            'mail_host' => null,
            'mail_port' => '587',
            'mail_encryption' => 'tls',
            'mail_username' => null,
            'mail_password' => null,
            'mail_from_address' => null,
            'mail_from_name' => 'Your Company',
            'mail_reply_to' => null,
        ] as $key => $value) {
            if ($this->repository->hasSystemConfig($key)) {
                continue;
            }

            $this->repository->upsertSystemConfig($key, $value, 'mail_server');
        }
    }

    private function seedLocations(): void
    {
        if ($this->repository->listLocations() !== []) {
            return;
        }

        $this->repository->createLocation([
            'location_key' => self::DEFAULT_LOCATION_KEY,
            'name' => 'Main Location',
            'contact_name' => 'Booking Desk',
            'email' => 'office@example.test',
            'phone' => '555-0100',
            'address_line_1' => '100 Atlantic Avenue',
            'city' => 'Halifax',
            'state_province' => 'NS',
            'postal_code' => 'B3H 1A1',
            'country' => 'Canada',
            'timezone' => $this->validatedTimezone($this->config->appTimezone),
        ]);
    }

    private function seedTeamMembers(): void
    {
        if ($this->repository->listTeamMembers() !== []) {
            return;
        }

        $this->repository->createTeamMember([
            'member_key' => self::DEFAULT_OWNER_ID,
            'location_key' => $this->defaultLocationKey(),
            'name' => 'Core Team',
            'email' => 'team@example.test',
            'phone' => '555-0101',
            'role' => 'Booking Manager',
            'owner_type' => self::DEFAULT_OWNER_TYPE,
            'timezone' => $this->validatedTimezone($this->config->appTimezone),
            'is_active' => 1,
        ]);
    }

    private function seedNotificationSettings(): void
    {
        $defaults = [
            'email' => [
                'is_enabled' => 1,
                'recipient' => 'ops@example.test',
                'sender_name' => 'Your Company',
                'trigger_summary' => 'Booking reminders, confirmations, reschedules, and cancellations',
                'config_payload' => 'Startup e-mail is used for client reminders and operator updates.',
            ],
            'daily_digest' => [
                'is_enabled' => 1,
                'recipient' => 'owners@example.test',
                'sender_name' => 'Your Company',
                'trigger_summary' => 'Daily agenda and waitlist summary',
                'config_payload' => 'Digest is generated from the startup SQLite schedule.',
            ],
        ];

        foreach ($defaults as $channel => $payload) {
            if ($this->repository->findNotificationSetting($channel) !== null) {
                continue;
            }

            $this->repository->saveNotificationSetting($channel, $payload);
        }
    }

    private function seedDefaults(): void
    {
        if ($this->repository->listServices() === []) {
            $this->scheduler->createService(new ServiceData(
                name: 'Standard Booking',
                durationMinutes: 60,
                bufferBeforeMinutes: 15,
                bufferAfterMinutes: 15,
                slotIntervalMinutes: 30,
                leadTimeMinutes: 0,
                maxAdvanceDays: 120,
                locationId: $this->defaultLocationKey(),
                timezone: $this->appTimezone,
                depositAmount: 25.00,
                noShowFeeAmount: 35.00,
            ));
        }

        if ($this->repository->listAvailabilityRules() === []) {
            foreach ([1, 2, 3, 4, 5] as $weekday) {
                $this->scheduler->addAvailabilityRule(new AvailabilityRuleData(
                    ownerType: self::DEFAULT_OWNER_TYPE,
                    ownerId: $this->defaultOwnerId(),
                    weekday: $weekday,
                    startTimeLocal: '09:00',
                    endTimeLocal: '17:00',
                    slotIntervalMinutes: 30,
                    locationId: $this->defaultLocationKey(),
                    timezone: $this->appTimezone,
                ));
            }
        }
    }

    private function handlePost(): void
    {
        $action = (string) ($_POST['action'] ?? '');

        try {
            match ($action) {
                'create_service' => $this->createService(),
                'add_rule' => $this->addAvailabilityRule(),
                'add_exception' => $this->addAvailabilityException(),
                'book_appointment' => $this->bookAppointment(),
                'reschedule_appointment' => $this->rescheduleAppointment(),
                'cancel_appointment' => $this->cancelAppointment(),
                'add_waitlist' => $this->addWaitlistEntry(),
                'save_system_config' => $this->saveSystemConfig(),
                'save_email_server' => $this->saveEmailServer(),
                'create_location' => $this->createLocation(),
                'update_location' => $this->updateLocation(),
                'create_team_member' => $this->createTeamMember(),
                'update_team_member' => $this->updateTeamMember(),
                'save_notification_setting' => $this->saveNotificationSetting(),
                'save_reminder_schedule' => $this->saveReminderSchedule(),
                'save_calendar_connection' => $this->saveCalendarConnection(),
                default => throw new RuntimeException('Unknown starter action.'),
            };

            $this->processDueEmailReminders();
            $this->redirectWithFlash('success', $this->successMessageFor($action));
        } catch (Throwable $exception) {
            $this->redirectWithFlash('error', $exception->getMessage());
        }
    }

    private function createService(): void
    {
        $name = trim((string) ($_POST['service_name'] ?? ''));

        if ($name === '') {
            throw new RuntimeException('Service name is required.');
        }

        $locationKey = $this->nullableLocationKey($_POST['service_location_key'] ?? null);
        $timezone = $this->validatedTimezone($this->requiredString($_POST['service_timezone'] ?? $this->appTimezone, 'Service timezone is required.'));

        $this->scheduler->createService(new ServiceData(
            name: $name,
            durationMinutes: $this->intValue($_POST['duration_minutes'] ?? 60, 1),
            bufferBeforeMinutes: $this->intValue($_POST['buffer_before_minutes'] ?? 0, 0),
            bufferAfterMinutes: $this->intValue($_POST['buffer_after_minutes'] ?? 0, 0),
            slotIntervalMinutes: $this->intValue($_POST['slot_interval_minutes'] ?? 30, 1),
            leadTimeMinutes: $this->intValue($_POST['lead_time_minutes'] ?? 0, 0),
            maxAdvanceDays: $this->intValue($_POST['max_advance_days'] ?? 120, 1),
            locationId: $locationKey,
            timezone: $timezone,
            depositAmount: $this->floatOrNull($_POST['deposit_amount'] ?? null),
            noShowFeeAmount: $this->floatOrNull($_POST['no_show_fee_amount'] ?? null),
        ));
    }

    private function addAvailabilityRule(): void
    {
        $owner = $this->selectedTeamOwner($_POST['rule_owner_id'] ?? self::DEFAULT_OWNER_ID);
        $timezone = $this->validatedTimezone($this->requiredString($_POST['rule_timezone'] ?? $this->appTimezone, 'Rule timezone is required.'));

        $this->scheduler->addAvailabilityRule(new AvailabilityRuleData(
            ownerType: $owner['owner_type'],
            ownerId: $owner['owner_id'],
            weekday: $this->intValue($_POST['rule_weekday'] ?? 1, 0),
            startTimeLocal: $this->requiredTime($_POST['rule_start_time'] ?? '09:00'),
            endTimeLocal: $this->requiredTime($_POST['rule_end_time'] ?? '17:00'),
            slotIntervalMinutes: $this->intValue($_POST['rule_slot_interval_minutes'] ?? 30, 1),
            locationId: $this->nullableLocationKey($_POST['rule_location_key'] ?? $this->defaultLocationKey()),
            timezone: $timezone,
        ));
    }

    private function addAvailabilityException(): void
    {
        $owner = $this->selectedTeamOwner($_POST['exception_owner_id'] ?? self::DEFAULT_OWNER_ID);
        $timezone = $this->validatedTimezone($this->requiredString($_POST['exception_timezone'] ?? $this->appTimezone, 'Exception timezone is required.'));

        $this->scheduler->addAvailabilityException(new AvailabilityExceptionData(
            ownerType: $owner['owner_type'],
            ownerId: $owner['owner_id'],
            startsAt: $this->dateTimeValue($_POST['exception_starts_at'] ?? null),
            endsAt: $this->dateTimeValue($_POST['exception_ends_at'] ?? null),
            exceptionType: 'blackout',
            locationId: $this->nullableLocationKey($_POST['exception_location_key'] ?? $this->defaultLocationKey()),
            timezone: $timezone,
            notes: $this->stringOrNull($_POST['exception_notes'] ?? null),
        ));
    }

    private function bookAppointment(): void
    {
        $owner = $this->parseOwnerToken($this->requiredString($_POST['owner'] ?? null, 'Owner is required.'));
        $slotStart = $this->dateAndTimeValue($_POST['date'] ?? null, $_POST['appointment_time'] ?? null);
        $service = $this->mustFindService($this->requiredString($_POST['service'] ?? null, 'Service is required.'));
        $timezone = $this->validatedTimezone((string) ($service['timezone'] ?? $this->appTimezone));
        $customerEmail = $this->stringOrNull($_POST['customer_email'] ?? null);
        $validatedReminderSendAt = $this->validatedReminderSchedule(
            reminderInput: $_POST['reminder_send_at'] ?? null,
            appointmentStartUtc: $slotStart->setTimezone(new DateTimeZone('UTC')),
            customerEmail: $customerEmail,
        );

        $appointmentId = $this->scheduler->bookAppointment(new BookAppointmentData(
            serviceId: $service['id'],
            startsAt: $slotStart,
            customerName: $this->requiredString($_POST['customer_name'] ?? null, 'Customer name is required.'),
            staffId: $owner['owner_type'] === 'staff' ? $owner['owner_id'] : null,
            resourceId: $owner['owner_type'] === 'resource' ? $owner['owner_id'] : null,
            customerEmail: $customerEmail,
            customerPhone: $this->stringOrNull($_POST['customer_phone'] ?? null),
            locationId: $service['location_id'] ?: null,
            timezone: $timezone,
            notes: $this->stringOrNull($_POST['booking_notes'] ?? null),
        ));

        if ($validatedReminderSendAt !== null) {
            $this->persistReminderSchedule($appointmentId, $validatedReminderSendAt);
        }
    }

    private function rescheduleAppointment(): void
    {
        $appointmentId = $this->requiredString($_POST['appointment_id'] ?? null, 'Appointment is required.');
        $existingAppointment = $this->repository->findAppointment($appointmentId);

        if ($existingAppointment === null) {
            throw new RuntimeException('Appointment not found.');
        }

        $newStart = $this->dateTimeValue($_POST['new_start'] ?? null);

        $this->validateReminderStillFits($existingAppointment, $newStart);

        $this->scheduler->rescheduleAppointment(
            appointmentId: $appointmentId,
            newStart: $newStart,
            timezone: $this->appTimezone,
        );
    }

    private function cancelAppointment(): void
    {
        $appointmentId = $this->requiredString($_POST['appointment_id'] ?? null, 'Appointment is required.');
        $existingAppointment = $this->repository->findAppointment($appointmentId);

        if ($existingAppointment === null) {
            throw new RuntimeException('Appointment not found.');
        }

        $reason = $this->stringOrNull($_POST['cancellation_reason'] ?? null);

        $this->scheduler->cancelAppointment(
            appointmentId: $appointmentId,
            reason: $reason,
        );

        $this->repository->updateAppointment($appointmentId, [
            'reminder_status' => ($existingAppointment['reminder_sent_at_utc'] ?? null)
                ? 'sent'
                : (($existingAppointment['reminder_send_at_utc'] ?? null) ? 'cancelled' : null),
            'reminder_last_error' => null,
            'updated_at_utc' => $this->nowUtc(),
        ]);
    }

    private function addWaitlistEntry(): void
    {
        $owner = $this->parseOwnerToken($this->requiredString($_POST['owner'] ?? null, 'Owner is required.'));
        $service = $this->mustFindService($this->requiredString($_POST['service'] ?? null, 'Service is required.'));
        $timezone = $this->validatedTimezone((string) ($service['timezone'] ?? $this->appTimezone));

        $this->scheduler->addToWaitlist(new WaitlistEntryData(
            serviceId: $service['id'],
            preferredStart: $this->dateTimeValue($_POST['preferred_start'] ?? null),
            preferredEnd: $this->dateTimeValue($_POST['preferred_end'] ?? null),
            customerName: $this->requiredString($_POST['waitlist_name'] ?? null, 'Customer name is required.'),
            staffId: $owner['owner_type'] === 'staff' ? $owner['owner_id'] : null,
            resourceId: $owner['owner_type'] === 'resource' ? $owner['owner_id'] : null,
            customerEmail: $this->stringOrNull($_POST['waitlist_email'] ?? null),
            customerPhone: $this->stringOrNull($_POST['waitlist_phone'] ?? null),
            locationId: $service['location_id'] ?: null,
            timezone: $timezone,
        ));
    }

    private function saveSystemConfig(): void
    {
        $timezone = $this->validatedTimezone($this->requiredString($_POST['app_timezone'] ?? null, 'System timezone is required.'));
        $companyName = $this->requiredString($_POST['company_name'] ?? null, 'Company name is required.');
        $companyLogoUrl = $this->stringOrNull($_POST['company_logo_url'] ?? null);
        $uploadedLogoUrl = $this->storeCompanyLogoUpload($_FILES['company_logo_file'] ?? null, $companyName);

        if ($uploadedLogoUrl !== null) {
            $companyLogoUrl = $uploadedLogoUrl;
        }

        $this->repository->upsertSystemConfig('app_timezone', $timezone, 'system');
        $this->repository->upsertSystemConfig('company_name', $companyName, 'system');
        $this->repository->upsertSystemConfig('company_logo_url', $companyLogoUrl, 'system');
        $this->appTimezone = $timezone;
        $this->companyName = $companyName;
        $this->companyLogoUrl = $companyLogoUrl;

        if (($this->repository->getSystemConfig('mail_from_name') ?? null) === 'Your Company') {
            $this->repository->upsertSystemConfig('mail_from_name', $companyName, 'mail_server');
        }
    }

    private function saveEmailServer(): void
    {
        $existingConfig = $this->mailServerConfig();
        $host = $this->stringOrNull($_POST['mail_host'] ?? null);
        $port = $this->stringOrNull($_POST['mail_port'] ?? null);
        $encryption = strtolower($this->requiredString($_POST['mail_encryption'] ?? 'tls', 'Mail encryption is required.'));
        $username = $this->stringOrNull($_POST['mail_username'] ?? null);
        $password = $this->stringOrNull($_POST['mail_password'] ?? null);
        $fromAddress = $this->stringOrNull($_POST['mail_from_address'] ?? null);
        $fromName = $this->stringOrNull($_POST['mail_from_name'] ?? null);
        $replyTo = $this->stringOrNull($_POST['mail_reply_to'] ?? null);

        if (!in_array($encryption, ['none', 'tls', 'ssl'], true)) {
            throw new RuntimeException('Mail encryption must be none, tls, or ssl.');
        }

        if ($host !== null || $port !== null || $username !== null || $password !== null || $fromAddress !== null || $replyTo !== null) {
            $host = $this->requiredString($host, 'SMTP host is required.');
            $fromAddress = $this->requiredEmail($fromAddress, 'From address is required.');
            $port = (string) $this->intValue($port, 1);

            if ($replyTo !== null) {
                $replyTo = $this->requiredEmail($replyTo, 'Reply-to address must be valid.');
            }

            if ($password !== null && $username === null) {
                throw new RuntimeException('SMTP username is required when a password is set.');
            }
        }

        if ($password === null) {
            $password = $existingConfig['password'] ?? null;
        }

        $this->repository->upsertSystemConfig('mail_host', $host, 'mail_server');
        $this->repository->upsertSystemConfig('mail_port', $port ?? '587', 'mail_server');
        $this->repository->upsertSystemConfig('mail_encryption', $encryption, 'mail_server');
        $this->repository->upsertSystemConfig('mail_username', $username, 'mail_server');
        $this->repository->upsertSystemConfig('mail_password', $password, 'mail_server');
        $this->repository->upsertSystemConfig('mail_from_address', $fromAddress, 'mail_server');
        $this->repository->upsertSystemConfig('mail_from_name', $fromName ?? $this->companyName, 'mail_server');
        $this->repository->upsertSystemConfig('mail_reply_to', $replyTo, 'mail_server');
    }

    private function createLocation(): void
    {
        $locationKey = $this->requiredString($_POST['location_key'] ?? null, 'Location key is required.');

        if ($this->repository->findLocation($locationKey) !== null) {
            throw new RuntimeException('Location key already exists.');
        }

        $this->repository->createLocation([
            'location_key' => $locationKey,
            'name' => $this->requiredString($_POST['location_name'] ?? null, 'Location name is required.'),
            'contact_name' => $this->stringOrNull($_POST['location_contact_name'] ?? null),
            'email' => $this->stringOrNull($_POST['location_email'] ?? null),
            'phone' => $this->stringOrNull($_POST['location_phone'] ?? null),
            'address_line_1' => $this->stringOrNull($_POST['location_address_line_1'] ?? null),
            'address_line_2' => $this->stringOrNull($_POST['location_address_line_2'] ?? null),
            'city' => $this->stringOrNull($_POST['location_city'] ?? null),
            'state_province' => $this->stringOrNull($_POST['location_state_province'] ?? null),
            'postal_code' => $this->stringOrNull($_POST['location_postal_code'] ?? null),
            'country' => $this->stringOrNull($_POST['location_country'] ?? null),
            'timezone' => $this->validatedTimezone($this->requiredString($_POST['location_timezone'] ?? null, 'Location timezone is required.')),
        ]);
    }

    private function updateLocation(): void
    {
        $locationKey = $this->requiredString($_POST['existing_location_key'] ?? null, 'Location key is required.');

        if ($this->repository->findLocation($locationKey) === null) {
            throw new RuntimeException('Location not found.');
        }

        $this->repository->updateLocation($locationKey, [
            'name' => $this->requiredString($_POST['location_name'] ?? null, 'Location name is required.'),
            'contact_name' => $this->stringOrNull($_POST['location_contact_name'] ?? null),
            'email' => $this->stringOrNull($_POST['location_email'] ?? null),
            'phone' => $this->stringOrNull($_POST['location_phone'] ?? null),
            'address_line_1' => $this->stringOrNull($_POST['location_address_line_1'] ?? null),
            'address_line_2' => $this->stringOrNull($_POST['location_address_line_2'] ?? null),
            'city' => $this->stringOrNull($_POST['location_city'] ?? null),
            'state_province' => $this->stringOrNull($_POST['location_state_province'] ?? null),
            'postal_code' => $this->stringOrNull($_POST['location_postal_code'] ?? null),
            'country' => $this->stringOrNull($_POST['location_country'] ?? null),
            'timezone' => $this->validatedTimezone($this->requiredString($_POST['location_timezone'] ?? null, 'Location timezone is required.')),
        ]);
    }

    private function createTeamMember(): void
    {
        $memberKey = $this->requiredString($_POST['member_key'] ?? null, 'Team key is required.');

        if ($this->repository->findTeamMember($memberKey) !== null) {
            throw new RuntimeException('Team key already exists.');
        }

        $ownerType = $this->ownerType($_POST['member_owner_type'] ?? self::DEFAULT_OWNER_TYPE);

        $this->repository->createTeamMember([
            'member_key' => $memberKey,
            'location_key' => $this->nullableLocationKey($_POST['member_location_key'] ?? null),
            'name' => $this->requiredString($_POST['member_name'] ?? null, 'Team member name is required.'),
            'email' => $this->stringOrNull($_POST['member_email'] ?? null),
            'phone' => $this->stringOrNull($_POST['member_phone'] ?? null),
            'role' => $this->requiredString($_POST['member_role'] ?? null, 'Role is required.'),
            'owner_type' => $ownerType,
            'timezone' => $this->validatedTimezone($this->requiredString($_POST['member_timezone'] ?? null, 'Team timezone is required.')),
            'is_active' => isset($_POST['member_is_active']) ? 1 : 0,
        ]);
    }

    private function updateTeamMember(): void
    {
        $memberKey = $this->requiredString($_POST['existing_member_key'] ?? null, 'Team key is required.');

        if ($this->repository->findTeamMember($memberKey) === null) {
            throw new RuntimeException('Team member not found.');
        }

        $ownerType = $this->ownerType($_POST['member_owner_type'] ?? self::DEFAULT_OWNER_TYPE);

        $this->repository->updateTeamMember($memberKey, [
            'location_key' => $this->nullableLocationKey($_POST['member_location_key'] ?? null),
            'name' => $this->requiredString($_POST['member_name'] ?? null, 'Team member name is required.'),
            'email' => $this->stringOrNull($_POST['member_email'] ?? null),
            'phone' => $this->stringOrNull($_POST['member_phone'] ?? null),
            'role' => $this->requiredString($_POST['member_role'] ?? null, 'Role is required.'),
            'owner_type' => $ownerType,
            'timezone' => $this->validatedTimezone($this->requiredString($_POST['member_timezone'] ?? null, 'Team timezone is required.')),
            'is_active' => isset($_POST['member_is_active']) ? 1 : 0,
        ]);
    }

    private function saveNotificationSetting(): void
    {
        $channel = strtolower($this->requiredString($_POST['notification_channel'] ?? null, 'Notification channel is required.'));

        $this->repository->saveNotificationSetting($channel, [
            'is_enabled' => isset($_POST['notification_is_enabled']) ? 1 : 0,
            'recipient' => $this->stringOrNull($_POST['notification_recipient'] ?? null),
            'sender_name' => $this->stringOrNull($_POST['notification_sender_name'] ?? null),
            'trigger_summary' => $this->stringOrNull($_POST['notification_trigger_summary'] ?? null),
            'config_payload' => $this->stringOrNull($_POST['notification_config_payload'] ?? null),
        ]);
    }

    private function saveReminderSchedule(): void
    {
        $appointmentId = $this->requiredString($_POST['appointment_id'] ?? null, 'Appointment is required.');
        $appointment = $this->repository->findAppointment($appointmentId);

        if ($appointment === null) {
            throw new RuntimeException('Appointment not found.');
        }

        $this->saveReminderScheduleForAppointment(
            appointment: $appointment,
            reminderInput: $_POST['reminder_send_at'] ?? null,
            customerEmail: $appointment['customer_email'] ?? null,
        );
    }

    private function saveCalendarConnection(): void
    {
        $provider = strtolower($this->requiredString($_POST['provider'] ?? null, 'Provider is required.'));

        if (!in_array($provider, ['google', 'microsoft'], true)) {
            throw new RuntimeException('Provider must be google or microsoft.');
        }

        $this->repository->upsertCalendarConnection([
            'owner_type' => self::SYSTEM_OWNER_TYPE,
            'owner_id' => self::SYSTEM_OWNER_ID,
            'provider' => $provider,
            'calendar_identifier' => $this->requiredString($_POST['calendar_identifier'] ?? null, 'Calendar identifier is required.'),
            'access_token_encrypted' => $this->stringOrNull($_POST['connection_status'] ?? null),
            'refresh_token_encrypted' => $this->stringOrNull($_POST['sync_notes'] ?? null),
            'expires_at_utc' => $this->nullableDateTimeValue($_POST['expires_at'] ?? null),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildState(): array
    {
        $locations = $this->repository->listLocations();
        $selectedLocationKey = $this->selectedLocationKey($locations);
        $services = $this->repository->listServices(locationId: $selectedLocationKey);
        $allTeamMembers = $this->repository->listTeamMembers();
        $teamMembers = $this->teamMembersForLocation($allTeamMembers, $selectedLocationKey);
        $availabilityRules = $this->repository->listAvailabilityRules(locationId: $selectedLocationKey);
        $availabilityExceptions = $this->repository->listAvailabilityExceptions(locationId: $selectedLocationKey);
        $allAppointments = $this->repository->listAppointments(locationId: $selectedLocationKey);
        $allWaitlistEntries = $this->repository->listWaitlistEntries(locationId: $selectedLocationKey);
        $notificationSettings = array_values(array_filter(
            $this->repository->listNotificationSettings(),
            static fn (array $setting): bool => in_array((string) ($setting['channel'] ?? ''), ['email', 'daily_digest'], true),
        ));
        $calendarConnections = $this->repository->listCalendarConnections(self::SYSTEM_OWNER_TYPE, self::SYSTEM_OWNER_ID);
        $systemConfig = $this->repository->listSystemConfig('system');
        $mailServerConfig = $this->systemConfigMap($this->repository->listSystemConfig('mail_server'));
        $emailDispatches = $this->repository->listEmailDispatches(12);
        $timezones = $this->repository->listTimezones();
        $currentPage = $this->selectedPage();
        $editingLocation = $this->editingLocation();
        $editingTeamMember = $this->editingTeamMember();
        $serviceMap = $this->serviceMap($services);
        $locationMap = $this->locationMap($locations);
        $teamMap = $this->teamMap($allTeamMembers);
        $owners = $this->ownersFromRules($availabilityRules, $teamMembers);
        $selectedServiceId = $this->selectedServiceId($services);
        $selectedService = $this->selectedService($services, $selectedServiceId);
        $selectedOwnerToken = $this->selectedOwnerToken($owners);
        $selectedOwner = $this->parseOwnerToken($selectedOwnerToken);
        $filteredAppointments = $this->appointmentsForOwner($allAppointments, $selectedOwner);
        $filteredWaitlistEntries = $this->waitlistEntriesForOwner($allWaitlistEntries, $selectedOwner);
        $appointments = array_slice($filteredAppointments, 0, 30);
        $waitlistEntries = array_slice($filteredWaitlistEntries, 0, 20);
        $selectedDate = $this->selectedDate();
        $selectedMonth = $this->selectedMonth($selectedDate);
        $selectedDayAppointments = $this->appointmentsForDate($filteredAppointments, $selectedDate);
        $upcomingAppointments = $this->upcomingAppointments($filteredAppointments);
        $calendarWeeks = $this->calendarWeeks($selectedMonth, $filteredAppointments, $selectedDate);
        $slots = $selectedServiceId === null
            ? []
            : $this->scheduler->findAvailableSlots(new SlotSearchData(
                serviceId: $selectedServiceId,
                windowStart: new DateTimeImmutable($selectedDate . ' 00:00:00', $this->appTimezone()),
                windowEnd: new DateTimeImmutable($selectedDate . ' 23:59:59', $this->appTimezone()),
                staffId: $selectedOwner['owner_type'] === 'staff' ? $selectedOwner['owner_id'] : null,
                resourceId: $selectedOwner['owner_type'] === 'resource' ? $selectedOwner['owner_id'] : null,
                locationId: $selectedLocationKey ?? ($selectedService['location_id'] ?? null),
                timezone: $selectedService['timezone'] ?? $this->appTimezone,
            ));
        $themeQueryParameter = $this->themeManager->themeQueryParameter();
        $metrics = $this->metrics($services, $filteredAppointments, $filteredWaitlistEntries, $slots, $selectedLocationKey === null ? $locations : array_values(array_filter(
            $locations,
            static fn (array $location): bool => (string) ($location['location_key'] ?? '') === $selectedLocationKey,
        )), $teamMembers);
        $flash = $this->pullFlash();
        $pages = $this->pages();
        $themeOptions = $this->themeManager->all();
        $notificationMap = $this->notificationMap($notificationSettings);
        $connectionMap = $this->calendarConnectionMap($calendarConnections);
        $context = [
            'page' => $currentPage,
            'location' => $selectedLocationKey,
            'service' => $selectedServiceId,
            'owner' => $selectedOwnerToken,
            'date' => $selectedDate,
            'month' => $selectedMonth,
            $themeQueryParameter => $this->themeKey,
        ];

        return [
            'appTimezone' => $this->appTimezone,
            'appointments' => $appointments,
            'availabilityExceptions' => $availabilityExceptions,
            'availabilityRules' => $availabilityRules,
            'calendarConnections' => $calendarConnections,
            'calendarConnectionMap' => $connectionMap,
            'calendarWeeks' => $calendarWeeks,
            'companyLogoUrl' => $this->companyLogoUrl,
            'companyName' => $this->companyName,
            'context' => $context,
            'contextFields' => $this->contextFields($context),
            'currentPage' => $currentPage,
            'editingLocation' => $editingLocation,
            'editingTeamMember' => $editingTeamMember,
            'flash' => $flash,
            'locations' => $locations,
            'locationMap' => $locationMap,
            'metrics' => $metrics,
            'emailDispatches' => $emailDispatches,
            'emailServerConfig' => $mailServerConfig,
            'notificationMap' => $notificationMap,
            'notificationSettings' => $notificationSettings,
            'owners' => $owners,
            'pages' => $pages,
            'selectedDate' => $selectedDate,
            'selectedDayAppointments' => $selectedDayAppointments,
            'selectedLocationKey' => $selectedLocationKey,
            'selectedMonth' => $selectedMonth,
            'selectedOwnerToken' => $selectedOwnerToken,
            'selectedService' => $selectedService,
            'selectedServiceId' => $selectedServiceId,
            'serviceMap' => $serviceMap,
            'services' => $services,
            'slots' => $slots,
            'systemConfig' => $systemConfig,
            'teamMap' => $teamMap,
            'teamMembers' => $teamMembers,
            'theme' => $this->theme,
            'themeKey' => $this->themeKey,
            'themeOptions' => $themeOptions,
            'themeQueryParameter' => $themeQueryParameter,
            'timezoneGroups' => $this->timezoneGroups($timezones),
            'timezones' => $timezones,
            'upcomingAppointments' => $upcomingAppointments,
            'waitlistEntries' => $waitlistEntries,
            'weekdayLabels' => $this->weekdayLabels(),
        ];
    }

    /**
     * @param array<string, mixed> $state
     */
    private function render(array $state): void
    {
        $template = $this->packageRoot . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'startup' . DIRECTORY_SEPARATOR . 'index.php';
        extract($state, EXTR_SKIP);
        require $template;
    }

    private function renderError(Throwable $exception): void
    {
        http_response_code(500);

        $themeConfig = [
            'css_variables' => [
                '--ua-page-background' => 'linear-gradient(135deg, #fce7e7 0%, #f4d0d0 48%, #eab2b2 100%)',
                '--ua-card-background' => '#fff9f9',
                '--ua-card-muted-background' => 'rgba(255, 255, 255, 0.72)',
                '--ua-border' => 'rgba(153, 27, 27, 0.18)',
                '--ua-text' => '#4a1010',
                '--ua-muted-text' => '#7f1d1d',
                '--ua-accent' => '#b91c1c',
                '--ua-accent-contrast' => '#fff9f9',
                '--ua-pill-background' => 'rgba(185, 28, 28, 0.12)',
                '--ua-shadow' => '0 24px 48px rgba(127, 29, 29, 0.16)',
            ],
        ];

        header('Content-Type: text/html; charset=UTF-8');

        $message = htmlspecialchars($exception->getMessage(), ENT_QUOTES, 'UTF-8');
        $vars = $this->cssVariables($themeConfig['css_variables']);

        echo <<<HTML
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Unified Appointments Startup Error</title>
    <style>
        :root {
            {$vars}
            color-scheme: light;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 2rem;
            background: var(--ua-page-background);
            color: var(--ua-text);
        }
        main {
            width: min(100%, 720px);
            background: var(--ua-card-background);
            border: 1px solid var(--ua-border);
            border-radius: 32px;
            box-shadow: var(--ua-shadow);
            padding: 2rem;
        }
        h1 { margin: 0 0 0.75rem; }
        p { margin: 0 0 1rem; color: var(--ua-muted-text); line-height: 1.6; }
        code {
            display: inline-block;
            padding: 0.15rem 0.45rem;
            border-radius: 999px;
            background: var(--ua-pill-background);
        }
    </style>
</head>
<body>
<main>
    <h1>Unified Appointments could not start</h1>
    <p>{$message}</p>
    <p>The startup page is designed for the SQLite startup edition. If you are moving to Pro or another database engine, use <code>php artisan unified-appointments:install</code> after configuring that connection.</p>
</main>
</body>
</html>
HTML;
    }

    /**
     * @param array<int, array<string, mixed>> $services
     * @return array<string, string>
     */
    private function serviceMap(array $services): array
    {
        $map = [];

        foreach ($services as $service) {
            $map[(string) $service['id']] = (string) $service['name'];
        }

        return $map;
    }

    /**
     * @param array<int, array<string, mixed>> $locations
     * @return array<string, string>
     */
    private function locationMap(array $locations): array
    {
        $map = [];

        foreach ($locations as $location) {
            $map[(string) $location['location_key']] = (string) $location['name'];
        }

        return $map;
    }

    /**
     * @param array<int, array<string, mixed>> $teamMembers
     * @return array<string, array<string, mixed>>
     */
    private function teamMap(array $teamMembers): array
    {
        $map = [];

        foreach ($teamMembers as $member) {
            $map[(string) $member['member_key']] = $member;
        }

        return $map;
    }

    /**
     * @param array<int, array<string, mixed>> $settings
     * @return array<string, array<string, mixed>>
     */
    private function notificationMap(array $settings): array
    {
        $map = [];

        foreach ($settings as $setting) {
            $map[(string) $setting['channel']] = $setting;
        }

        return $map;
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<string, string|null>
     */
    private function systemConfigMap(array $rows): array
    {
        $map = [];

        foreach ($rows as $row) {
            $map[(string) ($row['config_key'] ?? '')] = $row['config_value'] === null
                ? null
                : (string) $row['config_value'];
        }

        return $map;
    }

    /**
     * @param array<int, array<string, mixed>> $connections
     * @return array<string, array<string, mixed>>
     */
    private function calendarConnectionMap(array $connections): array
    {
        $map = [];

        foreach ($connections as $connection) {
            $map[(string) $connection['provider']] = $connection;
        }

        return $map;
    }

    /**
     * @param array<int, array<string, mixed>> $timezones
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function timezoneGroups(array $timezones): array
    {
        $groups = [];

        foreach ($timezones as $timezone) {
            $region = (string) ($timezone['region'] ?? 'Other');
            $groups[$region][] = $timezone;
        }

        return $groups;
    }

    /**
     * @param array<int, array<string, mixed>> $rules
     * @param array<int, array<string, mixed>> $teamMembers
     * @return array<int, array<string, string>>
     */
    private function ownersFromRules(array $rules, array $teamMembers): array
    {
        $owners = [];

        foreach ($teamMembers as $member) {
            $ownerType = $this->ownerType($member['owner_type'] ?? self::DEFAULT_OWNER_TYPE);
            $token = $this->ownerToken($ownerType, (string) $member['member_key']);
            $owners[$token] = [
                'token' => $token,
                'owner_type' => $ownerType,
                'owner_id' => (string) $member['member_key'],
                'label' => ucfirst($ownerType) . ': ' . (string) $member['name'],
            ];
        }

        foreach ($rules as $rule) {
            $token = $this->ownerToken((string) $rule['owner_type'], (string) $rule['owner_id']);

            if (isset($owners[$token])) {
                continue;
            }

            $owners[$token] = [
                'token' => $token,
                'owner_type' => (string) $rule['owner_type'],
                'owner_id' => (string) $rule['owner_id'],
                'label' => ucfirst((string) $rule['owner_type']) . ': ' . (string) $rule['owner_id'],
            ];
        }

        if ($owners === []) {
            $token = $this->ownerToken(self::DEFAULT_OWNER_TYPE, self::DEFAULT_OWNER_ID);
            $owners[$token] = [
                'token' => $token,
                'owner_type' => self::DEFAULT_OWNER_TYPE,
                'owner_id' => self::DEFAULT_OWNER_ID,
                'label' => 'Staff: Core Team',
            ];
        }

        return array_values($owners);
    }

    /**
     * @param array<int, array<string, mixed>> $services
     */
    private function selectedServiceId(array $services): int|string|null
    {
        $requested = $this->stringOrNull($_REQUEST['service'] ?? null);

        foreach ($services as $service) {
            if ((string) $service['id'] === $requested) {
                return $service['id'];
            }
        }

        return $services[0]['id'] ?? null;
    }

    /**
     * @param array<int, array<string, mixed>> $locations
     */
    private function selectedLocationKey(array $locations): ?string
    {
        $requested = $this->stringOrNull($_REQUEST['location'] ?? null);

        if ($requested === null) {
            return null;
        }

        foreach ($locations as $location) {
            if ((string) ($location['location_key'] ?? '') === $requested) {
                return $requested;
            }
        }

        return null;
    }

    /**
     * @param array<int, array<string, string>> $owners
     */
    private function selectedOwnerToken(array $owners): string
    {
        $requested = $this->stringOrNull($_REQUEST['owner'] ?? null);

        foreach ($owners as $owner) {
            if ($owner['token'] === $requested) {
                return $owner['token'];
            }
        }

        return $owners[0]['token'] ?? $this->ownerToken(self::DEFAULT_OWNER_TYPE, $this->defaultOwnerId());
    }

    /**
     * @return array<string, mixed>|null
     */
    private function editingLocation(): ?array
    {
        $locationKey = $this->stringOrNull($_GET['edit_location'] ?? null);

        if ($locationKey === null) {
            return null;
        }

        return $this->repository->findLocation($locationKey);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function editingTeamMember(): ?array
    {
        $memberKey = $this->stringOrNull($_GET['edit_team_member'] ?? null);

        if ($memberKey === null) {
            return null;
        }

        return $this->repository->findTeamMember($memberKey);
    }

    /**
     * @param array<int, array<string, mixed>> $teamMembers
     * @return array<int, array<string, mixed>>
     */
    private function teamMembersForLocation(array $teamMembers, ?string $locationKey): array
    {
        if ($locationKey === null) {
            return $teamMembers;
        }

        return array_values(array_filter(
            $teamMembers,
            static fn (array $member): bool => (string) ($member['location_key'] ?? '') === $locationKey,
        ));
    }

    private function selectedDate(): string
    {
        $requested = $this->stringOrNull($_REQUEST['date'] ?? null);

        if ($requested !== null && preg_match('/^\d{4}-\d{2}-\d{2}$/', $requested) === 1) {
            return $requested;
        }

        return $this->defaultDate()->format('Y-m-d');
    }

    private function selectedMonth(string $selectedDate): string
    {
        $requested = $this->stringOrNull($_REQUEST['month'] ?? null);

        if ($requested !== null && preg_match('/^\d{4}-\d{2}$/', $requested) === 1) {
            return $requested;
        }

        return substr($selectedDate, 0, 7);
    }

    private function selectedPage(): string
    {
        $requested = strtolower((string) ($this->stringOrNull($_REQUEST['page'] ?? null) ?? 'dashboard'));

        if ($requested === 'setup') {
            return 'services';
        }

        if (in_array($requested, self::STARTUP_PAGES, true)) {
            return $requested;
        }

        return 'dashboard';
    }

    /**
     * @param array<int, array<string, mixed>> $services
     * @param array<int, array<string, mixed>> $appointments
     * @param array<int, array<string, mixed>> $waitlistEntries
     * @param array<int, mixed> $slots
     * @param array<int, array<string, mixed>> $locations
     * @param array<int, array<string, mixed>> $teamMembers
     * @return array<int, array<string, string>>
     */
    private function metrics(
        array $services,
        array $appointments,
        array $waitlistEntries,
        array $slots,
        array $locations,
        array $teamMembers,
    ): array {
        $now = new DateTimeImmutable('now', $this->appTimezone());
        $activeAppointments = array_filter(
            $appointments,
            fn (array $appointment): bool => (string) ($appointment['status'] ?? '') !== 'cancelled'
                && new DateTimeImmutable((string) $appointment['starts_at_utc'], new DateTimeZone('UTC')) >= $now->setTimezone(new DateTimeZone('UTC')),
        );

        $nextSlot = $slots[0] ?? null;

        return [
            [
                'label' => 'Live Services',
                'value' => (string) count($services),
            ],
            [
                'label' => 'Locations',
                'value' => (string) count($locations),
            ],
            [
                'label' => 'Team Ready',
                'value' => (string) count(array_filter($teamMembers, static fn (array $member): bool => (int) ($member['is_active'] ?? 0) === 1)),
            ],
            [
                'label' => 'Upcoming Bookings',
                'value' => (string) count($activeAppointments),
            ],
            [
                'label' => 'Next Open Slot',
                'value' => $nextSlot === null
                    ? 'No slot'
                    : $nextSlot->startsAt->setTimezone($this->appTimezone())->format('M j, g:i A'),
            ],
            [
                'label' => 'Waitlist',
                'value' => (string) count($waitlistEntries),
            ],
        ];
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function pages(): array
    {
        return [
            'dashboard' => [
                'label' => 'Overview',
                'icon' => 'OV',
                'group' => 'Overview',
                'description' => 'Daily status, starter health, and queue visibility.',
            ],
            'calendar' => [
                'label' => 'Calendar',
                'icon' => 'CA',
                'group' => 'Scheduling',
                'description' => 'Monthly volume with day counts and a selected-day agenda.',
            ],
            'booking' => [
                'label' => 'New Appointment',
                'icon' => 'NW',
                'group' => 'Scheduling',
                'description' => 'Book confirmed appointments into non-overlapping slots.',
            ],
            'appointments' => [
                'label' => 'Appointment List',
                'icon' => 'AP',
                'group' => 'Scheduling',
                'description' => 'Review, reschedule, and cancel booked appointments.',
            ],
            'waitlist' => [
                'label' => 'Waitlist',
                'icon' => 'WL',
                'group' => 'Scheduling',
                'description' => 'Capture requests when the day is already full.',
            ],
            'services' => [
                'label' => 'Services',
                'icon' => 'SV',
                'group' => 'Setup',
                'description' => 'Services, branding, mail server, availability, blackout dates, and system timezone.',
            ],
            'locations' => [
                'label' => 'Locations',
                'icon' => 'LO',
                'group' => 'Setup',
                'description' => 'Physical sites, virtual destinations, and their local timezones.',
            ],
            'team' => [
                'label' => 'Team',
                'icon' => 'TM',
                'group' => 'Setup',
                'description' => 'Operators, staff, and resource owners.',
            ],
            'notifications' => [
                'label' => 'Notifications',
                'icon' => 'NT',
                'group' => 'Setup',
                'description' => 'E-mail reminder defaults, delivery status, and digest preferences.',
            ],
            'google' => [
                'label' => 'Google',
                'icon' => 'GO',
                'group' => 'Integrations',
                'description' => 'Google Calendar sync settings for the starter app.',
            ],
            'microsoft' => [
                'label' => 'Microsoft',
                'icon' => 'MS',
                'group' => 'Integrations',
                'description' => 'Microsoft 365 calendar sync settings for the starter app.',
            ],
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $services
     * @return array<string, mixed>|null
     */
    private function selectedService(array $services, int|string|null $selectedServiceId): ?array
    {
        foreach ($services as $service) {
            if ((string) ($service['id'] ?? '') === (string) $selectedServiceId) {
                return $service;
            }
        }

        return null;
    }

    /**
     * @param array<int, array<string, mixed>> $appointments
     * @param array{owner_type: string, owner_id: string} $owner
     * @return array<int, array<string, mixed>>
     */
    private function appointmentsForOwner(array $appointments, array $owner): array
    {
        return array_values(array_filter(
            $appointments,
            fn (array $appointment): bool => $this->matchesOwner(
                $appointment,
                $owner,
                staffColumn: 'staff_id',
                resourceColumn: 'resource_id',
            ),
        ));
    }

    /**
     * @param array<int, array<string, mixed>> $entries
     * @param array{owner_type: string, owner_id: string} $owner
     * @return array<int, array<string, mixed>>
     */
    private function waitlistEntriesForOwner(array $entries, array $owner): array
    {
        return array_values(array_filter(
            $entries,
            fn (array $entry): bool => $this->matchesOwner(
                $entry,
                $owner,
                staffColumn: 'staff_id',
                resourceColumn: 'resource_id',
            ),
        ));
    }

    /**
     * @param array<string, mixed> $record
     * @param array{owner_type: string, owner_id: string} $owner
     */
    private function matchesOwner(
        array $record,
        array $owner,
        string $staffColumn,
        string $resourceColumn,
    ): bool {
        return match ($owner['owner_type']) {
            'resource' => (string) ($record[$resourceColumn] ?? '') === $owner['owner_id'],
            default => (string) ($record[$staffColumn] ?? '') === $owner['owner_id'],
        };
    }

    /**
     * @param array<int, array<string, mixed>> $appointments
     * @return array<int, array<string, mixed>>
     */
    private function appointmentsForDate(array $appointments, string $selectedDate): array
    {
        return array_values(array_filter(
            $appointments,
            fn (array $appointment): bool => (new DateTimeImmutable((string) $appointment['starts_at_utc'], new DateTimeZone('UTC')))
                ->setTimezone($this->appTimezone())
                ->format('Y-m-d') === $selectedDate,
        ));
    }

    /**
     * @param array<int, array<string, mixed>> $appointments
     * @return array<int, array<string, mixed>>
     */
    private function upcomingAppointments(array $appointments): array
    {
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));

        return array_slice(array_values(array_filter(
            $appointments,
            static fn (array $appointment): bool => (string) ($appointment['status'] ?? '') !== 'cancelled'
                && new DateTimeImmutable((string) $appointment['starts_at_utc'], new DateTimeZone('UTC')) >= $now,
        )), 0, 8);
    }

    /**
     * @param array<int, array<string, mixed>> $appointments
     * @return array<int, array<int, array<string, mixed>>>
     */
    private function calendarWeeks(string $selectedMonth, array $appointments, string $selectedDate): array
    {
        $monthStart = new DateTimeImmutable($selectedMonth . '-01 00:00:00', $this->appTimezone());
        $gridStart = $monthStart->modify('-' . (int) $monthStart->format('w') . ' days');
        $counts = $this->appointmentCountsByDate($appointments);
        $weeks = [];
        $cursor = $gridStart;

        for ($week = 0; $week < 6; $week++) {
            $days = [];

            for ($day = 0; $day < 7; $day++) {
                $date = $cursor->format('Y-m-d');
                $days[] = [
                    'date' => $date,
                    'day' => $cursor->format('j'),
                    'count' => $counts[$date] ?? 0,
                    'in_month' => $cursor->format('Y-m') === $selectedMonth,
                    'is_selected' => $date === $selectedDate,
                    'is_today' => $date === (new DateTimeImmutable('today', $this->appTimezone()))->format('Y-m-d'),
                ];
                $cursor = $cursor->modify('+1 day');
            }

            $weeks[] = $days;
        }

        return $weeks;
    }

    /**
     * @param array<int, array<string, mixed>> $appointments
     * @return array<string, int>
     */
    private function appointmentCountsByDate(array $appointments): array
    {
        $counts = [];

        foreach ($appointments as $appointment) {
            if ((string) ($appointment['status'] ?? '') === 'cancelled') {
                continue;
            }

            $localDate = (new DateTimeImmutable((string) $appointment['starts_at_utc'], new DateTimeZone('UTC')))
                ->setTimezone($this->appTimezone())
                ->format('Y-m-d');

            $counts[$localDate] = ($counts[$localDate] ?? 0) + 1;
        }

        return $counts;
    }

    /**
     * @return array<string, string>|null
     */
    private function pullFlash(): ?array
    {
        if (!isset($_SESSION['unified_appointments_flash']) || !is_array($_SESSION['unified_appointments_flash'])) {
            return null;
        }

        $flash = $_SESSION['unified_appointments_flash'];
        unset($_SESSION['unified_appointments_flash']);

        return [
            'type' => (string) ($flash['type'] ?? 'info'),
            'message' => (string) ($flash['message'] ?? ''),
        ];
    }

    /**
     * @param array<string, string|int|null> $context
     */
    private function contextFields(array $context): string
    {
        $fields = [];

        foreach ($context as $name => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            $fields[] = sprintf(
                '<input type="hidden" name="%s" value="%s">',
                htmlspecialchars((string) $name, ENT_QUOTES, 'UTF-8'),
                htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'),
            );
        }

        return implode(PHP_EOL, $fields);
    }

    private function redirectWithFlash(string $type, string $message): never
    {
        $_SESSION['unified_appointments_flash'] = [
            'type' => $type,
            'message' => $message,
        ];

        $query = array_filter([
            'page' => $this->stringOrNull($_POST['page'] ?? $_GET['page'] ?? null),
            'location' => $this->stringOrNull($_POST['location'] ?? $_GET['location'] ?? null),
            'service' => $this->stringOrNull($_POST['service'] ?? $_GET['service'] ?? null),
            'owner' => $this->stringOrNull($_POST['owner'] ?? $_GET['owner'] ?? null),
            'date' => $this->stringOrNull($_POST['date'] ?? $_GET['date'] ?? null),
            'month' => $this->stringOrNull($_POST['month'] ?? $_GET['month'] ?? null),
            $this->themeManager->themeQueryParameter() => $this->stringOrNull(
                $_POST[$this->themeManager->themeQueryParameter()] ?? $_GET[$this->themeManager->themeQueryParameter()] ?? $this->themeKey,
            ),
        ], static fn ($value): bool => $value !== null && $value !== '');

        header('Location: ?' . http_build_query($query));
        exit;
    }

    private function successMessageFor(string $action): string
    {
        return match ($action) {
            'create_service' => 'Service saved to the startup SQLite app.',
            'add_rule' => 'Weekly availability added.',
            'add_exception' => 'Blackout added.',
            'book_appointment' => 'Appointment booked without overlap.',
            'reschedule_appointment' => 'Appointment rescheduled.',
            'cancel_appointment' => 'Appointment cancelled.',
            'add_waitlist' => 'Waitlist entry saved.',
            'save_system_config' => 'System settings saved.',
            'save_email_server' => 'E-mail server settings saved.',
            'create_location' => 'Location saved.',
            'update_location' => 'Location updated.',
            'create_team_member' => 'Team member saved.',
            'update_team_member' => 'Team member updated.',
            'save_notification_setting' => 'Notification settings saved.',
            'save_reminder_schedule' => 'Reminder schedule saved.',
            'save_calendar_connection' => 'Calendar settings saved.',
            default => 'Saved.',
        };
    }

    /**
     * @return array<string, string|null>
     */
    private function mailServerConfig(): array
    {
        $config = $this->systemConfigMap($this->repository->listSystemConfig('mail_server'));

        return [
            'host' => $config['mail_host'] ?? null,
            'port' => $config['mail_port'] ?? '587',
            'encryption' => $config['mail_encryption'] ?? 'tls',
            'username' => $config['mail_username'] ?? null,
            'password' => $config['mail_password'] ?? null,
            'from_address' => $config['mail_from_address'] ?? null,
            'from_name' => $config['mail_from_name'] ?? $this->companyName,
            'reply_to' => $config['mail_reply_to'] ?? null,
        ];
    }

    /**
     * @param array<string, mixed> $appointment
     */
    private function saveReminderScheduleForAppointment(array $appointment, mixed $reminderInput, mixed $customerEmail): void
    {
        $reminderSendAtUtc = $this->validatedReminderSchedule(
            reminderInput: $reminderInput,
            appointmentStartUtc: new DateTimeImmutable((string) $appointment['starts_at_utc'], new DateTimeZone('UTC')),
            customerEmail: $customerEmail,
        );

        $this->persistReminderSchedule($appointment['id'], $reminderSendAtUtc);
    }

    private function persistReminderSchedule(int|string $appointmentId, ?string $reminderSendAtUtc): void
    {
        $this->repository->updateAppointment($appointmentId, [
            'reminder_send_at_utc' => $reminderSendAtUtc,
            'reminder_status' => $reminderSendAtUtc === null ? null : 'pending',
            'reminder_sent_at_utc' => null,
            'reminder_last_error' => null,
            'updated_at_utc' => $this->nowUtc(),
        ]);
    }

    /**
     * @param array<string, mixed> $appointment
     */
    private function validateReminderStillFits(
        array $appointment,
        DateTimeImmutable $newStart,
    ): void {
        $scheduledReminder = $this->stringOrNull($appointment['reminder_send_at_utc'] ?? null);
        $reminderStatus = (string) ($appointment['reminder_status'] ?? '');

        if ($scheduledReminder === null || $reminderStatus === 'sent') {
            return;
        }

        if (new DateTimeImmutable($scheduledReminder, new DateTimeZone('UTC')) >= $newStart->setTimezone(new DateTimeZone('UTC'))) {
            throw new RuntimeException('Move the reminder time so it stays before the appointment start.');
        }
    }

    private function validatedReminderSchedule(
        mixed $reminderInput,
        DateTimeImmutable $appointmentStartUtc,
        mixed $customerEmail,
    ): ?string {
        $reminderValue = $this->stringOrNull($reminderInput);

        if ($reminderValue === null) {
            return null;
        }

        $this->requiredEmail($customerEmail, 'A client email is required before scheduling a reminder.');

        $reminderMoment = new DateTimeImmutable($reminderValue, $this->appTimezone());
        $reminderUtc = $reminderMoment->setTimezone(new DateTimeZone('UTC'));

        if ($reminderUtc >= $appointmentStartUtc) {
            throw new RuntimeException('Reminder time must be before the appointment starts.');
        }

        return $reminderUtc->format('Y-m-d H:i:s');
    }

    private function processDueEmailReminders(): void
    {
        $emailSetting = $this->repository->findNotificationSetting('email');

        if ($emailSetting !== null && (int) ($emailSetting['is_enabled'] ?? 0) !== 1) {
            return;
        }

        $dueAppointments = $this->repository->listDueReminderAppointments(
            new DateTimeImmutable('now', new DateTimeZone('UTC')),
            25,
        );

        foreach ($dueAppointments as $appointment) {
            $this->processReminderForAppointment($appointment);
        }
    }

    private function processReminderForAppointment(array $appointment): void
    {
        $service = $this->mustFindService($appointment['service_id']);
        $location = $this->stringOrNull($appointment['location_id'] ?? null) !== null
            ? $this->repository->findLocation((string) $appointment['location_id'])
            : null;
        $teamMember = $this->stringOrNull($appointment['staff_id'] ?? null) !== null
            ? $this->repository->findTeamMember((string) $appointment['staff_id'])
            : null;
        $recipient = $this->stringOrNull($appointment['customer_email'] ?? null);
        $serverConfig = $this->mailServerConfig();
        $subject = $this->appointmentReminderSubject($appointment, $service);
        $message = $this->appointmentReminderBody($appointment, $service, $location, $teamMember);

        try {
            $recipientEmail = $this->requiredEmail($recipient, 'Client email is required for reminder delivery.');
            $response = $this->emailDispatcher->dispatch($serverConfig, $recipientEmail, $subject, $message);

            $this->repository->createEmailDispatch([
                'provider' => 'smtp',
                'event_key' => 'appointment_reminder',
                'related_appointment_id' => $appointment['id'] ?? null,
                'recipient' => $recipientEmail,
                'subject_line' => $subject,
                'message_body' => $message,
                'status' => 'sent',
                'request_payload' => $this->payloadToJson($this->mailServerAuditConfig($serverConfig)),
                'response_payload' => $this->payloadToJson($response),
                'error_message' => null,
            ]);

            $this->repository->updateAppointment($appointment['id'], [
                'reminder_status' => 'sent',
                'reminder_sent_at_utc' => $this->nowUtc(),
                'reminder_last_error' => null,
                'updated_at_utc' => $this->nowUtc(),
            ]);
        } catch (Throwable $exception) {
            $this->repository->createEmailDispatch([
                'provider' => 'smtp',
                'event_key' => 'appointment_reminder',
                'related_appointment_id' => $appointment['id'] ?? null,
                'recipient' => $recipient,
                'subject_line' => $subject,
                'message_body' => $message,
                'status' => 'failed',
                'request_payload' => $this->payloadToJson($this->mailServerAuditConfig($serverConfig)),
                'response_payload' => null,
                'error_message' => $this->truncateText($exception->getMessage(), 1000),
            ]);

            $this->repository->updateAppointment($appointment['id'], [
                'reminder_status' => 'failed',
                'reminder_last_error' => $this->truncateText($exception->getMessage(), 1000),
                'updated_at_utc' => $this->nowUtc(),
            ]);
        }
    }

    /**
     * @param array<string, mixed> $appointment
     * @param array<string, mixed> $service
     */
    private function appointmentReminderSubject(
        array $appointment,
        array $service,
    ): string {
        $serviceName = (string) ($service['name'] ?? 'Booking');
        $scheduledAt = $this->appointmentMomentLabel((string) ($appointment['starts_at_utc'] ?? ''), $appointment, $service);

        return sprintf('%s reminder: %s', $this->companyName, $scheduledAt . ' - ' . $serviceName);
    }

    /**
     * @param array<string, mixed> $appointment
     * @param array<string, mixed> $service
     * @param array<string, mixed>|null $location
     * @param array<string, mixed>|null $teamMember
     */
    private function appointmentReminderBody(
        array $appointment,
        array $service,
        ?array $location,
        ?array $teamMember,
    ): string {
        $lines = [
            'Hello ' . ((string) ($appointment['customer_name'] ?? 'there')) . ',',
            '',
            sprintf('This is a reminder from %s for your upcoming booking.', $this->companyName),
            'Service: ' . (string) ($service['name'] ?? 'Booking'),
            'When: ' . $this->appointmentMomentLabel((string) ($appointment['starts_at_utc'] ?? ''), $appointment, $service),
        ];

        if ($location !== null) {
            $lines[] = 'Location: ' . (string) ($location['name'] ?? $location['location_key'] ?? 'Location');
        }

        if ($teamMember !== null) {
            $lines[] = 'Team: ' . (string) ($teamMember['name'] ?? $teamMember['member_key'] ?? 'Assigned team');
        }

        if (($notes = $this->stringOrNull($appointment['notes'] ?? null)) !== null) {
            $lines[] = 'Notes: ' . $notes;
        }

        $lines[] = '';
        $lines[] = 'If you need to make changes, please contact us before the appointment time.';

        return implode(PHP_EOL, $lines);
    }

    /**
     * @param array<string, mixed> $appointment
     * @param array<string, mixed> $service
     */
    private function appointmentMomentLabel(string $utcValue, array $appointment, array $service): string
    {
        if ($utcValue === '') {
            return 'the scheduled time';
        }

        $timezone = new DateTimeZone($this->validatedTimezone((string) ($appointment['timezone'] ?? $service['timezone'] ?? $this->appTimezone)));

        return (new DateTimeImmutable($utcValue, new DateTimeZone('UTC')))
            ->setTimezone($timezone)
            ->format('D M j, g:i A T');
    }

    private function payloadToJson(array $payload): ?string
    {
        $json = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return $json === false ? null : $json;
    }

    /**
     * @param array<string, string|null> $config
     * @return array<string, string|null>
     */
    private function mailServerAuditConfig(array $config): array
    {
        unset($config['password']);

        return $config;
    }

    private function truncateText(string $value, int $limit): string
    {
        if (strlen($value) <= $limit) {
            return $value;
        }

        return substr($value, 0, $limit - 3) . '...';
    }

    private function ownerType(mixed $value): string
    {
        $ownerType = strtolower($this->requiredString($value, 'Owner type is required.'));

        if (!in_array($ownerType, ['staff', 'resource'], true)) {
            throw new RuntimeException('Owner type must be staff or resource.');
        }

        return $ownerType;
    }

    /**
     * @return array{owner_type: string, owner_id: string}
     */
    private function parseOwnerToken(string $token): array
    {
        $parts = explode(':', $token, 2);

        if (count($parts) !== 2 || $parts[1] === '') {
            throw new RuntimeException('Owner selection is invalid.');
        }

        return [
            'owner_type' => $this->ownerType($parts[0]),
            'owner_id' => $parts[1],
        ];
    }

    /**
     * @return array{owner_type: string, owner_id: string}
     */
    private function selectedTeamOwner(mixed $memberKey): array
    {
        $key = $this->requiredString($memberKey, 'Owner is required.');
        $member = $this->repository->findTeamMember($key);

        if ($member === null) {
            return [
                'owner_type' => self::DEFAULT_OWNER_TYPE,
                'owner_id' => $key,
            ];
        }

        return [
            'owner_type' => $this->ownerType($member['owner_type'] ?? self::DEFAULT_OWNER_TYPE),
            'owner_id' => (string) $member['member_key'],
        ];
    }

    private function ownerToken(string $ownerType, string $ownerId): string
    {
        return $ownerType . ':' . $ownerId;
    }

    private function storeCompanyLogoUpload(mixed $file, string $companyName): ?string
    {
        if (!is_array($file) || !isset($file['error'])) {
            return null;
        }

        $error = (int) $file['error'];

        if ($error === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if ($error !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Logo upload could not be completed.');
        }

        $temporaryPath = (string) ($file['tmp_name'] ?? '');

        if ($temporaryPath === '' || !is_file($temporaryPath)) {
            throw new RuntimeException('Uploaded logo file is invalid.');
        }

        $extension = strtolower(pathinfo((string) ($file['name'] ?? 'logo'), PATHINFO_EXTENSION));
        $allowedExtensions = ['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg'];

        if (!in_array($extension, $allowedExtensions, true)) {
            throw new RuntimeException('Logo files must be png, jpg, jpeg, gif, webp, or svg.');
        }

        $uploadDirectory = $this->packageRoot
            . DIRECTORY_SEPARATOR . 'public'
            . DIRECTORY_SEPARATOR . 'uploads'
            . DIRECTORY_SEPARATOR . 'branding';

        if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0777, true) && !is_dir($uploadDirectory)) {
            throw new RuntimeException('Logo upload directory could not be created.');
        }

        $filename = sprintf(
            '%s-%s.%s',
            $this->safeSlug($companyName),
            date('YmdHis'),
            $extension,
        );
        $targetPath = $uploadDirectory . DIRECTORY_SEPARATOR . $filename;
        $stored = false;

        if (is_uploaded_file($temporaryPath)) {
            $stored = move_uploaded_file($temporaryPath, $targetPath);
        } elseif (@rename($temporaryPath, $targetPath)) {
            $stored = true;
        } elseif (@copy($temporaryPath, $targetPath)) {
            @unlink($temporaryPath);
            $stored = true;
        }

        if (!$stored) {
            throw new RuntimeException('Logo upload could not be saved.');
        }

        return 'uploads/branding/' . $filename;
    }

    private function safeSlug(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
        $value = trim($value, '-');

        return $value === '' ? 'company-logo' : $value;
    }

    private function requiredString(mixed $value, string $message): string
    {
        $string = $this->stringOrNull($value);

        if ($string === null) {
            throw new RuntimeException($message);
        }

        return $string;
    }

    private function requiredEmail(mixed $value, string $message): string
    {
        $email = $this->stringOrNull($value);

        if ($email === null || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new RuntimeException($message);
        }

        return $email;
    }

    private function requiredTime(mixed $value): string
    {
        $time = $this->requiredString($value, 'A time value is required.');

        if (preg_match('/^\d{2}:\d{2}$/', $time) !== 1) {
            throw new RuntimeException('Time values must use HH:MM format.');
        }

        return $time;
    }

    private function intValue(mixed $value, int $minimum): int
    {
        if (!is_numeric($value)) {
            throw new RuntimeException('Numeric values are required.');
        }

        $int = (int) $value;

        if ($int < $minimum) {
            throw new RuntimeException(sprintf('Numeric value must be at least %d.', $minimum));
        }

        return $int;
    }

    private function floatOrNull(mixed $value): ?float
    {
        $string = $this->stringOrNull($value);

        if ($string === null) {
            return null;
        }

        if (!is_numeric($string)) {
            throw new RuntimeException('Amounts must be numeric.');
        }

        return (float) $string;
    }

    private function stringOrNull(mixed $value): ?string
    {
        if (!is_string($value) && !is_numeric($value)) {
            return null;
        }

        $string = trim((string) $value);

        return $string === '' ? null : $string;
    }

    private function dateTimeValue(mixed $value): DateTimeImmutable
    {
        $string = $this->requiredString($value, 'A date and time is required.');

        return new DateTimeImmutable($string, $this->appTimezone());
    }

    private function dateAndTimeValue(mixed $date, mixed $time): DateTimeImmutable
    {
        $dateString = $this->requiredString($date, 'A booking date is required.');
        $timeString = $this->requiredTime($time);

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateString) !== 1) {
            throw new RuntimeException('Booking dates must use YYYY-MM-DD format.');
        }

        return new DateTimeImmutable($dateString . ' ' . $timeString, $this->appTimezone());
    }

    private function nullableDateTimeValue(mixed $value): ?string
    {
        $string = $this->stringOrNull($value);

        if ($string === null) {
            return null;
        }

        return (new DateTimeImmutable($string, $this->appTimezone()))
            ->setTimezone(new DateTimeZone('UTC'))
            ->format('Y-m-d H:i:s');
    }

    private function nowUtc(): string
    {
        return gmdate('Y-m-d H:i:s');
    }

    private function appTimezone(): DateTimeZone
    {
        return new DateTimeZone($this->appTimezone);
    }

    private function defaultDate(): DateTimeImmutable
    {
        $date = new DateTimeImmutable('today', $this->appTimezone());

        while (in_array((int) $date->format('w'), [0, 6], true)) {
            $date = $date->modify('+1 day');
        }

        return $date;
    }

    /**
     * @return array<int, string>
     */
    private function weekdayLabels(): array
    {
        return [
            0 => 'Sunday',
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
        ];
    }

    private function cssVariables(array $variables): string
    {
        $lines = [];

        foreach ($variables as $name => $value) {
            $lines[] = $name . ': ' . $value . ';';
        }

        return implode(PHP_EOL . '            ', $lines);
    }

    private function startSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        session_name('unified_appointments_startup');
        session_start();
    }

    /**
     * @return array<string, mixed>
     */
    private function mustFindService(int|string $serviceId): array
    {
        $service = $this->repository->findService($serviceId);

        if ($service === null) {
            throw new RuntimeException('Service not found.');
        }

        return $service;
    }

    private function validatedTimezone(string $timezone): string
    {
        $timezone = trim($timezone);

        try {
            new DateTimeZone($timezone);
        } catch (Throwable) {
            throw new RuntimeException('Timezone selection is invalid.');
        }

        return $timezone;
    }

    private function nullableLocationKey(mixed $value): ?string
    {
        $locationKey = $this->stringOrNull($value);

        if ($locationKey === null) {
            return null;
        }

        if ($this->repository->findLocation($locationKey) === null) {
            throw new RuntimeException('Selected location was not found.');
        }

        return $locationKey;
    }

    private function defaultLocationKey(): string
    {
        $locations = $this->repository->listLocations();

        return (string) ($locations[0]['location_key'] ?? self::DEFAULT_LOCATION_KEY);
    }

    private function defaultOwnerId(): string
    {
        $teamMembers = $this->repository->listTeamMembers();

        return (string) ($teamMembers[0]['member_key'] ?? self::DEFAULT_OWNER_ID);
    }
}
