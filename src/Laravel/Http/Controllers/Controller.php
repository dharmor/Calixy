<?php

namespace UnifiedAppointments\Laravel\Http\Controllers;

use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

/**
 * Controller.
 */
abstract class Controller extends \Illuminate\Routing\Controller
{
    /**
     * @param callable(): JsonResponse $callback
     */
    protected function action(callable $callback): JsonResponse
    {
        try {
            return $callback();
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), 422);
        } catch (RuntimeException $exception) {
            return $this->error($exception->getMessage(), $this->runtimeStatus($exception));
        } catch (Throwable $exception) {
            return $this->error('An unexpected error occurred while processing the appointment request.', 500);
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function ok(array $data = [], ?string $message = null, int $status = 200): JsonResponse
    {
        return new JsonResponse([
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function created(array $data = [], ?string $message = null): JsonResponse
    {
        return $this->ok($data, $message, 201);
    }

    /**
     * @param array<string, mixed> $errors
     */
    protected function error(string $message, int $status, array $errors = []): JsonResponse
    {
        return new JsonResponse([
            'message' => $message,
            'errors' => $errors,
        ], $status);
    }

    /**
     * Parse Date Time.
     */
    protected function parseDateTime(string $value, ?string $timezone = null): DateTimeImmutable
    {
        return new DateTimeImmutable($value, new DateTimeZone($timezone ?? $this->defaultTimezone()));
    }

    /**
     * Default Timezone.
     */
    protected function defaultTimezone(): string
    {
        return (string) config('unified-appointments.app_timezone', 'UTC');
    }

    /**
     * String Or Null.
     */
    protected function stringOrNull(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (string) $value;
    }

    /**
     * Int Or Null.
     */
    protected function intOrNull(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    /**
     * Float Or Null.
     */
    protected function floatOrNull(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (float) $value;
    }

    /**
     * Runtime Status.
     */
    protected function runtimeStatus(RuntimeException $exception): int
    {
        $message = strtolower($exception->getMessage());

        if (str_contains($message, 'not found')) {
            return 404;
        }

        if (str_contains($message, 'no longer available') || str_contains($message, 'not available')) {
            return 409;
        }

        return 422;
    }
}

