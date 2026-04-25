<?php

namespace UnifiedAppointments\Services;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use RuntimeException;
use UnifiedAppointments\Database\SchemaManager;
use UnifiedAppointments\DTO\AvailabilityExceptionData;
use UnifiedAppointments\DTO\AvailabilityRuleData;
use UnifiedAppointments\DTO\BookAppointmentData;
use UnifiedAppointments\DTO\ServiceData;
use UnifiedAppointments\DTO\SlotSearchData;
use UnifiedAppointments\DTO\WaitlistEntryData;
use UnifiedAppointments\Models\AppointmentSlot;
use UnifiedAppointments\Repositories\AppointmentRepository;

/**
 * AppointmentScheduler.
 */
final class AppointmentScheduler
{
    /**
     * Create a new instance.
     */
    public function __construct(
        private readonly AppointmentRepository $repository,
        private readonly SchemaManager $schema,
    ) {
    }

    /**
     * Install.
     */
    public function install(): void
    {
        $this->schema->install();
    }

    /**
     * Create Service.
     */
    public function createService(ServiceData $data): int|string
    {
        return $this->repository->createService($data);
    }

    /**
     * Add Availability Rule.
     */
    public function addAvailabilityRule(AvailabilityRuleData $data): int|string
    {
        $this->assertOwnerType($data->ownerType);

        if ($data->weekday < 0 || $data->weekday > 6) {
            throw new InvalidArgumentException('Weekday must be between 0 and 6.');
        }

        return $this->repository->createAvailabilityRule($data);
    }

    /**
     * Add Availability Exception.
     */
    public function addAvailabilityException(AvailabilityExceptionData $data): int|string
    {
        $this->assertOwnerType($data->ownerType);

        if ($data->endsAt <= $data->startsAt) {
            throw new InvalidArgumentException('Availability exceptions must end after they start.');
        }

        return $this->repository->createAvailabilityException($data);
    }

    /**
     * Add To Waitlist.
     */
    public function addToWaitlist(WaitlistEntryData $data): int|string
    {
        return $this->repository->addToWaitlist($data);
    }

