<?php

declare(strict_types=1);

namespace AfriSense\Backend\Models;

use PDO;

require_once __DIR__ . '/BaseModel.php';

class Role extends BaseModel
{
    private string $table = 'roles';

    /**
     * Create the role model.
     */
    public function __construct(PDO $pdo)
    {
        parent::__construct($pdo);
    }

    /**
     * Return all roles.
     */
    public function all(): array
    {
        return $this->allRows($this->table, 'id');
    }

    /**
     * Find a role by ID.
     */
    public function findById(int $id): ?array
    {
        return $this->findByIdRow($this->table, $id);
    }

    /**
     * Find a role by name or slug.
     */
    public function findByName(string $name): ?array
    {
        if (!$this->tableExists($this->table)) {
            return null;
        }

        $column = $this->nameColumn();

        if ($column === null) {
            return null;
        }

        $statement = $this->query(
            sprintf('SELECT * FROM `roles` WHERE `%s` = :name LIMIT 1', $column),
            ['name' => $name]
        );

        $row = $statement->fetch();

        return $row ?: null;
    }

    /**
     * Create a role and return the new ID.
     */
    public function create(array $data): ?int
    {
        $data = $this->normalizeData($data);

        if ($this->columnExists($this->table, 'created_at') && !isset($data['created_at'])) {
            $data['created_at'] = date('Y-m-d H:i:s');
        }

        return $this->insertRow($this->table, $data);
    }

    /**
     * Update a role by ID.
     */
    public function update(int $id, array $data): bool
    {
        $data = $this->normalizeData($data);

        if ($this->columnExists($this->table, 'updated_at')) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }

        return $this->updateByIdRow($this->table, $id, $data);
    }

    /**
     * Delete a role by ID.
     */
    public function delete(int $id): bool
    {
        return $this->deleteByIdRow($this->table, $id);
    }

    /**
     * Assign a permission to a role.
     */
    public function assignPermission(int $roleId, int $permissionId): bool
    {
        if (
            !$this->tableExists('role_permissions')
            || !$this->columnExists('role_permissions', 'role_id')
            || !$this->columnExists('role_permissions', 'permission_id')
        ) {
            return false;
        }

        $statement = $this->query(
            'SELECT COUNT(*) AS count_value
             FROM `role_permissions`
             WHERE `role_id` = :role_id AND `permission_id` = :permission_id',
            ['role_id' => $roleId, 'permission_id' => $permissionId]
        );
        $row = $statement->fetch();

        if (((int) ($row['count_value'] ?? 0)) > 0) {
            return true;
        }

        $data = [
            'role_id' => $roleId,
            'permission_id' => $permissionId,
            'created_at' => date('Y-m-d H:i:s'),
        ];

        return $this->insertRow('role_permissions', $data) !== null;
    }

    /**
     * Remove a permission from a role.
     */
    public function removePermission(int $roleId, int $permissionId): bool
    {
        if (
            !$this->tableExists('role_permissions')
            || !$this->columnExists('role_permissions', 'role_id')
            || !$this->columnExists('role_permissions', 'permission_id')
        ) {
            return false;
        }

        $statement = $this->query(
            'DELETE FROM `role_permissions`
             WHERE `role_id` = :role_id AND `permission_id` = :permission_id',
            ['role_id' => $roleId, 'permission_id' => $permissionId]
        );

        return $statement->rowCount() > 0;
    }

    /**
     * Return permissions assigned to a role.
     */
    public function getPermissions(int $roleId): array
    {
        if (
            !$this->tableExists('role_permissions')
            || !$this->tableExists('permissions')
            || !$this->columnExists('role_permissions', 'role_id')
            || !$this->columnExists('role_permissions', 'permission_id')
        ) {
            return [];
        }

        $statement = $this->query(
            'SELECT p.*
             FROM `permissions` p
             INNER JOIN `role_permissions` rp ON rp.`permission_id` = p.`id`
             WHERE rp.`role_id` = :role_id
             ORDER BY p.`id` ASC',
            ['role_id' => $roleId]
        );

        return $statement->fetchAll();
    }

    private function nameColumn(): ?string
    {
        foreach (['slug', 'name', 'rolename'] as $column) {
            if ($this->columnExists($this->table, $column)) {
                return $column;
            }
        }

        return null;
    }

    private function normalizeData(array $data): array
    {
        if (isset($data['name']) && $this->columnExists($this->table, 'rolename')) {
            $data['rolename'] = $data['name'];
            unset($data['name']);
        }

        return $data;
    }
}
