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