<?php

namespace UnifiedAppointments\Repositories;

use DateTimeImmutable;
use DateTimeZone;
use RuntimeException;
use UnifiedAppointments\Config\UnifiedAppointmentsConfig;
use UnifiedAppointments\Database\UnifiedDatabaseConnector;
use UnifiedAppointments\DTO\AvailabilityExceptionData;
use UnifiedAppointments\DTO\AvailabilityRuleData;
use UnifiedAppointments\DTO\ServiceData;
use UnifiedAppointments\DTO\WaitlistEntryData;

/**
 * AppointmentRepository.
 */
final class AppointmentRepository
{
    /**
     * Create a new instance.
     */
    public function __construct(
        private readonly UnifiedDatabaseConnector $connector,
        private readonly UnifiedAppointmentsConfig $config,
    ) {
    }

    /**
     * Create Service.
     */
    public function createService(ServiceData $data): int|string
    {
        return $this->insert($this->table('services'), [
            'tenant_id' => $data->tenantId,
            'location_id' => $data->locationId,
            'name' => $data->name,
            'duration_minutes' => $data->durationMinutes,
            'buffer_before_minutes' => $data->bufferBeforeMinutes,
            'buffer_after_minutes' => $data->bufferAfterMinutes,
            'slot_interval_minutes' => $data->slotIntervalMinutes,
            'lead_time_minutes' => $data->leadTimeMinutes,
            'max_advance_days' => $data->maxAdvanceDays,
            'deposit_type' => $data->depositType,
            'deposit_amount' => $data->depositAmount,
            'no_show_fee_amount' => $data->noShowFeeAmount,
            'timezone' => $data->timezone,
            'created_at_utc' => $this->nowUtc(),
            'updated_at_utc' => $this->nowUtc(),
        ]);
    }

    /**
     * Create Availability Rule.
     */
    public function createAvailabilityRule(AvailabilityRuleData $data): int|string
    {
        return $this->insert($this->table('availability_rules'), [
            'tenant_id' => $data->tenantId,
            'location_id' => $data->locationId,
            'owner_type' => $data->ownerType,
            'owner_id' => $data->ownerId,
            'weekday' => $data->weekday,
            'start_time_local' => $data->startTimeLocal,
            'end_time_local' => $data->endTimeLocal,
            'slot_interval_minutes' => $data->slotIntervalMinutes,
            'valid_from_local' => $data->validFromLocal,
            'valid_until_local' => $data->validUntilLocal,
            'timezone' => $data->timezone,
            'created_at_utc' => $this->nowUtc(),
            'updated_at_utc' => $this->nowUtc(),
        ]);
    }

