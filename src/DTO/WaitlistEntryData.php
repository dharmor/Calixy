<?php

namespace UnifiedAppointments\DTO;

use DateTimeImmutable;

final readonly class WaitlistEntryData
{
    /**
     * Create a new instance.
     */
    public function __construct(
        public int|string $serviceId,
        public DateTimeImmutable $preferredStart,
        public DateTimeImmutable $preferredEnd,
        public string $customerName,
        public ?string $staffId = null,
        public ?string $resourceId = null,
        public ?string $customerEmail = null,
        public ?string $customerPhone = null,
        public ?string $tenantId = null,
        public ?string $locationId = null,
        public string $timezone = 'UTC',
        public string $status = 'waiting',
    ) {
    }
}

