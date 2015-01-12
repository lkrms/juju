<?php

/**
 * Base class for schema providers.
 *
 * @package juju_core
 * @author Luke Arms <luke@arms.to>
 * @copyright Copyright (c) 2012-2013 Luke Arms
 */
abstract class jj_schema_BaseProvider
{
    /**
     * Returns SQL to create the given table.
     *
     * @param string $name The name of the table to create.
     * @param array $columns An array of column definition arrays, keyed by column name, with elements type, notnull, identity, primarykey, length, precision and scale.
     * @return string SQL to create the table, e.g. a CREATE TABLE statement.
     */
    abstract public function GetCreateTableSql($name, array $columns);

    /**
     * Returns SQL to create the given column.
     *
     * @param string $table The name of the table to add the column to.
     * @param string $name The name of the column to add.
     * @param array $column A column definition array, with elements type, notnull, length, precision and scale.
     * @return string SQL to create the column, e.g. an ALTER TABLE statement.
     */
    abstract public function GetCreateColumnSql($table, $name, array $column);

    /**
     * Returns SQL to create the given index.
     *
     * @param string $table The name of the table to add the index to.
     * @param string $name The name of the index to add.
     * @param array $index An index definition array, with elements unique and fields.
     * @return string SQL to create the index, e.g. an ALTER TABLE statement.
     */
    abstract public function GetCreateIndexSql($table, $name, array $index);

    /**
     * Returns SQL to create the given reference.
     *
     * @param string $table The name of the table to add the reference to.
     * @param string $name The name of the reference to add.
     * @param array $reference A reference definition array, with elements fields, reftable, reffields, ondelete and onupdate (restrict, cascade or setnull).
     * @return string SQL to create the reference, e.g. an ALTER TABLE statement.
     */
    abstract public function GetCreateReferenceSql($table, $name, array $reference);

    /**
     * Returns SQL to drop the given table.
     *
     * @param string $table The name of the table to drop.
     * @return string SQL to drop the table, e.g. a DROP TABLE statement.
     */
    abstract public function GetDropTableSql($table);

    /**
     * Returns SQL to drop the given column.
     *
     * @param string $table The name of the table to which the column belongs.
     * @param string $name The name of the column to drop.
     * @return string SQL to drop the column, e.g. an ALTER TABLE statement.
     */
    abstract public function GetDropColumnSql($table, $name);

    /**
     * Returns SQL to drop the given index.
     *
     * @param string $table The name of the table to which the index belongs.
     * @param string $name The name of the index to drop.
     * @return string SQL to drop the index, e.g. an ALTER TABLE statement.
     */
    abstract public function GetDropIndexSql($table, $name);

    /**
     * Returns SQL to drop the given reference.
     *
     * @param string $table The name of the table to which the reference belongs.
     * @param string $name The name of the reference to drop.
     * @return string SQL to drop the reference, e.g. an ALTER TABLE statement.
     */
    abstract public function GetDropReferenceSql($table, $name);

    /**
     * Returns SQL to alter an existing column.
     *
     * @param string $table The name of the table to which the column belongs.
     * @param string $name The name of the column to alter.
     * @param array $column A column definition array, with elements type, notnull, length, precision and scale.
     * @return string SQL to alter the column, e.g. an ALTER TABLE statement.
     */
    abstract public function GetAlterColumnSql($table, $name, array $column);
}

?>