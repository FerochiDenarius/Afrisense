<?php

declare(strict_types=1);

namespace AfriSense\Backend\Models;

use PDO;

require_once __DIR__ . '/BaseModel.php';
require_once __DIR__ . '/AuditLog.php';
require_once __DIR__ . '/Notification.php';

class Dashboard extends BaseModel
{
    private AuditLog $auditLogs;
    private Notification $notifications;

    /**
     * Create the dashboard model.
     */
    public function __construct(PDO $pdo)
    {
        parent::__construct($pdo);
        $this->auditLogs = new AuditLog($pdo);
        $this->notifications = new Notification($pdo);
    }

    /**
     * Return dashboard summary data for the current user.
     */
    public function summary(?int $userId = null): array
    {
        return [
            'counts' => [
                'users' => $this->countTable('users'),
                'roles' => $this->countTable('roles'),
                'permissions' => $this->countTable('permissions'),
                'notifications_unread' => $userId !== null ? $this->notifications->unreadCount($userId) : 0,
            ],
            'recent_audit_logs' => $this->auditLogs->all(10),
        ];
    }

    /**
     * Count rows in a database table when it exists.
     */
    public function countTable(string $table): int
    {
        if (!$this->tableExists($table)) {
            return 0;
        }

        $statement = $this->query(sprintf('SELECT COUNT(*) AS count_value FROM `%s`', $table));
        $row = $statement->fetch();

        return (int) ($row['count_value'] ?? 0);
    }
}
