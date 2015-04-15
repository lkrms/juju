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
        $dr       = $this->_conn->ExecuteReader("SELECT column_name, column_default, is_nullable, data_type, coalesce(character_maximum_length, numeric_precision) AS size, numeric_scale, extra FROM information_schema.columns WHERE table_schema = '$info->Database' AND table_name = '$table' ORDER BY ordinal_position");
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
            $columns[$col->ColumnName]  = $col;
        }

        $dr->Close();

        return $columns;
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

    public function GetCreateTableSql($name, array $columns)
    {
        $sql   = "CREATE TABLE $name (";
        $pk    = array();
        $cols  = array();

        foreach ($columns as $colName => $col)
        {
            $colType = $col["type"];

            switch ($colType)
            {
                case "varchar":
                case "nvarchar":

                    // no such thing as nvarchar in mysql
                    $colType = "varchar($col[length])";

                    break;

                case "ntext":

                    // no such thing as ntext in mysql
                    $colType = "text";

                    break;

                case "decimal":

                    $colType = "decimal($col[precision],$col[scale])";

                    break;
            }

            $colSql = "$colName $colType";

            if ($col["notnull"])
            {
                $colSql .= " NOT NULL";
            }

            if ($col["identity"])
            {
                $colSql .= " AUTO_INCREMENT";
            }

            if ($col["primarykey"])
            {
                $pk[] = $colName;
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

    private static function GetColumnSql( array $column)
    {
        $sql      = "";
        $colType  = $column["type"];

        switch ($colType)
        {
            case "varchar":
            case "nvarchar":

                // no such thing as nvarchar in mysql
                $colType = "varchar($column[length])";

                break;

            case "ntext":

                // no such thing as ntext in mysql
                $colType = "text";

                break;

            case "decimal":

                $colType = "decimal($column[precision],$column[scale])";

                break;
        }

        $sql .= $colType;

        if ($column["notnull"])
        {
            $sql .= " NOT NULL";
        }

        return $sql;
    }

    public function GetCreateColumnSql($table, $name, array $column)
    {
        $sql  = "ALTER TABLE $table ADD COLUMN $name ";
        $sql .= self::GetColumnSql($column);

        return $sql;
    }

    public function GetCreateIndexSql($table, $name, array $index)
    {
        $sql = "ALTER TABLE $table ADD ";

        if ($index["unique"])
        {
            $sql .= "UNIQUE ";
        }

        $sql .= "INDEX $name (" . implode(", ", $index["fields"]) . ")";

        return $sql;
    }

    private static function GetReferenceOption($name, $value)
    {
        $sql = "";

        if ($value)
        {
            switch ($name)
            {
                case "ondelete":

                    $sql .= " ON DELETE ";

                    break;

                case "onupdate":

                    $sql .= " ON UPDATE ";

                    break;
            }

            switch ($value)
            {
                case "restrict":

                    $sql .= "RESTRICT";

                    break;

                case "cascade":

                    $sql .= "CASCADE";

                    break;

                case "setnull":

                    $sql .= "SET NULL";

                    break;
            }
        }

        return $sql;
    }

    public function GetCreateReferenceSql($table, $name, array $reference)
    {
        $sql  = "ALTER TABLE $table ADD CONSTRAINT $name FOREIGN KEY (";
        $sql .= implode(", ", $reference["fields"]);
        $sql .= ") REFERENCES $reference[reftable] (";
        $sql .= implode(", ", $reference["reffields"]);
        $sql .= ")";
        $sql .= self::GetReferenceOption("ondelete", $reference["ondelete"]);
        $sql .= self::GetReferenceOption("onupdate", $reference["onupdate"]);

        return $sql;
    }

    public function GetDropTableSql($table)
    {
        $sql = "DROP TABLE $table";

        return $sql;
    }

    public function GetDropColumnSql($table, $name)
    {
        $sql = "ALTER TABLE $table DROP COLUMN $name";

        return $sql;
    }

    public function GetDropIndexSql($table, $name)
    {
        $sql = "ALTER TABLE $table DROP INDEX $name";

        return $sql;
    }

    public function GetDropReferenceSql($table, $name)
    {
        $sql = "ALTER TABLE $table DROP FOREIGN KEY $name";

        return $sql;
    }

    public function GetAlterColumnSql($table, $name, array $column)
    {
        $sql  = "ALTER TABLE $table CHANGE COLUMN $name $name ";
        $sql .= self::GetColumnSql($column);

        return $sql;
    }
}

?>