<?php

declare(strict_types=1);

namespace AfriSense\Backend\Models;

use PDO;

require_once __DIR__ . '/BaseModel.php';

class Notification extends BaseModel
{
    private string $table = 'notifications';

    /**
     * Create the notification model.
     */
    public function __construct(PDO $pdo)
    {
        parent::__construct($pdo);
    }

    /**
     * Return notifications for a user.
     */
    public function allForUser(int $userId): array
    {
        if (!$this->tableExists($this->table) || !$this->columnExists($this->table, 'user_id')) {
            return [];
        }

        $orderColumn = $this->columnExists($this->table, 'created_at') ? 'created_at' : 'id';
        $statement = $this->query(
            sprintf(
                'SELECT * FROM `notifications`
                 WHERE `user_id` = :user_id
                 ORDER BY `%s` DESC',
                $orderColumn
            ),
            ['user_id' => $userId]
        );

        return $statement->fetchAll();
    }

    /**
     * Create a notification and return the new ID.
     */
    public function create(array $data): ?int
    {
        if (isset($data['type']) && $this->columnExists($this->table, 'notification_type')) {
            $data['notification_type'] = $data['type'];
            unset($data['type']);
        }

        if ($this->columnExists($this->table, 'created_at') && !isset($data['created_at'])) {
            $data['created_at'] = date('Y-m-d H:i:s');
        }

        return $this->insertRow($this->table, $data);
    }

    /**
     * Mark one notification as read.
     */
    public function markAsRead(int $id, int $userId): bool
    {
        if (!$this->tableExists($this->table) || !$this->columnExists($this->table, 'user_id')) {
            return false;
        }

        $readColumn = $this->readColumn();

        if ($readColumn === null) {
            return false;
        }

        $readValue = $readColumn === 'read_at' ? date('Y-m-d H:i:s') : ($readColumn === 'status' ? 'read' : 1);
        $statement = $this->query(
            sprintf(
                'UPDATE `notifications`
                 SET `%s` = :read_value
                 WHERE `id` = :id AND `user_id` = :user_id',
                $readColumn
            ),
            ['read_value' => $readValue, 'id' => $id, 'user_id' => $userId]
        );

        return $statement->rowCount() > 0;
    }

    /**
     * Mark all notifications as read for a user.
     */
    public function markAllAsRead(int $userId): int
    {
        if (!$this->tableExists($this->table) || !$this->columnExists($this->table, 'user_id')) {
            return 0;
        }

        $readColumn = $this->readColumn();

        if ($readColumn === null) {
            return 0;
        }

        $readValue = $readColumn === 'read_at' ? date('Y-m-d H:i:s') : ($readColumn === 'status' ? 'read' : 1);
        $statement = $this->query(
            sprintf(
                'UPDATE `notifications`
                 SET `%s` = :read_value
                 WHERE `user_id` = :user_id',
                $readColumn
            ),
            ['read_value' => $readValue, 'user_id' => $userId]
        );

        return $statement->rowCount();
    }

    /**
     * Delete one notification owned by a user.
     */
    public function delete(int $id, int $userId): bool
    {
        if (!$this->tableExists($this->table) || !$this->columnExists($this->table, 'user_id')) {
            return false;
        }

        $statement = $this->query(
            'DELETE FROM `notifications` WHERE `id` = :id AND `user_id` = :user_id',
            ['id' => $id, 'user_id' => $userId]
        );

        return $statement->rowCount() > 0;
    }

    /**
     * Count unread notifications for a user.
     */
    public function unreadCount(int $userId): int
    {
        if (!$this->tableExists($this->table) || !$this->columnExists($this->table, 'user_id')) {
            return 0;
        }

        $readColumn = $this->readColumn();

        if ($readColumn === null) {
            return 0;
        }

        $operator = $readColumn === 'read_at' ? 'IS NULL' : '= :read_value';
        $params = ['user_id' => $userId];

        if ($readColumn !== 'read_at') {
            $params['read_value'] = $readColumn === 'status' ? 'unread' : 0;
        }

        $statement = $this->query(
            sprintf(
                'SELECT COUNT(*) AS count_value
                 FROM `notifications`
                 WHERE `user_id` = :user_id AND `%s` %s',
                $readColumn,
                $operator
            ),
            $params
        );

        $row = $statement->fetch();

        return (int) ($row['count_value'] ?? 0);
    }

    private function readColumn(): ?string
    {
        foreach (['is_read', 'read_at', 'status'] as $column) {
            if ($this->columnExists($this->table, $column)) {
                return $column;
            }
        }

        return null;
    }
}
