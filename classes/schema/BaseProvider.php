<?php

/**
 * Base class for schema providers.
 *
 * @package juju_core
 * @author Luke Arms <luke@arms.to>
 * @copyright Copyright (c) 2012-2015 Luke Arms
 */
abstract class jj_schema_BaseProvider
{
    /**
     * @var jj_data_Connection
     */
    protected $_conn;

    protected $_tables = array();

    protected $_indexes = array();

    protected function __construct(jj_data_Connection $conn)
    {
        $this->_conn = $conn;

        // for future reference / comparison, we're going to need to know the current state of the database
        $tableNames = $this->GetTables();

        foreach ($tableNames as $tableName)
        {
            $realTableName = $tableName;

            if ($this->_conn->Prefix)
            {
                if (substr($tableName, 0, strlen($this->_conn->Prefix)) == $this->_conn->Prefix)
                {
                    $tableName = substr($tableName, strlen($this->_conn->Prefix));
                }
                else
                {
                    continue;
                }
            }

            $this->_tables[$tableName]   = $this->GetColumns($realTableName);
            $this->_indexes[$tableName]  = $this->GetIndexes($realTableName);
        }
    }

    /**
     * Returns a schema provider instance for the given database connection.
     *
     * @param jj_data_Connection $conn Connection to the target database.
     * @return jj_schema_BaseProvider New schema provider instance.
     */
    public static function ByConnection(jj_data_Connection $conn)
    {
        jj_Assert::IsNotNull($conn, "conn");

        switch ($conn->Type)
        {
            case jj_data_Connection::TYPE_MYSQL:

                return new jj_schema_MySqlProvider($conn);

                break;

            default:

                throw new jj_Exception("Connection type $conn->Type isn't supported for schema management.");
        }
    }

    public function HasTable($table)
    {
        return array_key_exists($table, $this->_tables);
    }

    public function HasColumn($table, $column)
    {
        if ($this->HasTable($table))
        {
            return array_key_exists($column, $this->_tables[$table]);
        }
        else
        {
            return false;
        }
    }

    public function HasIndex($table, $index)
    {
        if ($this->HasTable($table))
        {
            return array_key_exists($index, $this->_indexes[$table]);
        }
        else
        {
            return false;
        }
    }

    /**
     * @return jj_schema_ColumnInfo
     */
    public function GetColumn($table, $column)
    {
        if ($this->HasColumn($table, $column))
        {
            return $this->_tables[$table][$column];
        }
        else
        {
            return null;
        }
    }

    /**
     * @return jj_schema_IndexInfo
     */
    public function GetIndex($table, $index)
    {
        if ($this->HasIndex($table, $index))
        {
            return $this->_indexes[$table][$index];
        }
        else
        {
            return null;
        }
    }

    /**
     * Returns TRUE if the database column described in $column matches the schema property in $property.
     *
     * The comparison needs to be handled by the provider because some databases only support a subset of schema features,
     * which could lead to unnecessary mismatches being identified by the base provider, forcing expensive database changes.
     *
     * @param jj_schema_ColumnInfo $column Database column.
     * @param jj_orm_schema_CompilerProperty $property Schema property.
     * @param boolean $typeOnly If TRUE, the provider should ignore everything except the data types of the column and property.
     * @return boolean TRUE if the column and property match, FALSE otherwise.
     */
    abstract public function ColumnMatches(jj_schema_ColumnInfo $column, jj_orm_schema_CompilerProperty $property, $typeOnly = false);

    /**
     * Returns TRUE if the database index described in $index matches the schema index in $compilerIndex.
     *
     * @param jj_schema_IndexInfo $index Database index.
     * @param jj_orm_schema_CompilerIndex $compilerIndex Schema index.
     * @return boolean TRUE if the indexes match, FALSE otherwise.
     */
    abstract public function IndexMatches(jj_schema_IndexInfo $index, jj_orm_schema_CompilerIndex $compilerIndex);

    /**
     * Returns an array of table names for every table currently in the target database.
     *
     * @return array An array of table names.
     */
    abstract protected function GetTables();

    /**
     * Returns an array of jj_schema_ColumnInfo objects for every column currently in the given table of the target database.
     *
     * @param string $table The table name.
     * @return array An array of jj_schema_ColumnInfo objects, keyed by column name.
     */
    abstract protected function GetColumns($table);

    /**
     * Returns an array of jj_schema_IndexInfo objects for every non-primary index currently in the given table of the target database.
     *
     * @param string $table The table name.
     * @return array An array of jj_schema_IndexInfo objects, keyed by index name.
     */
    abstract protected function GetIndexes($table);

    /**
     * Returns SQL to create the given table.
     *
     * @param string $name The name of the table to create.
     * @param array $columns An array of jj_schema_ColumnInfo objects.
     * @return string SQL to create the table, e.g. a CREATE TABLE statement.
     */
    abstract public function GetCreateTableSql($name, array $columns);

    /**
     * Returns SQL to create the given column.
     *
     * @param string $table The name of the table to add the column to.
     * @param jj_schema_ColumnInfo $column A jj_schema_ColumnInfo object.
     * @return string SQL to create the column, e.g. an ALTER TABLE statement.
     */
    abstract public function GetCreateColumnSql($table, jj_schema_ColumnInfo $column);

    /**
     * Returns SQL to alter an existing column.
     *
     * @param string $table The name of the table to which the column belongs.
     * @param jj_schema_ColumnInfo $column A jj_schema_ColumnInfo object.
     * @return string SQL to alter the column, e.g. an ALTER TABLE statement.
     */
    abstract public function GetAlterColumnSql($table, jj_schema_ColumnInfo $column);

    /**
     * Returns SQL to create the given index.
     *
     * @param string $table The name of the table to add the index to.
     * @param jj_schema_IndexInfo $index A jj_schema_IndexInfo object.
     * @return string SQL to create the index, e.g. an ALTER TABLE statement.
     */
    abstract public function GetCreateIndexSql($table, jj_schema_IndexInfo $index);

    /**
     * Returns SQL to drop (delete) the given index.
     *
     * @param string $table The name of the table with the index to drop.
     * @param string $indexName The name of the index to drop.
     * @return string SQL to drop the index, e.g. an ALTER TABLE statement.
     */
    abstract public function GetDropIndexSql($table, $indexName);
}

?>