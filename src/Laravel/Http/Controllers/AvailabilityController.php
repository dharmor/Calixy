<?php

namespace UnifiedAppointments\Laravel\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use UnifiedAppointments\DTO\SlotSearchData;
use UnifiedAppointments\Services\AppointmentScheduler;

final class AvailabilityController extends Controller
{
    public function index(Request $request, AppointmentScheduler $scheduler): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'service_id' => ['required'],
            'window_start' => ['required', 'date'],
            'window_end' => ['required', 'date'],
            'staff_id' => ['nullable', 'string', 'max:191'],
            'resource_id' => ['nullable', 'string', 'max:191'],
            'tenant_id' => ['nullable', 'string', 'max:191'],
            'location_id' => ['nullable', 'string', 'max:191'],
            'timezone' => ['nullable', 'timezone'],
            'slot_interval_minutes' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'exclude_appointment_id' => ['nullable'],
        ]);

        $validator->after(function ($validator) use ($request): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            if ($this->stringOrNull($request->input('staff_id')) === null && $this->stringOrNull($request->input('resource_id')) === null) {
                $validator->errors()->add('staff_id', 'Provide either staff_id or resource_id.');
            }

            $timezone = $this->stringOrNull($request->input('timezone')) ?? $this->defaultTimezone();
            $windowStart = $this->parseDateTime((string) $request->input('window_start'), $timezone);
            $windowEnd = $this->parseDateTime((string) $request->input('window_end'), $timezone);

            if ($windowEnd <= $windowStart) {
                $validator->errors()->add('window_end', 'The search window end must be after the start.');
            }
        });

        $validated = $validator->validate();

        return $this->action(function () use ($validated, $scheduler): JsonResponse {
            $timezone = $this->stringOrNull($validated['timezone'] ?? null);
            $slots = $scheduler->findAvailableSlots(new SlotSearchData(
                serviceId: $validated['service_id'],
                windowStart: $this->parseDateTime((string) $validated['window_start'], $timezone ?? $this->defaultTimezone()),
                windowEnd: $this->parseDateTime((string) $validated['window_end'], $timezone ?? $this->defaultTimezone()),
                staffId: $this->stringOrNull($validated['staff_id'] ?? null),
                resourceId: $this->stringOrNull($validated['resource_id'] ?? null),
                tenantId: $this->stringOrNull($validated['tenant_id'] ?? null),
                locationId: $this->stringOrNull($validated['location_id'] ?? null),
                timezone: $timezone,
                slotIntervalMinutes: $this->intOrNull($validated['slot_interval_minutes'] ?? null),
                excludeAppointmentId: $validated['exclude_appointment_id'] ?? null,
            ));

            return $this->ok([
                'slots' => array_map(
                    static fn ($slot): array => $slot->toArray(),
                    $slots,
                ),
                'count' => count($slots),
            ], 'Available slots loaded.');
        });
    }
}
