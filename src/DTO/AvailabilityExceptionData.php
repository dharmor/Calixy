<?php

namespace UnifiedAppointments\DTO;

use DateTimeImmutable;

final readonly class AvailabilityExceptionData
{
    public function __construct(
        public string $ownerType,
        public string $ownerId,
        public DateTimeImmutable $startsAt,
        public DateTimeImmutable $endsAt,
        public string $exceptionType = 'blackout',
        public ?string $tenantId = null,
        public ?string $locationId = null,
        public string $timezone = 'UTC',
        public ?string $notes = null,
    ) {
    }
}
