<?php

/**
 * Schema provider for MySQL databases.
 *
 * @package juju_core
 * @author Luke Arms <luke@arms.to>
 * @copyright Copyright (c) 2012-2013 Luke Arms
 */
class jj_schema_MySqlProvider extends jj_schema_BaseProvider
{
    protected function GetTables()
    {
        $info    = $this->_conn->GetInfo();
        $dr      = $this->_conn->ExecuteReader("SELECT table_name FROM information_schema.tables WHERE table_schema = '$info->Database' ORDER BY table_name");
        $tables  = array();

        while ($dr->Read())
        {
            $tables[] = $dr->GetValue(0);
        }

        $dr->Close();

        return $tables;
    }

    protected function GetColumns($table)
    {
        $info     = $this->_conn->GetInfo();
        $dr       = $this->_conn->ExecuteReader("SELECT column_name, column_default, is_nullable, data_type, coalesce(character_maximum_length, numeric_precision) AS size, numeric_scale, extra, column_key FROM information_schema.columns WHERE table_schema = '$info->Database' AND table_name = '$table' ORDER BY ordinal_position");
        $columns  = array();

        while ($dr->Read())
        {
            $col                        = new jj_schema_ColumnInfo();
            $col->ColumnName            = $dr->GetValue(0);
            $col->DataType              = $dr->GetValue(3);
            $col->DefaultValue          = $dr->GetValue(1);
            $col->Required              = $dr->GetValue(2) == "NO" ? true : false;
            $col->Size                  = $dr->GetValue(4);
            $col->Scale                 = $dr->GetValue(5);
            $col->AutoIncrement         = $dr->GetValue(6) == "auto_increment";
            $col->PrimaryKey            = $dr->GetValue(7) == "PRI";
            $columns[$col->ColumnName]  = $col;
        }

        $dr->Close();

        return $columns;
    }

    protected function GetIndexes($table)
    {
        $info     = $this->_conn->GetInfo();
        $dr       = $this->_conn->ExecuteReader("SELECT non_unique, index_name, column_name FROM information_schema.statistics WHERE table_schema = '$info->Database' AND table_name = '$table' AND index_name <> 'PRIMARY' ORDER BY index_name, seq_in_index");
        $indexes  = array();

        while ($dr->Read())
        {
            $indexName = $dr->GetValue(1);

            if ( ! array_key_exists($indexName, $indexes))
            {
                $ind                  = new jj_schema_IndexInfo();
                $ind->IndexName       = $indexName;
                $ind->Unique          = $dr->GetValue(0) == 0 ? true : false;
                $indexes[$indexName]  = $ind;
            }

            $indexes[$indexName]->Columns[] = $dr->GetValue(2);
        }

        $dr->Close();

        return $indexes;
    }

    public function ColumnMatches(jj_schema_ColumnInfo $column, jj_orm_schema_CompilerProperty $property, $typeOnly = false)
    {
        $colType     = $property->DataType;
        $checkSize   = false;
        $checkScale  = false;

        switch ($property->DataType)
        {
            case "varchar":
            case "nvarchar":

                $colType    = "varchar";
                $checkSize  = true;

                break;

            case "ntext":

                $colType = "text";

                break;

            case "decimal":

                $checkSize   = true;
                $checkScale  = true;

                break;

            case "boolean":

                $colType = "tinyint";

                break;
        }

        if ($column->DataType != $colType || ( ! $typeOnly && $column->DefaultValue != $property->DefaultValue) || ( ! $typeOnly && $column->Required != $property->Required) || ($checkSize && $column->Size != $property->Size) || ($checkScale && $column->Scale != $property->Scale) || ( ! $typeOnly && $column->AutoIncrement != $property->AutoIncrement))
        {
            return false;
        }

        return true;
    }

    public function IndexMatches(jj_schema_IndexInfo $index, jj_orm_schema_CompilerIndex $compilerIndex)
    {
        // get an array of column names from $compilerIndex
        $cols = $compilerIndex->GetColumnNames();

        if ($index->Unique != $compilerIndex->Unique || $index->Columns != $cols)
        {
            return false;
        }

        return true;
    }

