<?php

namespace UnifiedAppointments\Laravel\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use UnifiedAppointments\DTO\ServiceData;
use UnifiedAppointments\Services\AppointmentScheduler;

/**
 * ServiceController.
 */
final class ServiceController extends Controller
{
    /**
     * Store.
     */
    public function store(Request $request, AppointmentScheduler $scheduler): JsonResponse
    {
        $validated = Validator::make($request->all(), [
            'tenant_id' => ['nullable', 'string', 'max:191'],
            'location_id' => ['nullable', 'string', 'max:191'],
            'name' => ['required', 'string', 'max:191'],
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'buffer_before_minutes' => ['nullable', 'integer', 'min:0', 'max:1440'],
            'buffer_after_minutes' => ['nullable', 'integer', 'min:0', 'max:1440'],
            'slot_interval_minutes' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'lead_time_minutes' => ['nullable', 'integer', 'min:0', 'max:10080'],
            'max_advance_days' => ['nullable', 'integer', 'min:0', 'max:3650'],
            'deposit_type' => ['nullable', 'string', 'in:fixed,percentage'],
            'deposit_amount' => ['nullable', 'numeric', 'min:0'],
            'no_show_fee_amount' => ['nullable', 'numeric', 'min:0'],
            'timezone' => ['nullable', 'timezone'],
        ])->validate();

        return $this->action(function () use ($validated, $scheduler): JsonResponse {
            $id = $scheduler->createService(new ServiceData(
                name: (string) $validated['name'],
                durationMinutes: (int) $validated['duration_minutes'],
                bufferBeforeMinutes: (int) ($validated['buffer_before_minutes'] ?? 0),
                bufferAfterMinutes: (int) ($validated['buffer_after_minutes'] ?? 0),
                slotIntervalMinutes: (int) ($validated['slot_interval_minutes'] ?? 30),
                leadTimeMinutes: (int) ($validated['lead_time_minutes'] ?? 0),
                maxAdvanceDays: (int) ($validated['max_advance_days'] ?? 90),
                tenantId: $this->stringOrNull($validated['tenant_id'] ?? null),
                locationId: $this->stringOrNull($validated['location_id'] ?? null),
                timezone: (string) ($validated['timezone'] ?? $this->defaultTimezone()),
                depositType: $this->stringOrNull($validated['deposit_type'] ?? null),
                depositAmount: $this->floatOrNull($validated['deposit_amount'] ?? null),
                noShowFeeAmount: $this->floatOrNull($validated['no_show_fee_amount'] ?? null),
            ));

            return $this->created([
                'id' => (string) $id,
            ], 'Service created.');
        });
    }
}

