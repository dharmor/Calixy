<?php

namespace UnifiedAppointments\DTO;

use DateTimeImmutable;

final readonly class SlotSearchData
{
    /**
     * Create a new instance.
     */
    public function __construct(
        public int|string $serviceId,
        public DateTimeImmutable $windowStart,
        public DateTimeImmutable $windowEnd,
        public ?string $staffId = null,
        public ?string $resourceId = null,
        public ?string $tenantId = null,
        public ?string $locationId = null,
        public ?string $timezone = null,
        public ?int $slotIntervalMinutes = null,
        public int|string|null $excludeAppointmentId = null,
    ) {
    }
}

