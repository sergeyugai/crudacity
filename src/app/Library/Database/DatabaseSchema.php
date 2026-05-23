<?php

namespace Backpack\CRUD\app\Library\Database;

use Illuminate\Support\Facades\DB;

final class DatabaseSchema
{
    private static $schema;

    /**
     * Return the schema for the table.
     *
     * @param  string  $connection
     * @param  string  $table
     * @return array
     */
    public static function getForTable(string $connection, string $table)
    {
        self::generateDatabaseSchema($connection, $table);

        return self::$schema[$connection][$table] ?? [];
    }

    /**
     * Generates and store the database schema.
     *
     * @param  string  $connection
     * @param  string  $table
     * @return void
     */
    private static function generateDatabaseSchema(string $connection, string $table)
    {
        if (! isset(self::$schema[$connection])) {
            self::$schema[$connection] = [];
        } else {
            // check for a specific table in case it was created after schema had been generated.
            if (! isset(self::$schema[$connection][$table])) {
                self::$schema[$connection][$table] = self::readTableSchema($connection, $table);
            }
        }

        if (! isset(self::$schema[$connection][$table])) {
            self::$schema[$connection][$table] = self::readTableSchema($connection, $table);
        }
    }

    private static function readTableSchema(string $connectionName, string $table)
    {
        $connection = DB::connection($connectionName);
        $schema = $connection->getSchemaBuilder();

        if (method_exists($schema, 'getColumns')) {
            return [
                'columns' => $schema->getColumns($table),
                'indexes' => method_exists($schema, 'getIndexes') ? $schema->getIndexes($table) : [],
            ];
        }

        $prefixedTable = $connection->getTablePrefix().$table;
        $schemaManager = $connection->getDoctrineSchemaManager();

        return [
            'columns' => $schemaManager->listTableColumns($prefixedTable),
            'indexes' => $schemaManager->listTableIndexes($prefixedTable),
        ];
    }
}
