<?php

namespace Backpack\CRUD\app\Library\Database;

class TableSchema
{
    /** @var array */
    public $schema;

    public function __construct(string $connection, string $table)
    {
        $this->schema = DatabaseSchema::getForTable($connection, $table);
    }

    /**
     * Return an array of column names in database.
     *
     * @return array
     */
    public function getColumnsNames()
    {
        return array_column($this->getColumns(), 'name');
    }

    /**
     * Return the column type in database.
     *
     * @param  string  $columnName
     * @return string
     */
    public function getColumnType(string $columnName)
    {
        if (! $this->schemaExists() || ! $this->hasColumn($columnName)) {
            return 'varchar';
        }

        $column = $this->getColumn($columnName);

        return $column['type_name'];
    }

    /**
     * Check if the column exists in the database.
     *
     * @param  string  $columnName
     * @return bool
     */
    public function hasColumn($columnName)
    {
        if (! $this->schemaExists()) {
            return false;
        }

        return $this->getColumn($columnName) !== null;
    }

    /**
     * Check if the column is nullable in database.
     *
     * @param  string  $columnName
     * @return bool
     */
    public function columnIsNullable($columnName)
    {
        if (! $this->columnExists($columnName)) {
            return true;
        }

        $column = $this->getColumn($columnName);

        return $column['nullable'];
    }

    /**
     * Check if the column has default value set on database.
     *
     * @param  string  $columnName
     * @return bool
     */
    public function columnHasDefault($columnName)
    {
        if (! $this->columnExists($columnName)) {
            return false;
        }

        $column = $this->getColumn($columnName);

        return $column['default'] !== null ? true : false;
    }

    /**
     * Get the default value for a column on database.
     *
     * @param  string  $columnName
     * @return bool
     */
    public function getColumnDefault($columnName)
    {
        if (! $this->columnExists($columnName)) {
            return false;
        }

        $column = $this->getColumn($columnName);

        return $column['default'];
    }

    /**
     * Get the table schema columns.
     *
     * @return array
     */
    public function getColumns()
    {
        if (! $this->schemaExists()) {
            return [];
        }

        return array_map(function ($column) {
            return $this->normalizeColumn($column);
        }, $this->schema['columns'] ?? []);
    }

    /**
     * Get the table schema indexes.
     *
     * @return array
     */
    public function getIndexes()
    {
        if (! $this->schemaExists()) {
            return [];
        }

        return array_map(function ($index) {
            return $this->normalizeIndex($index);
        }, $this->schema['indexes'] ?? []);
    }

    /**
     * Make sure column exists or throw an exception.
     *
     * @param  string  $columnName
     * @return bool
     */
    private function columnExists($columnName)
    {
        if (! $this->schemaExists()) {
            return false;
        }

        return $this->hasColumn($columnName);
    }

    private function getColumn($columnName)
    {
        foreach ($this->getColumns() as $column) {
            if (strtolower($column['name']) === strtolower($columnName)) {
                return $column;
            }
        }

        return null;
    }

    private function normalizeColumn($column)
    {
        if (is_array($column)) {
            $typeName = $column['type_name'] ?? strtok($column['type'] ?? '', '(') ?: 'varchar';

            return [
                'name' => $column['name'],
                'type_name' => $this->normalizeTypeName($typeName),
                'type' => $column['type'] ?? $typeName,
                'nullable' => $column['nullable'] ?? true,
                'default' => $this->normalizeDefault($column['default'] ?? null),
            ];
        }

        $typeName = $column->getType()->getName();

        return [
            'name' => $column->getName(),
            'type_name' => $this->normalizeTypeName($typeName),
            'type' => $typeName,
            'nullable' => ! $column->getNotnull(),
            'default' => $this->normalizeDefault($column->getDefault()),
        ];
    }

    private function normalizeIndex($index)
    {
        if (is_array($index)) {
            return [
                'name' => $index['name'] ?? null,
                'columns' => $index['columns'] ?? [],
                'primary' => $index['primary'] ?? false,
                'unique' => $index['unique'] ?? false,
            ];
        }

        return [
            'name' => $index->getName(),
            'columns' => $index->getColumns(),
            'primary' => $index->isPrimary(),
            'unique' => $index->isUnique(),
        ];
    }

    private function normalizeTypeName($typeName)
    {
        $typeMap = [
            'bigint' => 'integer',
            'double' => 'float',
            'jsonb' => 'json',
            'numeric' => 'decimal',
            'tinyint' => 'boolean',
            'varchar' => 'string',
        ];

        return $typeMap[$typeName] ?? $typeName;
    }

    private function normalizeDefault($default)
    {
        if (! is_string($default)) {
            return $default;
        }

        $default = trim($default, "'");

        return is_numeric($default) ? $default + 0 : $default;
    }

    /**
     * Make sure the schema for the connection is initialized.
     *
     * @return bool
     */
    private function schemaExists()
    {
        if (! empty($this->schema)) {
            return true;
        }

        return false;
    }
}