    /**
     * @return array<int, AppointmentSlot>
     */
    public function findAvailableSlots(SlotSearchData $search): array
    {
        $service = $this->mustFindService($search->serviceId, $search->tenantId, $search->locationId);
        $this->assertOwnerSelection($search->staffId, $search->resourceId);

        $timezone = $this->resolveTimezone($search->timezone ?? $service['timezone'] ?? 'UTC');
        $durationMinutes = (int) $service['duration_minutes'];
        $bufferBeforeMinutes = (int) $service['buffer_before_minutes'];
        $bufferAfterMinutes = (int) $service['buffer_after_minutes'];
        $slotIntervalMinutes = $search->slotIntervalMinutes ?? (int) $service['slot_interval_minutes'];
        $leadTimeMinutes = (int) $service['lead_time_minutes'];
        $maxAdvanceDays = (int) $service['max_advance_days'];

        $ruleSets = $this->loadRulesForSearch($search, $search->staffId, $search->resourceId);
        $exceptions = $this->loadExceptionsForSearch($search, $search->staffId, $search->resourceId);
        $conflicts = $this->repository->fetchAppointmentsInRange(
            $search->windowStart->setTimezone(new DateTimeZone('UTC')),
            $search->windowEnd->setTimezone(new DateTimeZone('UTC')),
            $search->staffId,
            $search->resourceId,
            $search->tenantId,
            $search->locationId,
            $search->excludeAppointmentId,
        );

        $startOfDay = $search->windowStart->setTimezone($timezone)->setTime(0, 0);
        $endOfWindow = $search->windowEnd->setTimezone($timezone);
        $cutoffMinimum = (new DateTimeImmutable('now', $timezone))->modify('+' . $leadTimeMinutes . ' minutes');
        $cutoffMaximum = (new DateTimeImmutable('now', $timezone))->modify('+' . $maxAdvanceDays . ' days');
        $slots = [];
        $baseRules = $ruleSets[0] ?? [];

        if ($baseRules === []) {
            return [];
        }

        for ($cursor = $startOfDay; $cursor <= $endOfWindow; $cursor = $cursor->modify('+1 day')) {
            $localDate = $cursor->format('Y-m-d');
            $weekday = (int) $cursor->format('w');

            foreach ($baseRules as $rule) {
                if ((int) $rule['weekday'] !== $weekday) {
                    continue;
                }

                if (!$this->ruleAppliesOnDate($rule, $localDate)) {
                    continue;
                }

                $ruleTimezone = $this->resolveTimezone($rule['timezone'] ?: $timezone->getName());
                $slotStart = new DateTimeImmutable($localDate . ' ' . $rule['start_time_local'], $ruleTimezone);
                $ruleEnd = new DateTimeImmutable($localDate . ' ' . $rule['end_time_local'], $ruleTimezone);
                $increment = (int) ($rule['slot_interval_minutes'] ?: $slotIntervalMinutes);

                while ($slotStart < $ruleEnd) {
                    $slotEnd = $slotStart->modify('+' . $durationMinutes . ' minutes');

                    if ($slotEnd > $ruleEnd) {
                        break;
                    }

                    $occupiedStart = $slotStart->modify('-' . $bufferBeforeMinutes . ' minutes');
                    $occupiedEnd = $slotEnd->modify('+' . $bufferAfterMinutes . ' minutes');

                    if ($slotStart < $search->windowStart->setTimezone($ruleTimezone)) {
                        $slotStart = $slotStart->modify('+' . $increment . ' minutes');
                        continue;
                    }

                    if ($slotEnd > $search->windowEnd->setTimezone($ruleTimezone)) {
                        break;
                    }

                    if ($slotStart < $cutoffMinimum || $slotStart > $cutoffMaximum) {
                        $slotStart = $slotStart->modify('+' . $increment . ' minutes');
                        continue;
                    }

                    if ($this->overlapsException($occupiedStart, $occupiedEnd, $exceptions)) {
                        $slotStart = $slotStart->modify('+' . $increment . ' minutes');
                        continue;
                    }

                    if ($this->overlapsAppointment($occupiedStart, $occupiedEnd, $conflicts)) {
                        $slotStart = $slotStart->modify('+' . $increment . ' minutes');
                        continue;
                    }

                    if (!$this->candidateCoveredByEveryOwner($slotStart, $slotEnd, $ruleSets)) {
                        $slotStart = $slotStart->modify('+' . $increment . ' minutes');
                        continue;
                    }

                    $slot = new AppointmentSlot(
                        startsAt: $slotStart,
                        endsAt: $slotEnd,
                        occupiedStartsAt: $occupiedStart,
                        occupiedEndsAt: $occupiedEnd,
                        staffId: $search->staffId,
                        resourceId: $search->resourceId,
                        tenantId: $search->tenantId,
                        locationId: $search->locationId,
                    );

                    $slots[$slot->startsAt->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s')] = $slot;
                    $slotStart = $slotStart->modify('+' . $increment . ' minutes');
                }
            }
        }

        ksort($slots);

        return array_values($slots);
    }

    /**
     * Book Appointment.
     */
    public function bookAppointment(BookAppointmentData $data): int|string
    {
        $service = $this->mustFindService($data->serviceId, $data->tenantId, $data->locationId);
        $this->assertOwnerSelection($data->staffId, $data->resourceId);

        $timezone = $this->resolveTimezone($data->timezone ?? $service['timezone'] ?? 'UTC');
        $start = $data->startsAt->setTimezone($timezone);
        $durationMinutes = (int) $service['duration_minutes'];
        $bufferBeforeMinutes = (int) $service['buffer_before_minutes'];
        $bufferAfterMinutes = (int) $service['buffer_after_minutes'];
        $end = $start->modify('+' . $durationMinutes . ' minutes');
        $occupiedStart = $start->modify('-' . $bufferBeforeMinutes . ' minutes');
        $occupiedEnd = $end->modify('+' . $bufferAfterMinutes . ' minutes');

        $matchingSlot = $this->findExactSlot(new SlotSearchData(
            serviceId: $data->serviceId,
            windowStart: $start->setTime(0, 0),
            windowEnd: $start->setTime(23, 59, 59),
            staffId: $data->staffId,
            resourceId: $data->resourceId,
            tenantId: $data->tenantId,
            locationId: $data->locationId,
            timezone: $timezone->getName(),
        ), $start);

        if ($matchingSlot === null) {
            throw new RuntimeException('The requested appointment time is no longer available.');
        }

        $this->assertNoAppointmentOverlap(
            occupiedStart: $occupiedStart,
            occupiedEnd: $occupiedEnd,
            staffId: $data->staffId,
            resourceId: $data->resourceId,
            tenantId: $data->tenantId,
            locationId: $data->locationId,
            message: 'The requested appointment time overlaps an existing appointment.',
        );

        return $this->repository->createAppointment([
            'tenant_id' => $data->tenantId,
            'location_id' => $data->locationId,
            'service_id' => $data->serviceId,
            'staff_id' => $data->staffId,
            'resource_id' => $data->resourceId,
            'customer_name' => $data->customerName,
            'customer_email' => $data->customerEmail,
            'customer_phone' => $data->customerPhone,
            'starts_at_utc' => $this->toUtcString($start),
            'ends_at_utc' => $this->toUtcString($end),
            'occupied_starts_at_utc' => $this->toUtcString($occupiedStart),
            'occupied_ends_at_utc' => $this->toUtcString($occupiedEnd),
            'timezone' => $timezone->getName(),
            'status' => $data->status,
            'deposit_amount' => $data->depositAmount ?? $this->floatOrNull($service['deposit_amount'] ?? null),
            'no_show_fee_amount' => $data->noShowFeeAmount ?? $this->floatOrNull($service['no_show_fee_amount'] ?? null),
            'notes' => $data->notes,
            'external_reference' => $data->externalReference,
            'created_at_utc' => $this->utcNow()->format('Y-m-d H:i:s'),
            'updated_at_utc' => $this->utcNow()->format('Y-m-d H:i:s'),
            'cancelled_at_utc' => null,
            'cancellation_reason' => null,
        ]);
    }

    /**
     * Reschedule Appointment.
     */
    public function rescheduleAppointment(
        int|string $appointmentId,
        DateTimeImmutable $newStart,
        ?string $timezone = null,
    ): void {
        $appointment = $this->repository->findAppointment($appointmentId);

        if ($appointment === null) {
            throw new RuntimeException('Appointment not found.');
        }

        $service = $this->mustFindService(
            $appointment['service_id'],
            $appointment['tenant_id'] ?: null,
            $appointment['location_id'] ?: null,
        );

        $resolvedTimezone = $this->resolveTimezone($timezone ?? $appointment['timezone'] ?? $service['timezone'] ?? 'UTC');
        $localizedStart = $newStart->setTimezone($resolvedTimezone);
        $durationMinutes = (int) $service['duration_minutes'];
        $bufferBeforeMinutes = (int) $service['buffer_before_minutes'];
        $bufferAfterMinutes = (int) $service['buffer_after_minutes'];
        $newEnd = $localizedStart->modify('+' . $durationMinutes . ' minutes');
        $newOccupiedStart = $localizedStart->modify('-' . $bufferBeforeMinutes . ' minutes');
        $newOccupiedEnd = $newEnd->modify('+' . $bufferAfterMinutes . ' minutes');

        $matchingSlot = $this->findExactSlot(new SlotSearchData(
            serviceId: $appointment['service_id'],
            windowStart: $localizedStart->setTime(0, 0),
            windowEnd: $localizedStart->setTime(23, 59, 59),
            staffId: $appointment['staff_id'] ?: null,
            resourceId: $appointment['resource_id'] ?: null,
            tenantId: $appointment['tenant_id'] ?: null,
            locationId: $appointment['location_id'] ?: null,
            timezone: $resolvedTimezone->getName(),
            excludeAppointmentId: $appointmentId,
        ), $localizedStart);

        if ($matchingSlot === null) {
            throw new RuntimeException('The new appointment time is not available.');
        }

        $this->assertNoAppointmentOverlap(
            occupiedStart: $newOccupiedStart,
            occupiedEnd: $newOccupiedEnd,
            staffId: $appointment['staff_id'] ?: null,
            resourceId: $appointment['resource_id'] ?: null,
            tenantId: $appointment['tenant_id'] ?: null,
            locationId: $appointment['location_id'] ?: null,
            excludeAppointmentId: $appointmentId,
            message: 'The new appointment time overlaps an existing appointment.',
        );

        $this->repository->updateAppointment($appointmentId, [
            'starts_at_utc' => $this->toUtcString($localizedStart),
            'ends_at_utc' => $this->toUtcString($newEnd),
            'occupied_starts_at_utc' => $this->toUtcString($newOccupiedStart),
            'occupied_ends_at_utc' => $this->toUtcString($newOccupiedEnd),
            'timezone' => $resolvedTimezone->getName(),
            'status' => 'rescheduled',
            'updated_at_utc' => $this->utcNow()->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Cancel Appointment.
     */
    public function cancelAppointment(int|string $appointmentId, ?string $reason = null): void
    {
        $updated = $this->repository->updateAppointment($appointmentId, [
            'status' => 'cancelled',
            'cancelled_at_utc' => $this->utcNow()->format('Y-m-d H:i:s'),
            'cancellation_reason' => $reason,
            'updated_at_utc' => $this->utcNow()->format('Y-m-d H:i:s'),
        ]);

        if ($updated === 0) {
            throw new RuntimeException('Appointment not found.');
        }
    }

    /**
     * @return array<int, array<int, array<string, mixed>>>
     */
    private function loadRulesForSearch(SlotSearchData $search, ?string $staffId, ?string $resourceId): array
    {
        $ruleSets = [];

        if ($staffId !== null) {
            $ruleSets[] = $this->repository->fetchAvailabilityRules(
                'staff',
                $staffId,
                $search->tenantId,
                $search->locationId,
            );
        }

        if ($resourceId !== null) {
            $ruleSets[] = $this->repository->fetchAvailabilityRules(
                'resource',
                $resourceId,
                $search->tenantId,
                $search->locationId,
            );
        }

        usort(
            $ruleSets,
            static fn (array $left, array $right): int => count($left) <=> count($right),
        );

        return $ruleSets;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function loadExceptionsForSearch(SlotSearchData $search, ?string $staffId, ?string $resourceId): array
    {
        $exceptions = [];

        if ($staffId !== null) {
            $exceptions = array_merge(
                $exceptions,
                $this->repository->fetchAvailabilityExceptions(
                    'staff',
                    $staffId,
                    $search->windowStart,
                    $search->windowEnd,
                    $search->tenantId,
                    $search->locationId,
                ),
            );
        }

        if ($resourceId !== null) {
            $exceptions = array_merge(
                $exceptions,
                $this->repository->fetchAvailabilityExceptions(
                    'resource',
                    $resourceId,
                    $search->windowStart,
                    $search->windowEnd,
                    $search->tenantId,
                    $search->locationId,
                ),
            );
        }

        return $exceptions;
    }

    /**
     * @param array<int, array<string, mixed>> $exceptions
     */
    private function overlapsException(
        DateTimeImmutable $occupiedStart,
        DateTimeImmutable $occupiedEnd,
        array $exceptions,
    ): bool {
        $slotStartUtc = $occupiedStart->setTimezone(new DateTimeZone('UTC'));
        $slotEndUtc = $occupiedEnd->setTimezone(new DateTimeZone('UTC'));

        foreach ($exceptions as $exception) {
            $exceptionStart = new DateTimeImmutable($exception['starts_at_utc'], new DateTimeZone('UTC'));
            $exceptionEnd = new DateTimeImmutable($exception['ends_at_utc'], new DateTimeZone('UTC'));

            if ($slotStartUtc < $exceptionEnd && $slotEndUtc > $exceptionStart) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<int, array<string, mixed>> $appointments
     */
    private function overlapsAppointment(
        DateTimeImmutable $occupiedStart,
        DateTimeImmutable $occupiedEnd,
        array $appointments,
    ): bool {
        $slotStartUtc = $occupiedStart->setTimezone(new DateTimeZone('UTC'));
        $slotEndUtc = $occupiedEnd->setTimezone(new DateTimeZone('UTC'));

        foreach ($appointments as $appointment) {
            $appointmentStart = new DateTimeImmutable($appointment['occupied_starts_at_utc'], new DateTimeZone('UTC'));
            $appointmentEnd = new DateTimeImmutable($appointment['occupied_ends_at_utc'], new DateTimeZone('UTC'));

            if ($slotStartUtc < $appointmentEnd && $slotEndUtc > $appointmentStart) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, mixed> $rule
     */
    private function ruleAppliesOnDate(array $rule, string $localDate): bool
    {
        if (!empty($rule['valid_from_local']) && $localDate < $rule['valid_from_local']) {
            return false;
        }

        if (!empty($rule['valid_until_local']) && $localDate > $rule['valid_until_local']) {
            return false;
        }

        return true;
    }

    /**
     * @param array<int, array<int, array<string, mixed>>> $ruleSets
     */
    private function candidateCoveredByEveryOwner(
        DateTimeImmutable $slotStart,
        DateTimeImmutable $slotEnd,
        array $ruleSets,
    ): bool {
        foreach ($ruleSets as $rules) {
            $covered = false;

            foreach ($rules as $rule) {
                $ruleTimezone = $this->resolveTimezone($rule['timezone'] ?: $slotStart->getTimezone()->getName());
                $localStart = $slotStart->setTimezone($ruleTimezone);
                $localEnd = $slotEnd->setTimezone($ruleTimezone);
                $localDate = $localStart->format('Y-m-d');

                if ((int) $rule['weekday'] !== (int) $localStart->format('w')) {
                    continue;
                }

                if (!$this->ruleAppliesOnDate($rule, $localDate)) {
                    continue;
                }

                $ruleStart = new DateTimeImmutable($localDate . ' ' . $rule['start_time_local'], $ruleTimezone);
                $ruleEnd = new DateTimeImmutable($localDate . ' ' . $rule['end_time_local'], $ruleTimezone);

                if ($localStart >= $ruleStart && $localEnd <= $ruleEnd) {
                    $covered = true;
                    break;
                }
            }

            if (!$covered) {
                return false;
            }
        }

        return true;
    }

    /**
     * Find Exact Slot.
     */
    private function findExactSlot(SlotSearchData $search, DateTimeImmutable $expectedStart): ?AppointmentSlot
    {
        $expectedUtc = $expectedStart->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');

        foreach ($this->findAvailableSlots($search) as $slot) {
            if ($slot->startsAt->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s') === $expectedUtc) {
                return $slot;
            }
        }

        return null;
    }

    /**
     * Assert No Appointment Overlap.
     */
    private function assertNoAppointmentOverlap(
        DateTimeImmutable $occupiedStart,
        DateTimeImmutable $occupiedEnd,
        ?string $staffId,
        ?string $resourceId,
        ?string $tenantId = null,
        ?string $locationId = null,
        int|string|null $excludeAppointmentId = null,
        string $message = 'The requested appointment time overlaps an existing appointment.',
    ): void {
        $conflicts = $this->repository->fetchAppointmentsInRange(
            $occupiedStart->setTimezone(new DateTimeZone('UTC')),
            $occupiedEnd->setTimezone(new DateTimeZone('UTC')),
            $staffId,
            $resourceId,
            $tenantId,
            $locationId,
            $excludeAppointmentId,
        );

        if ($this->overlapsAppointment($occupiedStart, $occupiedEnd, $conflicts)) {
            throw new RuntimeException($message);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function mustFindService(int|string $serviceId, ?string $tenantId = null, ?string $locationId = null): array
    {
        $service = $this->repository->findService($serviceId, $tenantId, $locationId);

        if ($service === null) {
            throw new RuntimeException('Service not found.');
        }

        return $service;
    }

    /**
     * Assert Owner Selection.
     */
    private function assertOwnerSelection(?string $staffId, ?string $resourceId): void
    {
        if ($staffId === null && $resourceId === null) {
            throw new InvalidArgumentException('Provide either a staff ID, a resource ID, or both.');
        }
    }

    /**
     * Assert Owner Type.
     */
    private function assertOwnerType(string $ownerType): void
    {
        if (!in_array($ownerType, ['staff', 'resource'], true)) {
            throw new InvalidArgumentException('Owner type must be either "staff" or "resource".');
        }
    }

    /**
     * Resolve Timezone.
     */
    private function resolveTimezone(string $timezone): DateTimeZone
    {
        return new DateTimeZone($timezone);
    }

    /**
     * Utc Now.
     */
    private function utcNow(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }

    /**
     * To Utc String.
     */
    private function toUtcString(DateTimeImmutable $dateTime): string
    {
        return $dateTime->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
    }

    /**
     * Float Or Null.
     */
    private function floatOrNull(mixed $value): ?float
    {
        return $value === null || $value === '' ? null : (float) $value;
    }
}

