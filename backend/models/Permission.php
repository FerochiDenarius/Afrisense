<?php

declare(strict_types=1);

namespace AfriSense\Backend\Models;

use PDO;

require_once __DIR__ . '/BaseModel.php';

class Permission extends BaseModel
{
    private string $table = 'permissions';

    /**
     * Create the permission model.
     */
    public function __construct(PDO $pdo)
    {
        parent::__construct($pdo);
    }

    /**
     * Return all permissions.
     */
    public function all(): array
    {
        return $this->allRows($this->table, 'id');
    }

    /**
     * Find a permission by ID.
     */
    public function findById(int $id): ?array
    {
        return $this->findByIdRow($this->table, $id);
    }

    /**
     * Find a permission by name or slug.
     */
    public function findByName(string $name): ?array
    {
        if (!$this->tableExists($this->table)) {
            return null;
        }

        $column = $this->columnExists($this->table, 'slug') ? 'slug' : 'name';

        if (!$this->columnExists($this->table, $column)) {
            return null;
        }

        $statement = $this->query(
            sprintf('SELECT * FROM `permissions` WHERE `%s` = :name LIMIT 1', $column),
            ['name' => $name]
        );

        $row = $statement->fetch();

        return $row ?: null;
    }

    /**
     * Create a permission and return the new ID.
     */
    public function create(array $data): ?int
    {
        if ($this->columnExists($this->table, 'created_at') && !isset($data['created_at'])) {
            $data['created_at'] = date('Y-m-d H:i:s');
        }

        return $this->insertRow($this->table, $data);
    }

    /**
     * Update a permission by ID.
     */
    public function update(int $id, array $data): bool
    {
        if ($this->columnExists($this->table, 'updated_at')) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }

        return $this->updateByIdRow($this->table, $id, $data);
    }

    /**
     * Delete a permission by ID.
     */
    public function delete(int $id): bool
    {
        return $this->deleteByIdRow($this->table, $id);
    }
}
