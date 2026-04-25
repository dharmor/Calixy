<?php

namespace UnifiedAppointments\Models;

use DateTimeImmutable;

final readonly class AppointmentSlot
{
    /**
     * Create a new instance.
     */
    public function __construct(
        public DateTimeImmutable $startsAt,
        public DateTimeImmutable $endsAt,
        public DateTimeImmutable $occupiedStartsAt,
        public DateTimeImmutable $occupiedEndsAt,
        public ?string $staffId = null,
        public ?string $resourceId = null,
        public ?string $tenantId = null,
        public ?string $locationId = null,
    ) {
    }

    /**
     * @return array<string, string|null>
     */
    public function toArray(): array
    {
        return [
            'starts_at' => $this->startsAt->format(DATE_ATOM),
            'ends_at' => $this->endsAt->format(DATE_ATOM),
            'occupied_starts_at' => $this->occupiedStartsAt->format(DATE_ATOM),
            'occupied_ends_at' => $this->occupiedEndsAt->format(DATE_ATOM),
            'staff_id' => $this->staffId,
            'resource_id' => $this->resourceId,
            'tenant_id' => $this->tenantId,
            'location_id' => $this->locationId,
        ];
    }
}

