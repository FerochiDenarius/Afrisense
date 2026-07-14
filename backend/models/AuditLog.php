<?php

declare(strict_types=1);

namespace AfriSense\Backend\Models;

use PDO;

require_once __DIR__ . '/BaseModel.php';
require_once __DIR__ . '/../helpers/Security.php';

use AfriSense\Backend\Helpers\Security;

class AuditLog extends BaseModel
{
    private string $table = 'audit_logs';

    /**
     * Create the audit log model.
     */
    public function __construct(PDO $pdo)
    {
        parent::__construct($pdo);
    }

    /**
     * Create an audit log entry.
     */
    public function create(
        ?int $userId,
        string $action,
        string $description = '',
        array $metadata = [],
        string $tableName = 'users',
        ?int $recordId = null
    ): ?int {
        if ($userId === null || $userId <= 0) {
            return null;
        }

        $data = [
            'user_id' => $userId,
            'action' => $action,
            'table_name' => $tableName,
            'record_id' => $recordId ?? $userId,
            'description' => $description,
            'ip_address' => Security::currentIp(),
            'user_agent' => Security::userAgent(),
            'metadata' => $metadata !== [] ? json_encode($metadata, JSON_THROW_ON_ERROR) : null,
            'created_at' => date('Y-m-d H:i:s'),
        ];

        return $this->insertRow($this->table, $data);
    }

    /**
     * Find an audit log entry by ID.
     */
    public function findById(int $id): ?array
    {
        return $this->findByIdRow($this->table, $id);
    }

    /**
     * Return audit log entries.
     */
    public function all(int $limit = 100): array
    {
        if (!$this->tableExists($this->table)) {
            return [];
        }

        $limit = max(1, min($limit, 500));
        $orderColumn = $this->columnExists($this->table, 'created_at') ? 'created_at' : 'id';
        $statement = $this->query(
            sprintf('SELECT * FROM `audit_logs` ORDER BY `%s` DESC LIMIT %d', $orderColumn, $limit)
        );

        return $statement->fetchAll();
    }

    /**
     * Delete audit log entries older than a date.
     */
    public function deleteOlderThan(string $date): int
    {
        if (!$this->tableExists($this->table) || !$this->columnExists($this->table, 'created_at')) {
            return 0;
        }

        $statement = $this->query(
            'DELETE FROM `audit_logs` WHERE `created_at` < :date',
            ['date' => $date]
        );

        return $statement->rowCount();
    }
}
