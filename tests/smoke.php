<?php

require_once __DIR__ . '/bootstrap.php';

use UnifiedAppointments\Config\UnifiedAppointmentsConfig;
use UnifiedAppointments\Database\SchemaManager;
use UnifiedAppointments\Database\UnifiedDatabaseConnector;
use UnifiedAppointments\DTO\AvailabilityRuleData;
use UnifiedAppointments\DTO\BookAppointmentData;
use UnifiedAppointments\DTO\ServiceData;
use UnifiedAppointments\DTO\SlotSearchData;
use UnifiedAppointments\Repositories\AppointmentRepository;
use UnifiedAppointments\Services\AppointmentScheduler;

$databasePath = __DIR__ . '/smoke.sqlite';

if (is_file($databasePath)) {
    unlink($databasePath);
}

$config = UnifiedAppointmentsConfig::fromArray([
    'database_library_path' => 'C:\\Apache24\\htdocs\\Unified Databases',
    'driver' => 'sqlite',
    'host' => $databasePath,
    'database' => $databasePath,
    'table_prefix' => 'ua_',
    'app_timezone' => 'America/New_York',
]);

$connector = new UnifiedDatabaseConnector($config);
$schema = new SchemaManager($connector, $config);
$repository = new AppointmentRepository($connector, $config);
$scheduler = new AppointmentScheduler($repository, $schema);

$scheduler->install();

$serviceId = $scheduler->createService(new ServiceData(
    name: 'Consultation',
    durationMinutes: 60,
    bufferBeforeMinutes: 15,
    bufferAfterMinutes: 15,
    slotIntervalMinutes: 30,
    leadTimeMinutes: 0,
    maxAdvanceDays: 365,
    timezone: 'America/New_York',
    depositAmount: 25.00,
    noShowFeeAmount: 35.00,
));

$scheduler->addAvailabilityRule(new AvailabilityRuleData(
    ownerType: 'staff',
    ownerId: 'staff-1',
    weekday: 1,
    startTimeLocal: '09:00',
    endTimeLocal: '17:00',
    slotIntervalMinutes: 30,
    timezone: 'America/New_York',
));

$searchStart = new DateTimeImmutable('2026-04-27 00:00:00', new DateTimeZone('America/New_York'));
$searchEnd = new DateTimeImmutable('2026-04-27 23:59:59', new DateTimeZone('America/New_York'));

$initialSlots = $scheduler->findAvailableSlots(new SlotSearchData(
    serviceId: $serviceId,
    windowStart: $searchStart,
    windowEnd: $searchEnd,
    staffId: 'staff-1',
    timezone: 'America/New_York',
));

if (count($initialSlots) < 1) {
    fwrite(STDERR, "Expected at least one initial slot.\n");
    exit(1);
}

$appointmentId = $scheduler->bookAppointment(new BookAppointmentData(
    serviceId: $serviceId,
    startsAt: new DateTimeImmutable('2026-04-27 10:00:00', new DateTimeZone('America/New_York')),
    customerName: 'Smoke Test Customer',
    staffId: 'staff-1',
    customerEmail: 'smoke@example.test',
    timezone: 'America/New_York',
));

$overlapBlocked = false;

try {
    $scheduler->bookAppointment(new BookAppointmentData(
        serviceId: $serviceId,
        startsAt: new DateTimeImmutable('2026-04-27 10:30:00', new DateTimeZone('America/New_York')),
        customerName: 'Overlap Customer',
        staffId: 'staff-1',
        customerEmail: 'overlap@example.test',
        timezone: 'America/New_York',
    ));
} catch (\RuntimeException) {
    $overlapBlocked = true;
}

if (!$overlapBlocked) {
    fwrite(STDERR, "Overlapping appointment should be rejected.\n");
    exit(1);
}

$remainingSlots = $scheduler->findAvailableSlots(new SlotSearchData(
    serviceId: $serviceId,
    windowStart: $searchStart,
    windowEnd: $searchEnd,
    staffId: 'staff-1',
    timezone: 'America/New_York',
));

$stillHasTenAm = false;

foreach ($remainingSlots as $slot) {
    if ($slot->startsAt->format('Y-m-d H:i') === '2026-04-27 10:00') {
        $stillHasTenAm = true;
        break;
    }
}

if ($stillHasTenAm) {
    fwrite(STDERR, "Booked slot should not remain available.\n");
    exit(1);
}

$bookedAppointment = $repository->findAppointment($appointmentId);

if ($bookedAppointment === null) {
    fwrite(STDERR, "Expected the booked appointment to exist.\n");
    exit(1);
}

$repository->updateAppointment($bookedAppointment['id'], [
    'reminder_send_at_utc' => '2026-04-26 12:00:00',
    'reminder_status' => 'pending',
    'reminder_sent_at_utc' => null,
    'reminder_last_error' => null,
    'updated_at_utc' => gmdate('Y-m-d H:i:s'),
]);

$updatedAppointment = $repository->findAppointment($bookedAppointment['id']);

if (($updatedAppointment['reminder_status'] ?? null) !== 'pending') {
    fwrite(STDERR, "Expected the reminder schedule to be persisted on the appointment.\n");
    exit(1);
}

$repository->createEmailDispatch([
    'provider' => 'smtp',
    'event_key' => 'appointment_reminder',
    'related_appointment_id' => $bookedAppointment['id'],
    'recipient' => 'smoke@example.test',
    'subject_line' => 'Reminder',
    'message_body' => 'Test reminder message',
    'status' => 'sent',
    'request_payload' => '{"transport":"smtp"}',
    'response_payload' => '{"status":"ok"}',
    'error_message' => null,
]);

$dispatches = $repository->listEmailDispatches();

if (count($dispatches) !== 1 || (string) ($dispatches[0]['status'] ?? '') !== 'sent') {
    fwrite(STDERR, "Expected one successful e-mail dispatch log entry.\n");
    exit(1);
}

echo "Smoke test passed.\n";
echo 'Initial slots: ' . count($initialSlots) . PHP_EOL;
echo 'Remaining slots after booking: ' . count($remainingSlots) . PHP_EOL;
echo 'E-mail dispatch entries: ' . count($dispatches) . PHP_EOL;
