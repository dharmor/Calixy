<?php

namespace UnifiedAppointments\DTO;

final readonly class AvailabilityRuleData
{
    public function __construct(
        public string $ownerType,
        public string $ownerId,
        public int $weekday,
        public string $startTimeLocal,
        public string $endTimeLocal,
        public ?int $slotIntervalMinutes = null,
        public ?string $validFromLocal = null,
        public ?string $validUntilLocal = null,
        public ?string $tenantId = null,
        public ?string $locationId = null,
        public string $timezone = 'UTC',
    ) {
    }
}
