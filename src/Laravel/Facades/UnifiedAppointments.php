<?php

namespace UnifiedAppointments\Laravel\Facades;

/**
 * @method static void install()
 * @method static int|string createService(\UnifiedAppointments\DTO\ServiceData $data)
 * @method static int|string addAvailabilityRule(\UnifiedAppointments\DTO\AvailabilityRuleData $data)
 * @method static int|string addAvailabilityException(\UnifiedAppointments\DTO\AvailabilityExceptionData $data)
 * @method static int|string addToWaitlist(\UnifiedAppointments\DTO\WaitlistEntryData $data)
 * @method static array<int, \UnifiedAppointments\Models\AppointmentSlot> findAvailableSlots(\UnifiedAppointments\DTO\SlotSearchData $search)
 * @method static int|string bookAppointment(\UnifiedAppointments\DTO\BookAppointmentData $data)
 * @method static void rescheduleAppointment(int|string $appointmentId, \DateTimeImmutable $newStart, ?string $timezone = null)
 * @method static void cancelAppointment(int|string $appointmentId, ?string $reason = null)
 *
 * @see \UnifiedAppointments\Services\AppointmentScheduler
 */
final class UnifiedAppointments extends \Illuminate\Support\Facades\Facade
{
    /**
     * Get Facade Accessor.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'unified-appointments';
    }
}

