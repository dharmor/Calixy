<?php

namespace UnifiedAppointments\Laravel\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use UnifiedAppointments\DTO\WaitlistEntryData;
use UnifiedAppointments\Services\AppointmentScheduler;

final class WaitlistController extends Controller
{
    public function store(Request $request, AppointmentScheduler $scheduler): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'service_id' => ['required'],
            'preferred_start' => ['required', 'date'],
            'preferred_end' => ['required', 'date'],
            'customer_name' => ['required', 'string', 'max:191'],
            'staff_id' => ['nullable', 'string', 'max:191'],
            'resource_id' => ['nullable', 'string', 'max:191'],
            'customer_email' => ['nullable', 'email', 'max:191'],
            'customer_phone' => ['nullable', 'string', 'max:50'],
            'tenant_id' => ['nullable', 'string', 'max:191'],
            'location_id' => ['nullable', 'string', 'max:191'],
            'timezone' => ['nullable', 'timezone'],
            'status' => ['nullable', 'string', 'in:waiting,notified,expired'],
        ]);

        $validator->after(function ($validator) use ($request): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $timezone = $this->stringOrNull($request->input('timezone')) ?? $this->defaultTimezone();
            $preferredStart = $this->parseDateTime((string) $request->input('preferred_start'), $timezone);
            $preferredEnd = $this->parseDateTime((string) $request->input('preferred_end'), $timezone);

            if ($preferredEnd <= $preferredStart) {
                $validator->errors()->add('preferred_end', 'The preferred end must be after the preferred start.');
            }
        });

        $validated = $validator->validate();

        return $this->action(function () use ($validated, $scheduler): JsonResponse {
            $timezone = (string) ($validated['timezone'] ?? $this->defaultTimezone());
            $id = $scheduler->addToWaitlist(new WaitlistEntryData(
                serviceId: $validated['service_id'],
                preferredStart: $this->parseDateTime((string) $validated['preferred_start'], $timezone),
                preferredEnd: $this->parseDateTime((string) $validated['preferred_end'], $timezone),
                customerName: (string) $validated['customer_name'],
                staffId: $this->stringOrNull($validated['staff_id'] ?? null),
                resourceId: $this->stringOrNull($validated['resource_id'] ?? null),
                customerEmail: $this->stringOrNull($validated['customer_email'] ?? null),
                customerPhone: $this->stringOrNull($validated['customer_phone'] ?? null),
                tenantId: $this->stringOrNull($validated['tenant_id'] ?? null),
                locationId: $this->stringOrNull($validated['location_id'] ?? null),
                timezone: $timezone,
                status: (string) ($validated['status'] ?? 'waiting'),
            ));

            return $this->created([
                'id' => (string) $id,
            ], 'Waitlist entry created.');
        });
    }
}
