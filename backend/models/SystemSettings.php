<?php

declare(strict_types=1);

namespace AfriSense\Backend\Models;

use PDO;

require_once __DIR__ . '/BaseModel.php';

class SystemSettings extends BaseModel
{
    private string $table = 'system_settings';

    /**
     * Create the system settings model.
     */
    public function __construct(PDO $pdo)
    {
        parent::__construct($pdo);
    }

    /**
     * Return all system settings.
     */
    public function all(): array
    {
        return $this->allRows($this->table, 'id');
    }

    /**
     * Get one setting value by key.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $keyColumn = $this->keyColumn();
        $valueColumn = $this->valueColumn();

        if ($this->columnExists($this->table, $key)) {
            $statement = $this->query(sprintf('SELECT `%s` FROM `system_settings` ORDER BY `id` ASC LIMIT 1', $key));
            $row = $statement->fetch();

            return $row[$key] ?? $default;
        }

        if ($keyColumn === null || $valueColumn === null) {
            return $default;
        }

        $statement = $this->query(
            sprintf(
                'SELECT `%s` FROM `system_settings` WHERE `%s` = :setting_key LIMIT 1',
                $valueColumn,
                $keyColumn
            ),
            ['setting_key' => $key]
        );

        $row = $statement->fetch();

        return $row[$valueColumn] ?? $default;
    }

    /**
     * Create or update one setting value.
     */
    public function set(string $key, mixed $value): bool
    {
        $keyColumn = $this->keyColumn();
        $valueColumn = $this->valueColumn();
        $storedValue = is_scalar($value) ? (string) $value : json_encode($value, JSON_THROW_ON_ERROR);

        if ($this->columnExists($this->table, $key)) {
            $statement = $this->query('SELECT `id` FROM `system_settings` ORDER BY `id` ASC LIMIT 1');
            $existing = $statement->fetch();
            $data = [$key => $storedValue];

            if ($this->columnExists($this->table, 'updated_at')) {
                $data['updated_at'] = date('Y-m-d H:i:s');
            }

            if ($existing) {
                return $this->updateByIdRow($this->table, (int) $existing['id'], $data);
            }

            if ($this->columnExists($this->table, 'created_at')) {
                $data['created_at'] = date('Y-m-d H:i:s');
            }

            return $this->insertRow($this->table, $data) !== null;
        }

        if ($keyColumn === null || $valueColumn === null) {
            return false;
        }

        $statement = $this->query(
            sprintf('SELECT `id` FROM `system_settings` WHERE `%s` = :setting_key LIMIT 1', $keyColumn),
            ['setting_key' => $key]
        );
        $existing = $statement->fetch();

        $data = [
            $keyColumn => $key,
            $valueColumn => $storedValue,
        ];

        if ($this->columnExists($this->table, 'updated_at')) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }

        if ($existing) {
            return $this->updateByIdRow($this->table, (int) $existing['id'], $data);
        }

        if ($this->columnExists($this->table, 'created_at')) {
            $data['created_at'] = date('Y-m-d H:i:s');
        }

        return $this->insertRow($this->table, $data) !== null;
    }

    /**
     * Delete a setting by key.
     */
    public function delete(string $key): bool
    {
        $keyColumn = $this->keyColumn();

        if ($this->columnExists($this->table, $key)) {
            $statement = $this->query('SELECT `id` FROM `system_settings` ORDER BY `id` ASC LIMIT 1');
            $existing = $statement->fetch();

            if (!$existing) {
                return false;
            }

            return $this->updateByIdRow($this->table, (int) $existing['id'], [$key => null]);
        }

        if ($keyColumn === null) {
            return false;
        }

        $statement = $this->query(
            sprintf('DELETE FROM `system_settings` WHERE `%s` = :setting_key', $keyColumn),
            ['setting_key' => $key]
        );

        return $statement->rowCount() > 0;
    }

    private function keyColumn(): ?string
    {
        foreach (['setting_key', 'key', 'name'] as $column) {
            if ($this->columnExists($this->table, $column)) {
                return $column;
            }
        }

        return null;
    }

    private function valueColumn(): ?string
    {
        foreach (['setting_value', 'value'] as $column) {
            if ($this->columnExists($this->table, $column)) {
                return $column;
            }
        }

        return null;
    }
}
