<?php

namespace UnifiedAppointments\Laravel\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use UnifiedAppointments\DTO\AvailabilityRuleData;
use UnifiedAppointments\Services\AppointmentScheduler;

final class AvailabilityRuleController extends Controller
{
    public function store(Request $request, AppointmentScheduler $scheduler): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'tenant_id' => ['nullable', 'string', 'max:191'],
            'location_id' => ['nullable', 'string', 'max:191'],
            'owner_type' => ['required', 'string', 'in:staff,resource'],
            'owner_id' => ['required', 'string', 'max:191'],
            'weekday' => ['required', 'integer', 'min:0', 'max:6'],
            'start_time_local' => ['required', 'date_format:H:i'],
            'end_time_local' => ['required', 'date_format:H:i'],
            'slot_interval_minutes' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'valid_from_local' => ['nullable', 'date_format:Y-m-d'],
            'valid_until_local' => ['nullable', 'date_format:Y-m-d'],
            'timezone' => ['nullable', 'timezone'],
        ]);

        $validator->after(function ($validator) use ($request): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            if ((string) $request->input('end_time_local') <= (string) $request->input('start_time_local')) {
                $validator->errors()->add('end_time_local', 'The end time must be after the start time.');
            }

            $validFrom = $request->input('valid_from_local');
            $validUntil = $request->input('valid_until_local');

            if ($validFrom !== null && $validUntil !== null && (string) $validUntil < (string) $validFrom) {
                $validator->errors()->add('valid_until_local', 'The valid-until date must be on or after the valid-from date.');
            }
        });

        $validated = $validator->validate();

        return $this->action(function () use ($validated, $scheduler): JsonResponse {
            $id = $scheduler->addAvailabilityRule(new AvailabilityRuleData(
                ownerType: (string) $validated['owner_type'],
                ownerId: (string) $validated['owner_id'],
                weekday: (int) $validated['weekday'],
                startTimeLocal: (string) $validated['start_time_local'],
                endTimeLocal: (string) $validated['end_time_local'],
                slotIntervalMinutes: $this->intOrNull($validated['slot_interval_minutes'] ?? null),
                validFromLocal: $this->stringOrNull($validated['valid_from_local'] ?? null),
                validUntilLocal: $this->stringOrNull($validated['valid_until_local'] ?? null),
                tenantId: $this->stringOrNull($validated['tenant_id'] ?? null),
                locationId: $this->stringOrNull($validated['location_id'] ?? null),
                timezone: (string) ($validated['timezone'] ?? $this->defaultTimezone()),
            ));

            return $this->created([
                'id' => (string) $id,
            ], 'Availability rule created.');
        });
    }
}
