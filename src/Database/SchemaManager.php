<?php

namespace UnifiedAppointments\Database;

use PDOException;
use Throwable;
use UnifiedAppointments\Config\UnifiedAppointmentsConfig;

/**
 * SchemaManager.
 */
final class SchemaManager
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
     * Install.
     */
    public function install(): void
    {
        foreach ($this->tableStatements() as $sql) {
            $this->connector->pdo()->exec($sql);
        }

        $this->ensureAppointmentReminderColumns();

        foreach ($this->indexStatements() as $sql) {
            $this->executeIndexStatement($sql);
        }
    }

    /**
     * Is Installed.
     */
    public function isInstalled(): bool
    {
        try {
            $this->connector->first(
                sprintf('SELECT 1 FROM %s WHERE 1 = 0', $this->table('services')),
            );

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * @return array<int, string>
     */
    private function tableStatements(): array
    {
        $driver = $this->connector->driverName();
        $id = $this->idColumnDefinition($driver);
        $string = $this->stringType($driver);
        $text = $this->textType($driver);
        $timestamp = $this->timestampType($driver);
        $date = $this->dateType($driver);
        $decimal = $this->decimalType($driver);
        $integer = $this->integerType($driver);

        return [
            sprintf(
                'CREATE TABLE IF NOT EXISTS %s (
                    id %s,
                    config_key %s NOT NULL,
                    config_value %s NULL,
                    config_group %s NULL,
                    created_at_utc %s NOT NULL,
                    updated_at_utc %s NOT NULL
                )',
                $this->table('system_config'),
                $id,
                $string,
                $text,
                $string,
                $timestamp,
                $timestamp,
            ),
            sprintf(
                'CREATE TABLE IF NOT EXISTS %s (
                    id %s,
                    region %s NOT NULL,
                    timezone_key %s NOT NULL,
                    label %s NOT NULL,
                    sort_order %s NOT NULL,
                    created_at_utc %s NOT NULL
                )',
                $this->table('timezones'),
                $id,
                $string,
                $string,
                $string,
                $integer,
                $timestamp,
            ),
            sprintf(
                'CREATE TABLE IF NOT EXISTS %s (
                    id %s,
                    location_key %s NOT NULL,
                    name %s NOT NULL,
                    contact_name %s NULL,
                    email %s NULL,
                    phone %s NULL,
                    address_line_1 %s NULL,
                    address_line_2 %s NULL,
                    city %s NULL,
                    state_province %s NULL,
                    postal_code %s NULL,
                    country %s NULL,
                    timezone %s NOT NULL,
                    created_at_utc %s NOT NULL,
                    updated_at_utc %s NOT NULL
                )',
                $this->table('locations'),
                $id,
                $string,
                $string,
                $string,
                $string,
                $string,
                $string,
                $string,
                $string,
                $string,
                $string,
                $string,
                $string,
                $timestamp,
                $timestamp,
            ),
            sprintf(
                'CREATE TABLE IF NOT EXISTS %s (
                    id %s,
                    member_key %s NOT NULL,
                    location_key %s NULL,
                    name %s NOT NULL,
                    email %s NULL,
                    phone %s NULL,
                    role %s NOT NULL,
                    owner_type %s NOT NULL,
                    timezone %s NOT NULL,
                    is_active %s NOT NULL DEFAULT 1,
                    created_at_utc %s NOT NULL,
                    updated_at_utc %s NOT NULL
                )',
                $this->table('team_members'),
                $id,
                $string,
                $string,
                $string,
                $string,
                $string,
                $string,
                $string,
                $string,
                $integer,
                $timestamp,
                $timestamp,
            ),
            sprintf(
                'CREATE TABLE IF NOT EXISTS %s (
                    id %s,
                    channel %s NOT NULL,
                    is_enabled %s NOT NULL DEFAULT 0,
                    recipient %s NULL,
                    sender_name %s NULL,
                    trigger_summary %s NULL,
                    config_payload %s NULL,
                    created_at_utc %s NOT NULL,
                    updated_at_utc %s NOT NULL
                )',
                $this->table('notification_settings'),
                $id,
                $string,
                $integer,
                $string,
                $string,
                $string,
                $text,
                $timestamp,
                $timestamp,
            ),
            sprintf(
                'CREATE TABLE IF NOT EXISTS %s (
                    id %s,
                    tenant_id %s NULL,
                    location_id %s NULL,
                    name %s NOT NULL,
                    duration_minutes %s NOT NULL,
                    buffer_before_minutes %s NOT NULL DEFAULT 0,
                    buffer_after_minutes %s NOT NULL DEFAULT 0,
                    slot_interval_minutes %s NOT NULL DEFAULT 30,
                    lead_time_minutes %s NOT NULL DEFAULT 0,
                    max_advance_days %s NOT NULL DEFAULT 90,
                    deposit_type %s NULL,
                    deposit_amount %s NULL,
                    no_show_fee_amount %s NULL,
                    timezone %s NOT NULL,
                    created_at_utc %s NOT NULL,
                    updated_at_utc %s NOT NULL
                )',
                $this->table('services'),
                $id,
                $string,
                $string,
                $string,
                $integer,
                $integer,
                $integer,
                $integer,
                $integer,
                $integer,
                $string,
                $decimal,
                $decimal,
                $string,
                $timestamp,
                $timestamp,
            ),
            sprintf(
                'CREATE TABLE IF NOT EXISTS %s (
                    id %s,
                    tenant_id %s NULL,
                    location_id %s NULL,
                    owner_type %s NOT NULL,
                    owner_id %s NOT NULL,
                    weekday %s NOT NULL,
                    start_time_local %s NOT NULL,
                    end_time_local %s NOT NULL,
                    slot_interval_minutes %s NULL,
                    valid_from_local %s NULL,
                    valid_until_local %s NULL,
                    timezone %s NOT NULL,
                    created_at_utc %s NOT NULL,
                    updated_at_utc %s NOT NULL
                )',
                $this->table('availability_rules'),
                $id,
                $string,
                $string,
                $string,
                $string,
                $integer,
                $string,
                $string,
                $integer,
                $date,
                $date,
                $string,
                $timestamp,
                $timestamp,
            ),
            sprintf(
                'CREATE TABLE IF NOT EXISTS %s (
                    id %s,
                    tenant_id %s NULL,
                    location_id %s NULL,
                    owner_type %s NOT NULL,
                    owner_id %s NOT NULL,
                    exception_type %s NOT NULL,
                    starts_at_utc %s NOT NULL,
                    ends_at_utc %s NOT NULL,
                    timezone %s NOT NULL,
                    notes %s NULL,
                    created_at_utc %s NOT NULL
                )',
                $this->table('availability_exceptions'),
                $id,
                $string,
                $string,
                $string,
                $string,
                $string,
                $timestamp,
                $timestamp,
                $string,
                $text,
                $timestamp,
            ),
            sprintf(
                'CREATE TABLE IF NOT EXISTS %s (
                    id %s,
                    tenant_id %s NULL,
                    location_id %s NULL,
                    service_id %s NOT NULL,
                    staff_id %s NULL,
                    resource_id %s NULL,
                    customer_name %s NOT NULL,
                    customer_email %s NULL,
                    customer_phone %s NULL,
                    starts_at_utc %s NOT NULL,
                    ends_at_utc %s NOT NULL,
                    occupied_starts_at_utc %s NOT NULL,
                    occupied_ends_at_utc %s NOT NULL,
                    timezone %s NOT NULL,
                    status %s NOT NULL,
                    deposit_amount %s NULL,
                    no_show_fee_amount %s NULL,
                    notes %s NULL,
                    external_reference %s NULL,
                    reminder_send_at_utc %s NULL,
                    reminder_status %s NULL,
                    reminder_sent_at_utc %s NULL,
                    reminder_last_error %s NULL,
                    created_at_utc %s NOT NULL,
                    updated_at_utc %s NOT NULL,
                    cancelled_at_utc %s NULL,
                    cancellation_reason %s NULL
                )',
                $this->table('appointments'),
                $id,
                $string,
                $string,
                $integer,
                $string,
                $string,
                $string,
                $string,
                $string,
                $timestamp,
                $timestamp,
                $timestamp,
                $timestamp,
                $string,
                $string,
                $decimal,
                $decimal,
                $text,
                $string,
                $timestamp,
                $string,
                $timestamp,
                $text,
                $timestamp,
                $timestamp,
                $timestamp,
                $text,
            ),
            sprintf(
                'CREATE TABLE IF NOT EXISTS %s (
                    id %s,
                    tenant_id %s NULL,
                    location_id %s NULL,
                    service_id %s NOT NULL,
                    staff_id %s NULL,
                    resource_id %s NULL,
                    customer_name %s NOT NULL,
                    customer_email %s NULL,
                    customer_phone %s NULL,
                    preferred_start_utc %s NOT NULL,
                    preferred_end_utc %s NOT NULL,
                    timezone %s NOT NULL,
                    status %s NOT NULL,
                    notified_at_utc %s NULL,
                    created_at_utc %s NOT NULL
                )',
                $this->table('waitlist_entries'),
                $id,
                $string,
                $string,
                $integer,
                $string,
                $string,
                $string,
                $string,
                $string,
                $timestamp,
                $timestamp,
                $string,
                $string,
                $timestamp,
                $timestamp,
            ),
            sprintf(
                'CREATE TABLE IF NOT EXISTS %s (
                    id %s,
                    provider %s NOT NULL,
                    event_key %s NOT NULL,
                    related_appointment_id %s NULL,
                    recipient %s NULL,
                    subject_line %s NOT NULL,
                    message_body %s NOT NULL,
                    status %s NOT NULL,
                    request_payload %s NULL,
                    response_payload %s NULL,
                    error_message %s NULL,
                    created_at_utc %s NOT NULL
                )',
                $this->table('email_dispatches'),
                $id,
                $string,
                $string,
                $integer,
                $string,
                $string,
                $text,
                $string,
                $text,
                $text,
                $text,
                $timestamp,
            ),
            sprintf(
                'CREATE TABLE IF NOT EXISTS %s (
                    id %s,
                    tenant_id %s NULL,
                    location_id %s NULL,
                    owner_type %s NOT NULL,
                    owner_id %s NOT NULL,
                    provider %s NOT NULL,
                    calendar_identifier %s NOT NULL,
                    access_token_encrypted %s NULL,
                    refresh_token_encrypted %s NULL,
                    expires_at_utc %s NULL,
                    created_at_utc %s NOT NULL,
                    updated_at_utc %s NOT NULL
                )',
                $this->table('calendar_connections'),
                $id,
                $string,
                $string,
                $string,
                $string,
                $string,
                $string,
                $text,
                $text,
                $timestamp,
                $timestamp,
                $timestamp,
            ),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function indexStatements(): array
    {
        return [
            sprintf(
                'CREATE UNIQUE INDEX %s ON %s (%s)',
                $this->indexName('system_config_key'),
                $this->table('system_config'),
                $this->quote('config_key'),
            ),
            sprintf(
                'CREATE UNIQUE INDEX %s ON %s (%s)',
                $this->indexName('timezones_key'),
                $this->table('timezones'),
                $this->quote('timezone_key'),
            ),
            sprintf(
                'CREATE UNIQUE INDEX %s ON %s (%s)',
                $this->indexName('locations_key'),
                $this->table('locations'),
                $this->quote('location_key'),
            ),
            sprintf(
                'CREATE UNIQUE INDEX %s ON %s (%s)',
                $this->indexName('team_members_key'),
                $this->table('team_members'),
                $this->quote('member_key'),
            ),
            sprintf(
                'CREATE INDEX %s ON %s (%s)',
                $this->indexName('team_members_location'),
                $this->table('team_members'),
                $this->quote('location_key'),
            ),
            sprintf(
                'CREATE UNIQUE INDEX %s ON %s (%s)',
                $this->indexName('notification_channel'),
                $this->table('notification_settings'),
                $this->quote('channel'),
            ),
            sprintf(
                'CREATE INDEX %s ON %s (%s)',
                $this->indexName('appointments_reminder_due'),
                $this->table('appointments'),
                implode(', ', [
                    $this->quote('reminder_status'),
                    $this->quote('reminder_send_at_utc'),
                ]),
            ),
            sprintf(
                'CREATE INDEX %s ON %s (%s)',
                $this->indexName('email_dispatches_created'),
                $this->table('email_dispatches'),
                $this->quote('created_at_utc'),
            ),
            sprintf(
                'CREATE INDEX %s ON %s (%s)',
                $this->indexName('email_dispatches_appointment'),
                $this->table('email_dispatches'),
                $this->quote('related_appointment_id'),
            ),
            sprintf(
                'CREATE INDEX %s ON %s (%s, %s, %s)',
                $this->indexName('rules_owner'),
                $this->table('availability_rules'),
                $this->quote('owner_type'),
                $this->quote('owner_id'),
                $this->quote('weekday'),
            ),
            sprintf(
                'CREATE INDEX %s ON %s (%s, %s, %s)',
                $this->indexName('exceptions_owner'),
                $this->table('availability_exceptions'),
                $this->quote('owner_type'),
                $this->quote('owner_id'),
                $this->quote('starts_at_utc'),
            ),
            sprintf(
                'CREATE INDEX %s ON %s (%s, %s, %s)',
                $this->indexName('appointments_staff'),
                $this->table('appointments'),
                $this->quote('staff_id'),
                $this->quote('occupied_starts_at_utc'),
                $this->quote('occupied_ends_at_utc'),
            ),
            sprintf(
                'CREATE INDEX %s ON %s (%s, %s, %s)',
                $this->indexName('appointments_resource'),
                $this->table('appointments'),
                $this->quote('resource_id'),
                $this->quote('occupied_starts_at_utc'),
                $this->quote('occupied_ends_at_utc'),
            ),
            sprintf(
                'CREATE INDEX %s ON %s (%s, %s, %s)',
                $this->indexName('waitlist_lookup'),
                $this->table('waitlist_entries'),
                $this->quote('service_id'),
                $this->quote('staff_id'),
                $this->quote('resource_id'),
            ),
            sprintf(
                'CREATE UNIQUE INDEX %s ON %s (%s, %s, %s)',
                $this->indexName('calendar_provider'),
                $this->table('calendar_connections'),
                $this->quote('owner_type'),
                $this->quote('owner_id'),
                $this->quote('provider'),
            ),
        ];
    }

    /**
     * Execute Index Statement.
     */
    private function executeIndexStatement(string $sql): void
    {
        try {
            $this->connector->pdo()->exec($sql);
        } catch (PDOException $exception) {
            $message = strtolower($exception->getMessage());

            if (
                str_contains($message, 'already exists')
                || str_contains($message, 'duplicate key name')
                || str_contains($message, 'is not unique')
                || str_contains($message, 'there is already an object named')
            ) {
                return;
            }

            throw $exception;
        }
    }

    /**
     * Ensure Appointment Reminder Columns.
     */
    private function ensureAppointmentReminderColumns(): void
    {
        $driver = $this->connector->driverName();

        $this->ensureColumnExists(
            'appointments',
            'reminder_send_at_utc',
            $this->timestampType($driver) . ' NULL',
        );
        $this->ensureColumnExists(
            'appointments',
            'reminder_status',
            $this->stringType($driver, 48) . ' NULL',
        );
        $this->ensureColumnExists(
            'appointments',
            'reminder_sent_at_utc',
            $this->timestampType($driver) . ' NULL',
        );
        $this->ensureColumnExists(
            'appointments',
            'reminder_last_error',
            $this->textType($driver) . ' NULL',
        );
    }

    /**
     * Ensure Column Exists.
     */
    private function ensureColumnExists(string $table, string $column, string $definition): void
    {
        if ($this->columnExists($table, $column)) {
            return;
        }

        $this->connector->pdo()->exec(sprintf(
            'ALTER TABLE %s ADD COLUMN %s %s',
            $this->table($table),
            $this->quote($column),
            $definition,
        ));
    }

    /**
     * Column Exists.
     */
    private function columnExists(string $table, string $column): bool
    {
        $tableName = $this->config->table($table);
        $driver = $this->connector->driverName();

        return match ($driver) {
            'mysql' => $this->connector->first(
                sprintf('SHOW COLUMNS FROM %s LIKE :column_name', $this->table($table)),
                ['column_name' => $column],
            ) !== null,
            'pgsql' => $this->connector->first(
                'SELECT column_name FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = :table_name AND column_name = :column_name',
                [
                    'table_name' => $tableName,
                    'column_name' => $column,
                ],
            ) !== null,
            'sqlsrv' => $this->connector->first(
                'SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = :table_name AND COLUMN_NAME = :column_name',
                [
                    'table_name' => $tableName,
                    'column_name' => $column,
                ],
            ) !== null,
            default => $this->sqliteColumnExists($tableName, $column),
        };
    }

    /**
     * Sqlite Column Exists.
     */
    private function sqliteColumnExists(string $tableName, string $column): bool
    {
        $rows = $this->connector->select(sprintf(
            'PRAGMA table_info(%s)',
            $this->quote($tableName),
        ));

        foreach ($rows as $row) {
            if ((string) ($row['name'] ?? '') === $column) {
                return true;
            }
        }

        return false;
    }

    /**
     * Id Column Definition.
     */
    private function idColumnDefinition(string $driver): string
    {
        return match ($driver) {
            'mysql' => 'BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
            'pgsql' => 'BIGSERIAL PRIMARY KEY',
            'sqlsrv' => 'BIGINT IDENTITY(1,1) PRIMARY KEY',
            default => 'INTEGER PRIMARY KEY AUTOINCREMENT',
        };
    }

    /**
     * String Type.
     */
    private function stringType(string $driver, int $length = 191): string
    {
        return match ($driver) {
            'sqlsrv' => 'NVARCHAR(' . $length . ')',
            default => 'VARCHAR(' . $length . ')',
        };
    }

    /**
     * Text Type.
     */
    private function textType(string $driver): string
    {
        return match ($driver) {
            'sqlsrv' => 'NVARCHAR(MAX)',
            default => 'TEXT',
        };
    }

    /**
     * Timestamp Type.
     */
    private function timestampType(string $driver): string
    {
        return match ($driver) {
            'sqlsrv' => 'DATETIME2',
            default => 'TIMESTAMP',
        };
    }

    /**
     * Date Type.
     */
    private function dateType(string $driver): string
    {
        return 'DATE';
    }

    /**
     * Decimal Type.
     */
    private function decimalType(string $driver): string
    {
        return 'DECIMAL(10,2)';
    }

    /**
     * Integer Type.
     */
    private function integerType(string $driver): string
    {
        return 'INTEGER';
    }

    /**
     * Table.
     */
    private function table(string $name): string
    {
        return $this->quote($this->config->table($name));
    }

    /**
     * Quote.
     */
    private function quote(string $identifier): string
    {
        return $this->connector->quoteIdentifier($identifier);
    }

    /**
     * Index Name.
     */
    private function indexName(string $suffix): string
    {
        return $this->connector->quoteIdentifier($this->config->table($suffix . '_idx'));
    }
}

