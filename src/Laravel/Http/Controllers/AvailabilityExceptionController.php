<?php

namespace UnifiedAppointments\Laravel\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use UnifiedAppointments\DTO\AvailabilityExceptionData;
use UnifiedAppointments\Services\AppointmentScheduler;

final class AvailabilityExceptionController extends Controller
{
    public function store(Request $request, AppointmentScheduler $scheduler): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'tenant_id' => ['nullable', 'string', 'max:191'],
            'location_id' => ['nullable', 'string', 'max:191'],
            'owner_type' => ['required', 'string', 'in:staff,resource'],
            'owner_id' => ['required', 'string', 'max:191'],
            'exception_type' => ['nullable', 'string', 'max:50'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date'],
            'timezone' => ['nullable', 'timezone'],
            'notes' => ['nullable', 'string'],
        ]);

        $validator->after(function ($validator) use ($request): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $timezone = $this->stringOrNull($request->input('timezone')) ?? $this->defaultTimezone();
            $startsAt = $this->parseDateTime((string) $request->input('starts_at'), $timezone);
            $endsAt = $this->parseDateTime((string) $request->input('ends_at'), $timezone);

            if ($endsAt <= $startsAt) {
                $validator->errors()->add('ends_at', 'The end time must be after the start time.');
            }
        });

        $validated = $validator->validate();

        return $this->action(function () use ($validated, $scheduler): JsonResponse {
            $timezone = (string) ($validated['timezone'] ?? $this->defaultTimezone());
            $id = $scheduler->addAvailabilityException(new AvailabilityExceptionData(
                ownerType: (string) $validated['owner_type'],
                ownerId: (string) $validated['owner_id'],
                startsAt: $this->parseDateTime((string) $validated['starts_at'], $timezone),
                endsAt: $this->parseDateTime((string) $validated['ends_at'], $timezone),
                exceptionType: (string) ($validated['exception_type'] ?? 'blackout'),
                tenantId: $this->stringOrNull($validated['tenant_id'] ?? null),
                locationId: $this->stringOrNull($validated['location_id'] ?? null),
                timezone: $timezone,
                notes: $this->stringOrNull($validated['notes'] ?? null),
            ));

            return $this->created([
                'id' => (string) $id,
            ], 'Availability exception created.');
        });
    }
}
