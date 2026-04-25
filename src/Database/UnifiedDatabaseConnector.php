<?php

namespace UnifiedAppointments\Database;

use PDO;
use PDOStatement;
use RuntimeException;
use UnifiedAppointments\Config\UnifiedAppointmentsConfig;

/**
 * UnifiedDatabaseConnector.
 */
final class UnifiedDatabaseConnector
{
    private ?object $connection = null;

    /**
     * Create a new instance.
     */
    public function __construct(private readonly UnifiedAppointmentsConfig $config)
    {
    }

    /**
     * Connection.
     */
    public function connection(): object
    {
        if ($this->connection !== null) {
            return $this->connection;
        }

        $this->loadUnifiedDatabaseLibrary();

        /** @var object $database */
        $database = \DatabaseFactory::create(
            $this->config->driver,
            $this->config->host,
            $this->config->username,
            $this->config->password,
            $this->config->database,
            $this->config->port,
        );

        return $this->connection = $database;
    }

    /**
     * Pdo.
     */
    public function pdo(): PDO
    {
        $pdo = $this->connection()->getConnection();

        if (!$pdo instanceof PDO) {
            throw new RuntimeException('Unified database connection did not return a PDO instance.');
        }

        return $pdo;
    }

    /**
     * Driver Name.
     */
    public function driverName(): string
    {
        return $this->pdo()->getAttribute(PDO::ATTR_DRIVER_NAME);
    }

    /**
     * Quote Identifier.
     */
    public function quoteIdentifier(string $identifier): string
    {
        return match ($this->driverName()) {
            'mysql' => '`' . str_replace('`', '``', $identifier) . '`',
            'sqlsrv' => '[' . str_replace(']', ']]', $identifier) . ']',
            default => '"' . str_replace('"', '""', $identifier) . '"',
        };
    }

    /**
     * @param array<string, mixed> $params
     */
    public function execute(string $sql, array $params = []): int
    {
        $statement = $this->prepareAndExecute($sql, $params);

        return $statement->rowCount();
    }

    /**
     * @param array<string, mixed> $params
     * @return array<int, array<string, mixed>>
     */
    public function select(string $sql, array $params = []): array
    {
        $statement = $this->prepareAndExecute($sql, $params);
        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);
        $statement->closeCursor();

        return $rows;
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>|null
     */
    public function first(string $sql, array $params = []): ?array
    {
        $statement = $this->prepareAndExecute($sql, $params);
        $row = $statement->fetch(PDO::FETCH_ASSOC) ?: null;
        $statement->closeCursor();

        return $row;
    }

    /**
     * @param array<string, mixed> $params
     * @return \Generator<int, array<string, mixed>>
     */
    public function cursor(string $sql, array $params = []): \Generator
    {
        $statement = $this->prepareAndExecute($sql, $params);

        try {
            while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                yield $row;
            }
        } finally {
            $statement->closeCursor();
        }
    }

    /**
     * @param array<string, mixed> $params
     */
    private function prepareAndExecute(string $sql, array $params): PDOStatement
    {
        $statement = $this->pdo()->prepare($sql);

        foreach ($params as $key => $value) {
            $parameter = str_starts_with($key, ':') ? $key : ':' . $key;
            $type = match (true) {
                is_int($value) => PDO::PARAM_INT,
                is_bool($value) => PDO::PARAM_BOOL,
                $value === null => PDO::PARAM_NULL,
                default => PDO::PARAM_STR,
            };

            $statement->bindValue($parameter, $value, $type);
        }

        $statement->execute();

        return $statement;
    }

    /**
     * Load Unified Database Library.
     */
    private function loadUnifiedDatabaseLibrary(): void
    {
        $interfacePath = $this->config->databaseLibraryPath . DIRECTORY_SEPARATOR . 'DatabaseInterface.php';
        $factoryPath = $this->config->databaseLibraryPath . DIRECTORY_SEPARATOR . 'DatabaseFactory.php';

        if (!is_file($interfacePath) || !is_file($factoryPath)) {
            throw new RuntimeException(sprintf(
                'Unified Databases library not found at "%s".',
                $this->config->databaseLibraryPath,
            ));
        }

        require_once $interfacePath;
        require_once $factoryPath;
    }
}

