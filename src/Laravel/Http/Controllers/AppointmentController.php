<?php

namespace UnifiedAppointments\Laravel\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use UnifiedAppointments\DTO\BookAppointmentData;
use UnifiedAppointments\Services\AppointmentScheduler;

/**
 * AppointmentController.
 */
final class AppointmentController extends Controller
{
    /**
     * Store.
     */
    public function store(Request $request, AppointmentScheduler $scheduler): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'service_id' => ['required'],
            'starts_at' => ['required', 'date'],
            'customer_name' => ['required', 'string', 'max:191'],
            'staff_id' => ['nullable', 'string', 'max:191'],
            'resource_id' => ['nullable', 'string', 'max:191'],
            'customer_email' => ['nullable', 'email', 'max:191'],
            'customer_phone' => ['nullable', 'string', 'max:50'],
            'tenant_id' => ['nullable', 'string', 'max:191'],
            'location_id' => ['nullable', 'string', 'max:191'],
            'timezone' => ['nullable', 'timezone'],
            'status' => ['nullable', 'string', 'in:pending,confirmed'],
            'deposit_amount' => ['nullable', 'numeric', 'min:0'],
            'no_show_fee_amount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'external_reference' => ['nullable', 'string', 'max:191'],
        ]);

        $validator->after(function ($validator) use ($request): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            if ($this->stringOrNull($request->input('staff_id')) === null && $this->stringOrNull($request->input('resource_id')) === null) {
                $validator->errors()->add('staff_id', 'Provide either staff_id or resource_id.');
            }
        });

        $validated = $validator->validate();

        return $this->action(function () use ($validated, $scheduler): JsonResponse {
            $timezone = $this->stringOrNull($validated['timezone'] ?? null);
            $id = $scheduler->bookAppointment(new BookAppointmentData(
                serviceId: $validated['service_id'],
                startsAt: $this->parseDateTime((string) $validated['starts_at'], $timezone ?? $this->defaultTimezone()),
                customerName: (string) $validated['customer_name'],
                staffId: $this->stringOrNull($validated['staff_id'] ?? null),
                resourceId: $this->stringOrNull($validated['resource_id'] ?? null),
                customerEmail: $this->stringOrNull($validated['customer_email'] ?? null),
                customerPhone: $this->stringOrNull($validated['customer_phone'] ?? null),
                tenantId: $this->stringOrNull($validated['tenant_id'] ?? null),
                locationId: $this->stringOrNull($validated['location_id'] ?? null),
                timezone: $timezone,
                status: (string) ($validated['status'] ?? 'confirmed'),
                depositAmount: $this->floatOrNull($validated['deposit_amount'] ?? null),
                noShowFeeAmount: $this->floatOrNull($validated['no_show_fee_amount'] ?? null),
                notes: $this->stringOrNull($validated['notes'] ?? null),
                externalReference: $this->stringOrNull($validated['external_reference'] ?? null),
            ));

            return $this->created([
                'id' => (string) $id,
            ], 'Appointment booked.');
        });
    }

    /**
     * Reschedule.
     */
    public function reschedule(string $appointment, Request $request, AppointmentScheduler $scheduler): JsonResponse
    {
        $validated = Validator::make($request->all(), [
            'starts_at' => ['required', 'date'],
            'timezone' => ['nullable', 'timezone'],
        ])->validate();

        return $this->action(function () use ($appointment, $validated, $scheduler): JsonResponse {
            $timezone = $this->stringOrNull($validated['timezone'] ?? null);
            $scheduler->rescheduleAppointment(
                appointmentId: $appointment,
                newStart: $this->parseDateTime((string) $validated['starts_at'], $timezone ?? $this->defaultTimezone()),
                timezone: $timezone,
            );

            return $this->ok([], 'Appointment rescheduled.');
        });
    }

    /**
     * Cancel.
     */
    public function cancel(string $appointment, Request $request, AppointmentScheduler $scheduler): JsonResponse
    {
        $validated = Validator::make($request->all(), [
            'reason' => ['nullable', 'string'],
        ])->validate();

        return $this->action(function () use ($appointment, $validated, $scheduler): JsonResponse {
            $scheduler->cancelAppointment(
                appointmentId: $appointment,
                reason: $this->stringOrNull($validated['reason'] ?? null),
            );

            return $this->ok([], 'Appointment cancelled.');
        });
    }
}

