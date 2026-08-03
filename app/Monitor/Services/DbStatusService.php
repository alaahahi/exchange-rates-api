<?php

namespace App\Monitor\Services;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;

class DbStatusService
{
    protected ?array $cachedSnapshot = null;

    /** @var list<string> */
    protected array $mysqlStatusKeys = [
        'Threads_connected',
        'Threads_running',
        'Connections',
        'Max_used_connections',
        'Aborted_clients',
        'Aborted_connects',
        'Uptime',
    ];

    public function snapshot(bool $force = false): ?array
    {
        if ($this->cachedSnapshot !== null && ! $force) {
            return $this->cachedSnapshot;
        }

        try {
            $connection = DB::connection();
            $driver = $connection->getDriverName();
            $database = $connection->getDatabaseName();

            if (in_array($driver, ['mysql', 'mariadb'], true)) {
                $this->cachedSnapshot = $this->mysqlSnapshot($connection, $database, $driver);

                return $this->cachedSnapshot;
            }

            $this->cachedSnapshot = [
                'database' => $database,
                'connection_id' => null,
                'threads_connected' => null,
                'threads_running' => null,
                'connections' => null,
                'max_used_connections' => null,
                'aborted_clients' => null,
                'aborted_connects' => null,
                'uptime' => null,
                'driver' => $driver,
                'supported' => false,
            ];

            return $this->cachedSnapshot;
        } catch (\Throwable $e) {
            $this->cachedSnapshot = [
                'database' => null,
                'connection_id' => null,
                'threads_connected' => null,
                'threads_running' => null,
                'connections' => null,
                'max_used_connections' => null,
                'aborted_clients' => null,
                'aborted_connects' => null,
                'uptime' => null,
                'driver' => null,
                'supported' => false,
                'error' => $e->getMessage(),
            ];

            return $this->cachedSnapshot;
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function mysqlSnapshot(Connection $connection, ?string $database, string $driver): array
    {
        $connectionId = null;
        try {
            $row = $connection->selectOne('SELECT CONNECTION_ID() AS id');
            $connectionId = $this->rowValue($row, 'id');
        } catch (\Throwable) {
            // ignore
        }

        $status = $this->readMysqlStatus($connection);

        return [
            'database' => $database,
            'connection_id' => $connectionId !== null ? (int) $connectionId : null,
            'threads_connected' => isset($status['Threads_connected']) ? (int) $status['Threads_connected'] : null,
            'threads_running' => isset($status['Threads_running']) ? (int) $status['Threads_running'] : null,
            'connections' => isset($status['Connections']) ? (int) $status['Connections'] : null,
            'max_used_connections' => isset($status['Max_used_connections']) ? (int) $status['Max_used_connections'] : null,
            'aborted_clients' => isset($status['Aborted_clients']) ? (int) $status['Aborted_clients'] : null,
            'aborted_connects' => isset($status['Aborted_connects']) ? (int) $status['Aborted_connects'] : null,
            'uptime' => isset($status['Uptime']) ? (int) $status['Uptime'] : null,
            'driver' => $driver,
            'supported' => true,
        ];
    }

    /**
     * SHOW STATUS with bound parameters is unreliable on many MySQL/PDO setups.
     * Use a single GLOBAL STATUS query with a hard-coded whitelist instead.
     *
     * @return array<string, string|null>
     */
    protected function readMysqlStatus(Connection $connection): array
    {
        $keys = $this->mysqlStatusKeys;
        $list = "'".implode("','", $keys)."'";

        try {
            $rows = $connection->select("SHOW GLOBAL STATUS WHERE Variable_name IN ({$list})");
        } catch (\Throwable) {
            // MariaDB / older MySQL fallback
            $rows = [];
            foreach ($keys as $key) {
                try {
                    $rows = array_merge(
                        $rows,
                        $connection->select("SHOW GLOBAL STATUS LIKE '{$key}'")
                    );
                } catch (\Throwable) {
                    // continue
                }
            }
        }

        $status = [];
        foreach ($rows as $row) {
            $name = $this->rowValue($row, 'Variable_name') ?? $this->rowValue($row, 'variable_name');
            $value = $this->rowValue($row, 'Value') ?? $this->rowValue($row, 'value');
            if ($name !== null) {
                $status[(string) $name] = $value;
            }
        }

        return $status;
    }

    protected function rowValue(mixed $row, string $key): mixed
    {
        if (is_array($row)) {
            return $row[$key] ?? $row[strtolower($key)] ?? null;
        }

        if (is_object($row)) {
            return $row->{$key} ?? $row->{strtolower($key)} ?? null;
        }

        return null;
    }

    public function formatMemory(?int $bytes = null): string
    {
        $bytes = $bytes ?? memory_get_usage(true);

        return round($bytes / 1048576, 1).'MB';
    }
}