    /**
     * Create Availability Exception.
     */
    public function createAvailabilityException(AvailabilityExceptionData $data): int|string
    {
        return $this->insert($this->table('availability_exceptions'), [
            'tenant_id' => $data->tenantId,
            'location_id' => $data->locationId,
            'owner_type' => $data->ownerType,
            'owner_id' => $data->ownerId,
            'exception_type' => $data->exceptionType,
            'starts_at_utc' => $this->toUtcString($data->startsAt),
            'ends_at_utc' => $this->toUtcString($data->endsAt),
            'timezone' => $data->timezone,
            'notes' => $data->notes,
            'created_at_utc' => $this->nowUtc(),
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function createAppointment(array $payload): int|string
    {
        return $this->insert($this->table('appointments'), $payload);
    }

    /**
     * Add To Waitlist.
     */
    public function addToWaitlist(WaitlistEntryData $data): int|string
    {
        return $this->insert($this->table('waitlist_entries'), [
            'tenant_id' => $data->tenantId,
            'location_id' => $data->locationId,
            'service_id' => $data->serviceId,
            'staff_id' => $data->staffId,
            'resource_id' => $data->resourceId,
            'customer_name' => $data->customerName,
            'customer_email' => $data->customerEmail,
            'customer_phone' => $data->customerPhone,
            'preferred_start_utc' => $this->toUtcString($data->preferredStart),
            'preferred_end_utc' => $this->toUtcString($data->preferredEnd),
            'timezone' => $data->timezone,
            'status' => $data->status,
            'notified_at_utc' => null,
            'created_at_utc' => $this->nowUtc(),
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function createLocation(array $payload): int|string
    {
        return $this->insert($this->table('locations'), array_merge([
            'contact_name' => null,
            'email' => null,
            'phone' => null,
            'address_line_1' => null,
            'address_line_2' => null,
            'city' => null,
            'state_province' => null,
            'postal_code' => null,
            'country' => null,
        ], $payload, [
            'created_at_utc' => $this->nowUtc(),
            'updated_at_utc' => $this->nowUtc(),
        ]));
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function updateLocation(string $locationKey, array $payload): void
    {
        $record = $this->findLocation($locationKey);

        if ($record === null) {
            throw new RuntimeException('Location not found.');
        }

        $this->updateById($this->table('locations'), $record['id'], array_merge([
            'contact_name' => null,
            'email' => null,
            'phone' => null,
            'address_line_1' => null,
            'address_line_2' => null,
            'city' => null,
            'state_province' => null,
            'postal_code' => null,
            'country' => null,
        ], $payload, [
            'updated_at_utc' => $this->nowUtc(),
        ]));
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function createTeamMember(array $payload): int|string
    {
        return $this->insert($this->table('team_members'), array_merge([
            'location_key' => null,
            'email' => null,
            'phone' => null,
            'owner_type' => 'staff',
            'is_active' => 1,
        ], $payload, [
            'created_at_utc' => $this->nowUtc(),
            'updated_at_utc' => $this->nowUtc(),
        ]));
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function updateTeamMember(string $memberKey, array $payload): void
    {
        $record = $this->findTeamMember($memberKey);

        if ($record === null) {
            throw new RuntimeException('Team member not found.');
        }

        $this->updateById($this->table('team_members'), $record['id'], array_merge([
            'location_key' => null,
            'email' => null,
            'phone' => null,
            'owner_type' => 'staff',
            'is_active' => 1,
        ], $payload, [
            'updated_at_utc' => $this->nowUtc(),
        ]));
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function saveNotificationSetting(string $channel, array $payload): void
    {
        $record = $this->findNotificationSetting($channel);

        $changes = array_merge($payload, [
            'updated_at_utc' => $this->nowUtc(),
        ]);

        if ($record === null) {
            $this->insert($this->table('notification_settings'), array_merge([
                'channel' => $channel,
                'created_at_utc' => $this->nowUtc(),
            ], $changes));

            return;
        }

        $this->updateById($this->table('notification_settings'), $record['id'], $changes);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function createEmailDispatch(array $payload): int|string
    {
        return $this->insert($this->table('email_dispatches'), array_merge([
            'related_appointment_id' => null,
            'recipient' => null,
            'request_payload' => null,
            'response_payload' => null,
            'error_message' => null,
        ], $payload, [
            'created_at_utc' => $this->nowUtc(),
        ]));
    }

    /**
     * Upsert System Config.
     */
    public function upsertSystemConfig(string $key, ?string $value, ?string $group = null): void
    {
        $record = $this->findByFilters($this->table('system_config'), ['config_key' => $key]);
        $changes = [
            'config_value' => $value,
            'config_group' => $group,
            'updated_at_utc' => $this->nowUtc(),
        ];

        if ($record === null) {
            $this->insert($this->table('system_config'), array_merge([
                'config_key' => $key,
                'created_at_utc' => $this->nowUtc(),
            ], $changes));

            return;
        }

        $this->updateById($this->table('system_config'), $record['id'], $changes);
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    public function replaceTimezones(array $rows): void
    {
        $this->connector->execute(
            sprintf('DELETE FROM %s', $this->quotedTable('timezones')),
            [],
        );

        foreach ($rows as $row) {
            $this->insert($this->table('timezones'), [
                'region' => $row['region'],
                'timezone_key' => $row['timezone_key'],
                'label' => $row['label'],
                'sort_order' => $row['sort_order'],
                'created_at_utc' => $this->nowUtc(),
            ]);
        }
    }

    /**
     * Timezone Count.
     */
    public function timezoneCount(): int
    {
        $result = $this->connector->first(
            sprintf('SELECT COUNT(*) AS %s FROM %s', $this->quote('aggregate_count'), $this->quotedTable('timezones')),
        );

        return (int) ($result['aggregate_count'] ?? 0);
    }

    /**
     * Get System Config.
     */
    public function getSystemConfig(string $key): ?string
    {
        $record = $this->findByFilters($this->table('system_config'), ['config_key' => $key]);

        if ($record === null) {
            return null;
        }

        return $record['config_value'] === null ? null : (string) $record['config_value'];
    }

    /**
     * Has System Config.
     */
    public function hasSystemConfig(string $key): bool
    {
        return $this->findByFilters($this->table('system_config'), ['config_key' => $key]) !== null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listSystemConfig(?string $group = null): array
    {
        [$filters, $params] = $this->optionalFilters([
            'config_group' => $group,
        ]);

        return $this->connector->select(
            sprintf(
                'SELECT * FROM %s%s ORDER BY %s ASC',
                $this->quotedTable('system_config'),
                $this->whereClause($filters),
                $this->quote('config_key'),
            ),
            $params,
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listTimezones(): array
    {
        return $this->connector->select(
            sprintf(
                'SELECT * FROM %s ORDER BY %s ASC, %s ASC',
                $this->quotedTable('timezones'),
                $this->quote('sort_order'),
                $this->quote('timezone_key'),
            ),
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listLocations(): array
    {
        return $this->connector->select(
            sprintf(
                'SELECT * FROM %s ORDER BY %s ASC',
                $this->quotedTable('locations'),
                $this->quote('name'),
            ),
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findLocation(string $locationKey): ?array
    {
        return $this->findByFilters($this->table('locations'), ['location_key' => $locationKey]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listTeamMembers(): array
    {
        return $this->connector->select(
            sprintf(
                'SELECT * FROM %s ORDER BY %s DESC, %s ASC',
                $this->quotedTable('team_members'),
                $this->quote('is_active'),
                $this->quote('name'),
            ),
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findTeamMember(string $memberKey): ?array
    {
        return $this->findByFilters($this->table('team_members'), ['member_key' => $memberKey]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listNotificationSettings(): array
    {
        return $this->connector->select(
            sprintf(
                'SELECT * FROM %s ORDER BY %s ASC',
                $this->quotedTable('notification_settings'),
                $this->quote('channel'),
            ),
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findNotificationSetting(string $channel): ?array
    {
        return $this->findByFilters($this->table('notification_settings'), ['channel' => $channel]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listEmailDispatches(int $limit = 20): array
    {
        $rows = $this->connector->select(
            sprintf(
                'SELECT * FROM %s ORDER BY %s DESC',
                $this->quotedTable('email_dispatches'),
                $this->quote('created_at_utc'),
            ),
        );

        return array_slice($rows, 0, max(1, $limit));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listDueReminderAppointments(DateTimeImmutable $nowUtc, int $limit = 25): array
    {
        $rows = $this->connector->select(
            sprintf(
                'SELECT * FROM %s WHERE %s IS NOT NULL AND %s = :reminder_status AND %s <= :send_at AND %s <> :cancelled_status ORDER BY %s ASC',
                $this->quotedTable('appointments'),
                $this->quote('reminder_send_at_utc'),
                $this->quote('reminder_status'),
                $this->quote('reminder_send_at_utc'),
                $this->quote('status'),
                $this->quote('reminder_send_at_utc'),
            ),
            [
                'reminder_status' => 'pending',
                'send_at' => $this->toUtcString($nowUtc),
                'cancelled_status' => 'cancelled',
            ],
        );

        return array_slice($rows, 0, max(1, $limit));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listCalendarConnections(?string $ownerType = null, ?string $ownerId = null): array
    {
        [$filters, $params] = $this->optionalFilters([
            'owner_type' => $ownerType,
            'owner_id' => $ownerId,
        ]);

        return $this->connector->select(
            sprintf(
                'SELECT * FROM %s%s ORDER BY %s ASC',
                $this->quotedTable('calendar_connections'),
                $this->whereClause($filters),
                $this->quote('provider'),
            ),
            $params,
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findCalendarConnection(string $provider, string $ownerType = 'system', string $ownerId = 'starter-app'): ?array
    {
        return $this->findByFilters($this->table('calendar_connections'), [
            'owner_type' => $ownerType,
            'owner_id' => $ownerId,
            'provider' => $provider,
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function upsertCalendarConnection(array $payload): void
    {
        $ownerType = (string) ($payload['owner_type'] ?? 'system');
        $ownerId = (string) ($payload['owner_id'] ?? 'starter-app');
        $provider = (string) ($payload['provider'] ?? '');
        $record = $this->findCalendarConnection($provider, $ownerType, $ownerId);
        $changes = array_merge($payload, [
            'updated_at_utc' => $this->nowUtc(),
        ]);

        if ($record === null) {
            $this->insert($this->table('calendar_connections'), array_merge([
                'tenant_id' => null,
                'location_id' => null,
                'access_token_encrypted' => null,
                'refresh_token_encrypted' => null,
                'expires_at_utc' => null,
                'created_at_utc' => $this->nowUtc(),
            ], $changes));

            return;
        }

        $this->updateById($this->table('calendar_connections'), $record['id'], $changes);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listServices(?string $tenantId = null, ?string $locationId = null): array
    {
        [$filters, $params] = $this->optionalFilters([
            'tenant_id' => $tenantId,
            'location_id' => $locationId,
        ]);

        return $this->connector->select(
            sprintf(
                'SELECT * FROM %s%s ORDER BY %s ASC',
                $this->quotedTable('services'),
                $this->whereClause($filters),
                $this->quote('name'),
            ),
            $params,
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listAvailabilityRules(?string $tenantId = null, ?string $locationId = null): array
    {
        [$filters, $params] = $this->optionalFilters([
            'tenant_id' => $tenantId,
            'location_id' => $locationId,
        ]);

        return $this->connector->select(
            sprintf(
                'SELECT * FROM %s%s ORDER BY %s ASC, %s ASC, %s ASC',
                $this->quotedTable('availability_rules'),
                $this->whereClause($filters),
                $this->quote('owner_type'),
                $this->quote('owner_id'),
                $this->quote('weekday'),
            ),
            $params,
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listAvailabilityExceptions(?string $tenantId = null, ?string $locationId = null): array
    {
        [$filters, $params] = $this->optionalFilters([
            'tenant_id' => $tenantId,
            'location_id' => $locationId,
        ]);

        return $this->connector->select(
            sprintf(
                'SELECT * FROM %s%s ORDER BY %s ASC',
                $this->quotedTable('availability_exceptions'),
                $this->whereClause($filters),
                $this->quote('starts_at_utc'),
            ),
            $params,
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listAppointments(?string $tenantId = null, ?string $locationId = null): array
    {
        [$filters, $params] = $this->optionalFilters([
            'tenant_id' => $tenantId,
            'location_id' => $locationId,
        ]);

        return $this->connector->select(
            sprintf(
                'SELECT * FROM %s%s ORDER BY %s ASC',
                $this->quotedTable('appointments'),
                $this->whereClause($filters),
                $this->quote('starts_at_utc'),
            ),
            $params,
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listWaitlistEntries(?string $tenantId = null, ?string $locationId = null): array
    {
        [$filters, $params] = $this->optionalFilters([
            'tenant_id' => $tenantId,
            'location_id' => $locationId,
        ]);

        return $this->connector->select(
            sprintf(
                'SELECT * FROM %s%s ORDER BY %s ASC',
                $this->quotedTable('waitlist_entries'),
                $this->whereClause($filters),
                $this->quote('preferred_start_utc'),
            ),
            $params,
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findService(int|string $serviceId, ?string $tenantId = null, ?string $locationId = null): ?array
    {
        [$filters, $params] = $this->optionalFilters([
            'id' => $serviceId,
            'tenant_id' => $tenantId,
            'location_id' => $locationId,
        ]);

        return $this->connector->first(
            sprintf('SELECT * FROM %s WHERE %s', $this->quotedTable('services'), implode(' AND ', $filters)),
            $params,
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findAppointment(int|string $appointmentId, ?string $tenantId = null, ?string $locationId = null): ?array
    {
        [$filters, $params] = $this->optionalFilters([
            'id' => $appointmentId,
            'tenant_id' => $tenantId,
            'location_id' => $locationId,
        ]);

        return $this->connector->first(
            sprintf('SELECT * FROM %s WHERE %s', $this->quotedTable('appointments'), implode(' AND ', $filters)),
            $params,
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetchAvailabilityRules(
        string $ownerType,
        string $ownerId,
        ?string $tenantId = null,
        ?string $locationId = null,
    ): array {
        [$filters, $params] = $this->optionalFilters([
            'owner_type' => $ownerType,
            'owner_id' => $ownerId,
            'tenant_id' => $tenantId,
            'location_id' => $locationId,
        ]);

        return $this->connector->select(
            sprintf(
                'SELECT * FROM %s WHERE %s ORDER BY %s ASC, %s ASC',
                $this->quotedTable('availability_rules'),
                implode(' AND ', $filters),
                $this->quote('weekday'),
                $this->quote('start_time_local'),
            ),
            $params,
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetchAvailabilityExceptions(
        string $ownerType,
        string $ownerId,
        DateTimeImmutable $rangeStartUtc,
        DateTimeImmutable $rangeEndUtc,
        ?string $tenantId = null,
        ?string $locationId = null,
    ): array {
        [$filters, $params] = $this->optionalFilters([
            'owner_type' => $ownerType,
            'owner_id' => $ownerId,
            'tenant_id' => $tenantId,
            'location_id' => $locationId,
        ]);

        $filters[] = $this->quote('starts_at_utc') . ' < :range_end';
        $filters[] = $this->quote('ends_at_utc') . ' > :range_start';
        $params['range_start'] = $this->toUtcString($rangeStartUtc);
        $params['range_end'] = $this->toUtcString($rangeEndUtc);

        return $this->connector->select(
            sprintf(
                'SELECT * FROM %s WHERE %s ORDER BY %s ASC',
                $this->quotedTable('availability_exceptions'),
                implode(' AND ', $filters),
                $this->quote('starts_at_utc'),
            ),
            $params,
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetchAppointmentsInRange(
        DateTimeImmutable $rangeStartUtc,
        DateTimeImmutable $rangeEndUtc,
        ?string $staffId = null,
        ?string $resourceId = null,
        ?string $tenantId = null,
        ?string $locationId = null,
        int|string|null $excludeAppointmentId = null,
    ): array {
        $filters = [
            $this->quote('occupied_starts_at_utc') . ' < :range_end',
            $this->quote('occupied_ends_at_utc') . ' > :range_start',
            $this->quote('status') . ' <> :cancelled_status',
        ];

        $params = [
            'range_start' => $this->toUtcString($rangeStartUtc),
            'range_end' => $this->toUtcString($rangeEndUtc),
            'cancelled_status' => 'cancelled',
        ];

        if ($staffId !== null || $resourceId !== null) {
            $ownership = [];

            if ($staffId !== null) {
                $ownership[] = $this->quote('staff_id') . ' = :staff_id';
                $params['staff_id'] = $staffId;
            }

            if ($resourceId !== null) {
                $ownership[] = $this->quote('resource_id') . ' = :resource_id';
                $params['resource_id'] = $resourceId;
            }

            $filters[] = '(' . implode(' OR ', $ownership) . ')';
        }

        if ($tenantId !== null) {
            $filters[] = $this->quote('tenant_id') . ' = :tenant_id';
            $params['tenant_id'] = $tenantId;
        }

        if ($locationId !== null) {
            $filters[] = $this->quote('location_id') . ' = :location_id';
            $params['location_id'] = $locationId;
        }

        if ($excludeAppointmentId !== null) {
            $filters[] = $this->quote('id') . ' <> :exclude_id';
            $params['exclude_id'] = $excludeAppointmentId;
        }

        return $this->connector->select(
            sprintf(
                'SELECT * FROM %s WHERE %s ORDER BY %s ASC',
                $this->quotedTable('appointments'),
                implode(' AND ', $filters),
                $this->quote('occupied_starts_at_utc'),
            ),
            $params,
        );
    }

    /**
     * @param array<string, mixed> $changes
     */
    public function updateAppointment(int|string $appointmentId, array $changes): int
    {
        return $this->updateById($this->table('appointments'), $appointmentId, $changes);
    }

    /**
     * @return array{0: array<int, string>, 1: array<string, mixed>}
     */
    private function optionalFilters(array $filters): array
    {
        $clauses = [];
        $params = [];

        foreach ($filters as $column => $value) {
            if ($value === null) {
                continue;
            }

            $parameter = str_replace('.', '_', (string) $column);
            $clauses[] = $this->quote((string) $column) . ' = :' . $parameter;
            $params[$parameter] = $value;
        }

        return [$clauses, $params];
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<string, mixed>|null
     */
    private function findByFilters(string $table, array $filters): ?array
    {
        [$clauses, $params] = $this->optionalFilters($filters);

        if ($clauses === []) {
            return null;
        }

        return $this->connector->first(
            sprintf('SELECT * FROM %s WHERE %s', $this->quotedTableByName($table), implode(' AND ', $clauses)),
            $params,
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    private function insert(string $table, array $data): int|string
    {
        $columns = array_keys($data);
        $quotedColumns = array_map(fn (string $column): string => $this->quote($column), $columns);
        $placeholders = array_map(fn (string $column): string => ':' . $column, $columns);

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->quotedTableByName($table),
            implode(', ', $quotedColumns),
            implode(', ', $placeholders),
        );

        $this->connector->execute($sql, $data);

        return (string) $this->connector->pdo()->lastInsertId();
    }

    /**
     * @param array<string, mixed> $changes
     */
    private function updateById(string $table, int|string $id, array $changes): int
    {
        $assignments = [];
        $params = [];

        foreach ($changes as $column => $value) {
            $parameter = 'set_' . $column;
            $assignments[] = $this->quote((string) $column) . ' = :' . $parameter;
            $params[$parameter] = $value;
        }

        $params['id'] = $id;

        $sql = sprintf(
            'UPDATE %s SET %s WHERE %s = :id',
            $this->quotedTableByName($table),
            implode(', ', $assignments),
            $this->quote('id'),
        );

        return $this->connector->execute($sql, $params);
    }

    /**
     * Quoted Table.
     */
    private function quotedTable(string $name): string
    {
        return $this->connector->quoteIdentifier($this->table($name));
    }

    /**
     * Quoted Table By Name.
     */
    private function quotedTableByName(string $table): string
    {
        return $this->connector->quoteIdentifier($table);
    }

    /**
     * @param array<int, string> $filters
     */
    private function whereClause(array $filters): string
    {
        if ($filters === []) {
            return '';
        }

        return ' WHERE ' . implode(' AND ', $filters);
    }

    /**
     * Table.
     */
    private function table(string $name): string
    {
        return $this->config->table($name);
    }

    /**
     * Quote.
     */
    private function quote(string $column): string
    {
        return $this->connector->quoteIdentifier($column);
    }

    /**
     * Now Utc.
     */
    private function nowUtc(): string
    {
        return (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s');
    }

    /**
     * To Utc String.
     */
    private function toUtcString(DateTimeImmutable $dateTime): string
    {
        return $dateTime->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
    }
}

