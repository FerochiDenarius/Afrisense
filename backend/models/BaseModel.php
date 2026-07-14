<?php

declare(strict_types=1);

namespace AfriSense\Backend\Models;

use PDO;
use PDOStatement;

abstract class BaseModel
{
    protected PDO $pdo;
    private array $columnCache = [];
    private array $tableCache = [];

    /**
     * Create the model with a PDO connection.
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    protected function tableExists(string $table): bool
    {
        if (array_key_exists($table, $this->tableCache)) {
            return $this->tableCache[$table];
        }

        $statement = $this->query(
            'SELECT COUNT(*) AS count_value
             FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table',
            ['table' => $table]
        );

        $row = $statement->fetch();
        $this->tableCache[$table] = ((int) ($row['count_value'] ?? 0)) > 0;

        return $this->tableCache[$table];
    }

    protected function columnExists(string $table, string $column): bool
    {
        return in_array($column, $this->getColumns($table), true);
    }

    protected function getColumns(string $table): array
    {
        if (array_key_exists($table, $this->columnCache)) {
            return $this->columnCache[$table];
        }

        $statement = $this->query(
            'SELECT COLUMN_NAME
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table',
            ['table' => $table]
        );

        $this->columnCache[$table] = array_map(
            static fn (array $row): string => $row['COLUMN_NAME'],
            $statement->fetchAll()
        );

        return $this->columnCache[$table];
    }

    protected function filterColumns(string $table, array $data): array
    {
        $columns = $this->getColumns($table);

        return array_filter(
            $data,
            static fn (string $column): bool => in_array($column, $columns, true),
            ARRAY_FILTER_USE_KEY
        );
    }

    protected function findByIdRow(string $table, int $id): ?array
    {
        if (!$this->tableExists($table) || !$this->columnExists($table, 'id')) {
            return null;
        }

        $statement = $this->query(
            sprintf('SELECT * FROM `%s` WHERE `id` = :id LIMIT 1', $table),
            ['id' => $id]
        );

        $row = $statement->fetch();

        return $row ?: null;
    }

    protected function allRows(string $table, string $orderBy = 'id', string $direction = 'ASC'): array
    {
        if (!$this->tableExists($table)) {
            return [];
        }

        $columns = $this->getColumns($table);
        $orderColumn = in_array($orderBy, $columns, true) ? $orderBy : ($columns[0] ?? 'id');
        $sortDirection = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';

        $statement = $this->query(
            sprintf('SELECT * FROM `%s` ORDER BY `%s` %s', $table, $orderColumn, $sortDirection)
        );

        return $statement->fetchAll();
    }

    protected function insertRow(string $table, array $data): ?int
    {
        if (!$this->tableExists($table)) {
            return null;
        }

        $filtered = $this->filterColumns($table, $data);

        if ($filtered === []) {
            return null;
        }

        $columns = array_keys($filtered);
        $placeholders = array_map(static fn (string $column): string => ':' . $column, $columns);

        $this->query(
            sprintf(
                'INSERT INTO `%s` (`%s`) VALUES (%s)',
                $table,
                implode('`, `', $columns),
                implode(', ', $placeholders)
            ),
            $filtered
        );

        return (int) $this->pdo->lastInsertId();
    }

    protected function updateByIdRow(string $table, int $id, array $data): bool
    {
        if (!$this->tableExists($table) || !$this->columnExists($table, 'id')) {
            return false;
        }

        $filtered = $this->filterColumns($table, $data);
        unset($filtered['id']);

        if ($filtered === []) {
            return false;
        }

        $assignments = array_map(
            static fn (string $column): string => sprintf('`%s` = :%s', $column, $column),
            array_keys($filtered)
        );

        $filtered['id'] = $id;
        $statement = $this->query(
            sprintf(
                'UPDATE `%s` SET %s WHERE `id` = :id',
                $table,
                implode(', ', $assignments)
            ),
            $filtered
        );

        return $statement->rowCount() > 0;
    }

    protected function deleteByIdRow(string $table, int $id): bool
    {
        if (!$this->tableExists($table) || !$this->columnExists($table, 'id')) {
            return false;
        }

        $statement = $this->query(
            sprintf('DELETE FROM `%s` WHERE `id` = :id', $table),
            ['id' => $id]
        );

        return $statement->rowCount() > 0;
    }

    protected function query(string $sql, array $params = []): PDOStatement
    {
        $statement = $this->pdo->prepare($sql);

        foreach ($params as $key => $value) {
            $parameter = is_int($key) ? $key + 1 : ':' . ltrim((string) $key, ':');
            $statement->bindValue($parameter, $value);
        }

        $statement->execute();

        return $statement;
    }
}
