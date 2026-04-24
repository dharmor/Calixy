<?php

namespace UnifiedAppointments\DTO;

use DateTimeImmutable;

final readonly class BookAppointmentData
{
    public function __construct(
        public int|string $serviceId,
        public DateTimeImmutable $startsAt,
        public string $customerName,
        public ?string $staffId = null,
        public ?string $resourceId = null,
        public ?string $customerEmail = null,
        public ?string $customerPhone = null,
        public ?string $tenantId = null,
        public ?string $locationId = null,
        public ?string $timezone = null,
        public string $status = 'confirmed',
        public ?float $depositAmount = null,
        public ?float $noShowFeeAmount = null,
        public ?string $notes = null,
        public ?string $externalReference = null,
    ) {
    }
}
