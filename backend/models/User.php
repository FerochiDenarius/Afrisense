<?php

declare(strict_types=1);

namespace AfriSense\Backend\Models;

use PDO;

require_once __DIR__ . '/BaseModel.php';
require_once __DIR__ . '/../helpers/Security.php';

use AfriSense\Backend\Helpers\Security;

class User extends BaseModel
{
    private string $table = 'users';

    /**
     * Create the user model.
     */
    public function __construct(PDO $pdo)
    {
        parent::__construct($pdo);
    }

    /**
     * Find a user by ID.
     */
    public function findById(int $id): ?array
    {
        return $this->findByIdRow($this->table, $id);
    }

    /**
     * Find a user by email address.
     */
    public function findByEmail(string $email): ?array
    {
        if (!$this->tableExists($this->table) || !$this->columnExists($this->table, 'email')) {
            return null;
        }

        $statement = $this->query(
            'SELECT * FROM `users` WHERE `email` = :email LIMIT 1',
            ['email' => $email]
        );

        $row = $statement->fetch();

        return $row ?: null;
    }

    /**
     * Backward-compatible alias for older authentication code.
     */
    public function getUserByEmail(string $email): ?array
    {
        return $this->findByEmail($email);
    }

    /**
     * Return all users.
     */
    public function all(): array
    {
        return $this->allRows($this->table, 'id', 'DESC');
    }

    /**
     * Create a user and return the new ID.
     */
    public function create(array $data): ?int
    {
        $data = $this->normalizeData($data);

        if (isset($data['password']) && $data['password'] !== '') {
            $data['password'] = Security::hashPassword((string) $data['password']);
        }

        if ($this->columnExists($this->table, 'created_at') && !isset($data['created_at'])) {
            $data['created_at'] = date('Y-m-d H:i:s');
        }

        return $this->insertRow($this->table, $data);
    }

    /**
     * Update a user by ID.
     */
    public function update(int $id, array $data): bool
    {
        $data = $this->normalizeData($data);

        if (array_key_exists('password', $data)) {
            if ($data['password'] === '' || $data['password'] === null) {
                unset($data['password']);
            } else {
                $data['password'] = Security::hashPassword((string) $data['password']);
            }
        }

        if ($this->columnExists($this->table, 'updated_at')) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }

        return $this->updateByIdRow($this->table, $id, $data);
    }

    /**
     * Delete a user by ID.
     */
    public function delete(int $id): bool
    {
        return $this->deleteByIdRow($this->table, $id);
    }

    /**
     * Update the user's last login timestamp.
     */
    public function updateLastLogin(int $id): bool
    {
        if (!$this->columnExists($this->table, 'last_login')) {
            return false;
        }

        return $this->updateByIdRow($this->table, $id, ['last_login' => date('Y-m-d H:i:s')]);
    }

    /**
     * Determine whether a user has a named permission.
     */
    public function hasPermission(int $userId, string $permission): bool
    {
        if (
            !$this->tableExists('users')
            || !$this->tableExists('roles')
            || !$this->tableExists('permissions')
            || !$this->tableExists('role_permissions')
            || !$this->columnExists('users', 'role_id')
        ) {
            return false;
        }

        $permissionColumn = $this->columnExists('permissions', 'slug') ? 'slug' : 'name';

        if (!$this->columnExists('permissions', $permissionColumn)) {
            return false;
        }

        $statement = $this->query(
            sprintf(
                'SELECT COUNT(*) AS count_value
                 FROM `users` u
                 INNER JOIN `role_permissions` rp ON rp.`role_id` = u.`role_id`
                 INNER JOIN `permissions` p ON p.`id` = rp.`permission_id`
                 WHERE u.`id` = :user_id AND p.`%s` = :permission',
                $permissionColumn
            ),
            ['user_id' => $userId, 'permission' => $permission]
        );

        $row = $statement->fetch();

        return ((int) ($row['count_value'] ?? 0)) > 0;
    }

    /**
     * Return the user's role row when role data is available.
     */
    public function getRole(int $userId): ?array
    {
        if (
            !$this->tableExists('users')
            || !$this->tableExists('roles')
            || !$this->columnExists('users', 'role_id')
        ) {
            return null;
        }

        $statement = $this->query(
            'SELECT r.*
             FROM `users` u
             INNER JOIN `roles` r ON r.`id` = u.`role_id`
             WHERE u.`id` = :user_id
             LIMIT 1',
            ['user_id' => $userId]
        );

        $row = $statement->fetch();

        return $row ?: null;
    }

    private function normalizeData(array $data): array
    {
        if (isset($data['phone']) && $this->columnExists($this->table, 'phonenumber')) {
            $data['phonenumber'] = $data['phone'];
            unset($data['phone']);
        }

        return $data;
    }
}
