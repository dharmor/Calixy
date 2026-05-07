<?php

namespace UnifiedAppointments\DTO;

final readonly class ServiceData
{
    /**
     * Create a new instance.
     */
    public function __construct(
        public string $name,
        public int $durationMinutes,
        public int $bufferBeforeMinutes = 0,
        public int $bufferAfterMinutes = 0,
        public int $slotIntervalMinutes = 30,
        public int $leadTimeMinutes = 0,
        public int $maxAdvanceDays = 90,
        public ?string $tenantId = null,
        public ?string $locationId = null,
        public string $timezone = 'UTC',
        public ?string $depositType = null,
        public ?float $depositAmount = null,
        public ?float $noShowFeeAmount = null,
    ) {
    }
}