    public function GetCreateTableSql($name, array $columns)
    {
        $name  = $this->_conn->Prefix . $name;
        $sql   = "CREATE TABLE `$name` (";
        $pk    = array();
        $cols  = array();

        foreach ($columns as $column)
        {
            $colType = $column->DataType;

            switch ($colType)
            {
                case "varchar":
                case "nvarchar":

                    // no such thing as nvarchar in mysql
                    $colType = "varchar({$column->Size})";

                    break;

                case "ntext":

                    // no such thing as ntext in mysql
                    $colType = "text";

                    break;

                case "decimal":

                    $colType = "decimal({$column->Size},{$column->Scale})";

                    break;

                case "boolean":

                    $colType = "tinyint";

                    break;
            }

            $colSql = "`{$column->ColumnName}` $colType";

            if ($column->Required)
            {
                $colSql .= " NOT NULL";
            }

            if (isset($column->DefaultValue))
            {
                $default = "'{$column->DefaultValue}'";

                if (is_bool($column->DefaultValue))
                {
                    $default = $column->DefaultValue ? "'1'" : "'0'";
                }

                $colSql .= " DEFAULT $default";
            }

            if ($column->AutoIncrement)
            {
                $colSql .= " AUTO_INCREMENT";
            }

            if ($column->PrimaryKey)
            {
                $pk[] = "`{$column->ColumnName}`";
            }

            $cols[] = $colSql;
        }

        $sql .= implode(", ", $cols);

        if ($pk)
        {
            $sql .= ", PRIMARY KEY (" . implode(", ", $pk) . ")";
        }

        $sql .= ") ENGINE = InnoDB";

        return $sql;
    }

    private static function GetColumnSql(jj_schema_ColumnInfo $column)
    {
        $colType = $column->DataType;

        switch ($colType)
        {
            case "varchar":
            case "nvarchar":

                // no such thing as nvarchar in mysql
                $colType = "varchar({$column->Size})";

                break;

            case "ntext":

                // no such thing as ntext in mysql
                $colType = "text";

                break;

            case "decimal":

                $colType = "decimal({$column->Size},{$column->Scale})";

                break;

            case "boolean":

                $colType = "tinyint";

                break;
        }

        $sql = $colType;

        if ($column->Required)
        {
            $sql .= " NOT NULL";
        }

        if (isset($column->DefaultValue))
        {
            $default = "'{$column->DefaultValue}'";

            if (is_bool($column->DefaultValue))
            {
                $default = $column->DefaultValue ? "'1'" : "'0'";
            }

            $sql .= " DEFAULT $default";
        }

        return $sql;
    }

    public function GetCreateColumnSql($table, jj_schema_ColumnInfo $column)
    {
        $table = $this->_conn->Prefix . $table;

        if ($column->PrimaryKey)
        {
            throw new jj_Exception("Error: primary key columns ({$table}.{$column->ColumnName}) can't be added after table creation.");
        }

        $sql  = "ALTER TABLE `$table` ADD COLUMN `{$column->ColumnName}` ";
        $sql .= self::GetColumnSql($column);

        return $sql;
    }

    public function GetCreateIndexSql($table, jj_schema_IndexInfo $index)
    {
        $table  = $this->_conn->Prefix . $table;
        $sql    = "ALTER TABLE `$table` ADD ";

        if ($index->Unique)
        {
            $sql .= "UNIQUE ";
        }

        $sql .= "INDEX `{$index->IndexName}` (`" . implode("`, `", $index->Columns) . "`)";

        return $sql;
    }

    public function GetDropIndexSql($table, $indexName)
    {
        $table  = $this->_conn->Prefix . $table;
        $sql    = "ALTER TABLE `$table` DROP INDEX `$indexName`";

        return $sql;
    }

    public function GetAlterColumnSql($table, jj_schema_ColumnInfo $column)
    {
        $table = $this->_conn->Prefix . $table;

        if ($column->PrimaryKey)
        {
            throw new jj_Exception("Error: primary key columns ({$table}.{$column->ColumnName}) can't be altered after table creation.");
        }

        $sql  = "ALTER TABLE `$table` CHANGE COLUMN `{$column->ColumnName}` `{$column->ColumnName}` ";
        $sql .= self::GetColumnSql($column);

        return $sql;
    }
}

?>