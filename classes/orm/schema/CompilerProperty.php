<?php

/**
 * Internal use only.
 *
 * @package juju_core
 * @author Luke Arms <luke@arms.to>
 * @copyright Copyright (c) 2012-2015 Luke Arms
 */
class jj_orm_schema_CompilerProperty
{
    public $ColumnName;

    public $PropertyName;

    public $DataType;

    public $DefaultValue = null;

    public $Required = false;

    public $Size;

    public $Scale;

    public $AutoIncrement = false;

    public $LazyLoad = false;

    public $ObjectTypeName;

    /**
     * @var jj_orm_schema_CompilerClass
     */
    public $ObjectType;

    public $ObjectStorageColumns = array();

    public $ObjectStorageTable;

    public $FullObjectStorageTable;

    protected $IsPrepared = false;

    protected $ParentColumns = array();

    protected $ChildColumns = array();

    protected $ObjectSetIndexes = array();

    public $NonExistentColumns = array();

    public $NonCurrentColumns = array();

    public $ColumnExists;

    public $ColumnIsCurrent;

    /**
     * @var jj_orm_schema_CompilerClass
     */
    private $_class;

    /**
     * @var jj_orm_schema_Compiler
     */
    private $_compiler;

    public function __construct(jj_orm_schema_CompilerClass $class, $columnName)
    {
        $this->_class        = $class;
        $this->_compiler     = $class->GetCompiler();
        $this->ColumnName    = $columnName;
        $this->PropertyName  = jj_Common::GetCamelCase($columnName);
    }

    public function Prepare()
    {
        if ($this->IsPrepared)
        {
            return;
        }

        if (in_array($this->DataType, array("object", "objectSet")))
        {
            // we can't store a reference to anything unless it has a primary key
            jj_Assert::IsNotEmpty($this->ObjectType->PrimaryKey, "{$this->ObjectType->TableName} primary key");

            // create a copy of this array--we're going to empty it as we proceed
            $objectStorageColumns  = $this->ObjectStorageColumns;
            $customNames           = ! empty($objectStorageColumns);

            // for objectSets, the first element/s of the array will provide custom names for "parent" key fields
            if ($this->DataType == "objectSet")
            {
                $ind = new jj_orm_schema_CompilerIndex($this->_class, "_idx_parent_" . $this->_class->TableName . "_" . $this->ColumnName);
                $this->ObjectSetIndexes[] = $ind;

                foreach ($this->_class->PrimaryKey as $pkProp)
                {
                    if ($customNames)
                    {
                        $columnName = array_shift($objectStorageColumns);

                        if (is_null($columnName))
                        {
                            throw new jj_Exception("Error: not enough information in objectStorageColumns for {$this->ColumnName} in {$this->_class->TableName}.");
                        }
                    }
                    else
                    {
                        $columnName = "_parent_" . $this->_class->TableName . "_" . $pkProp->ColumnName;
                    }

                    $this->ParentColumns[$columnName]  = $pkProp;
                    $ind->Columns[]                    = $columnName;
                }

                if ( ! $this->ObjectStorageTable)
                {
                    $this->ObjectStorageTable = "_ref_" . $this->_class->TableName . "_" . $this->ColumnName . "_" . $this->ObjectType->TableName;
                }

                $this->FullObjectStorageTable  = $this->_compiler->TablePrefix . $this->ObjectStorageTable;
                $ind->TableName                = $this->FullObjectStorageTable;
                $ind->Prepare();
            }

            // for both objects and objectSets, remaining array elements will provide custom names for "child" key fields
            $ind = new jj_orm_schema_CompilerIndex($this->_class, "_idx_" . ($this->DataType == "objectSet" ? "child_" : "") . $this->_class->TableName . "_" . $this->ColumnName);

            foreach ($this->ObjectType->PrimaryKey as $pkProp)
            {
                if ($customNames)
                {
                    $columnName = array_shift($objectStorageColumns);

                    if (is_null($columnName))
                    {
                        throw new jj_Exception("Error: not enough information in objectStorageColumns for {$this->ColumnName} in {$this->_class->TableName}.");
                    }
                }
                else
                {
                    if ($this->DataType == "objectSet")
                    {
                        $columnName = "_child_" . $this->ObjectType->TableName . "_" . $pkProp->ColumnName;
                    }
                    else
                    {
                        $columnName = $this->ColumnName . "_" . $pkProp->ColumnName;
                    }
                }

                $this->ChildColumns[$columnName]  = $pkProp;
                $ind->Columns[]                   = $columnName;
            }

            if ($this->DataType == "objectSet")
            {
                $this->ObjectSetIndexes[]  = $ind;
                $ind->TableName            = $this->FullObjectStorageTable;
                $ind->Prepare();
            }
            else
            {
                $this->_class->Indexes[$ind->IndexName] = $ind;
            }
        }

        if ( ! $this->_class->SkipPhp)
        {
        }

        if ( ! $this->_class->SkipSql)
        {
            $provider = $this->_compiler->GetProvider();

            if ($this->DataType == "objectSet")
            {
                $this->ColumnExists = $provider->HasTable($this->FullObjectStorageTable);

                if ($this->ColumnExists)
                {
                    $this->CheckObjectColumns($this->FullObjectStorageTable, $this->ParentColumns);
                    $this->CheckObjectColumns($this->FullObjectStorageTable, $this->ChildColumns);
                    $this->ColumnIsCurrent = empty($this->NonExistentColumns) && empty($this->NonCurrentColumns);
                }
                else
                {
                    $this->NonExistentColumns  = array_merge($this->ParentColumns, $this->ChildColumns);
                    $this->ColumnIsCurrent     = false;
                }
            }
            elseif ($this->_class->TableExists)
            {
                if ($this->DataType == "object")
                {
                    $this->CheckObjectColumns($this->_class->FullTableName, $this->ChildColumns);
                    $this->ColumnExists     = empty($this->NonExistentColumns);
                    $this->ColumnIsCurrent  = empty($this->NonCurrentColumns);
                }
                else
                {
                    $column              = $provider->GetColumn($this->_class->FullTableName, $this->ColumnName);
                    $this->ColumnExists  = ! is_null($column);

                    if ($this->ColumnExists)
                    {
                        $this->ColumnIsCurrent = $provider->ColumnMatches($column, $this);
                    }
                    else
                    {
                        $this->ColumnIsCurrent = false;
                    }
                }
            }
            else
            {
                $this->ColumnExists        = false;
                $this->ColumnIsCurrent     = false;
                $this->NonExistentColumns  = $this->ChildColumns;
            }
        }

        $this->IsPrepared = true;
    }

    private function CheckObjectColumns($tableName, $columns)
    {
        $provider = $this->_compiler->GetProvider();

        foreach ($columns as $columnName => $prop)
        {
            $column = $provider->GetColumn($tableName, $columnName);

            if (is_null($column))
            {
                $this->NonExistentColumns[$columnName] = $prop;
            }
            else
            {
                $current = $provider->ColumnMatches($column, $prop, true) && ($this->DataType != "object" || $column->Required == $this->Required);

                if ( ! $current)
                {
                    $this->NonCurrentColumns[$columnName] = $prop;
                }
            }
        }
    }

    private function GetColumnInfo()
    {
        $col                 = new jj_schema_ColumnInfo();
        $col->ColumnName     = $this->ColumnName;
        $col->DataType       = $this->DataType;
        $col->DefaultValue   = $this->DefaultValue;
        $col->Required       = $this->Required;
        $col->Size           = $this->Size;
        $col->Scale          = $this->Scale;
        $col->AutoIncrement  = $this->AutoIncrement;
        $col->PrimaryKey     = in_array($this, $this->_class->PrimaryKey);

        return $col;
    }

    public function GetColumnNames()
    {
        $cols = array();

        if ($this->DataType != "objectSet")
        {
            if ($this->DataType == "object")
            {
                foreach ($this->ChildColumns as $columnName => $prop)
                {
                    $cols[] = $columnName;
                }
            }
            else
            {
                $cols[] = $this->ColumnName;
            }
        }

        return $cols;
    }

    public function GetNewColumns()
    {
        $cols = array();

        if ($this->DataType != "objectSet")
        {
            if ($this->DataType == "object")
            {
                foreach ($this->NonExistentColumns as $columnName => $prop)
                {
                    $col                 = $prop->GetColumnInfo();
                    $col->ColumnName     = $columnName;
                    $col->Required       = $this->Required;
                    $col->AutoIncrement  = false;
                    $col->PrimaryKey     = in_array($this, $this->_class->PrimaryKey);
                    $cols[]              = $col;
                }
            }
            elseif ( ! $this->ColumnExists)
            {
                $cols[] = $this->GetColumnInfo();
            }
        }

        return $cols;
    }

    public function GetChangedColumns()
    {
        $cols = array();

        if ($this->DataType != "objectSet")
        {
            if ($this->DataType == "object")
            {
                foreach ($this->NonCurrentColumns as $columnName => $prop)
                {
                    $col                 = $prop->GetColumnInfo();
                    $col->ColumnName     = $columnName;
                    $col->Required       = $this->Required;
                    $col->AutoIncrement  = false;
                    $col->PrimaryKey     = in_array($this, $this->_class->PrimaryKey);
                    $cols[]              = $col;
                }
            }
            elseif ($this->ColumnExists && ! $this->ColumnIsCurrent)
            {
                $cols[] = $this->GetColumnInfo();
            }
        }

        return $cols;
    }

    public function GetObjectSetSql()
    {
        $sql = array();

        if ($this->DataType == "objectSet")
        {
            $provider = $this->_compiler->GetProvider();

            // i.e. if the reference table exists
            if ( ! $this->ColumnExists)
            {
                $columns = array();

                foreach ($this->NonExistentColumns as $columnName => $prop)
                {
                    $col                 = $prop->GetColumnInfo();
                    $col->ColumnName     = $columnName;
                    $col->AutoIncrement  = false;
                    $columns[]           = $col;
                }

                $sql[] = $provider->GetCreateTableSql($this->FullObjectStorageTable, $columns);
            }
            else
            {
                foreach ($this->NonCurrentColumns as $columnName => $prop)
                {
                    $col                 = $prop->GetColumnInfo();
                    $col->ColumnName     = $columnName;
                    $col->AutoIncrement  = false;
                    $sql[]               = $provider->GetAlterColumnSql($this->FullObjectStorageTable, $col);
                }

                foreach ($this->NonExistentColumns as $columnName => $prop)
                {
                    $col                 = $prop->GetColumnInfo();
                    $col->ColumnName     = $columnName;
                    $col->AutoIncrement  = false;
                    $sql[]               = $provider->GetCreateColumnSql($this->FullObjectStorageTable, $col);
                }
            }

            foreach ($this->ObjectSetIndexes as $ind)
            {
                if ($ind->IndexExists && ! $ind->IndexIsCurrent)
                {
                    $sql[] = $provider->GetDropIndexSql($this->FullObjectStorageTable, $ind->IndexName);
                }

                if ( ! $ind->IndexExists || ! $ind->IndexIsCurrent)
                {
                    $sql[] = $provider->GetCreateIndexSql($this->FullObjectStorageTable, $ind->GetIndexInfo());
                }
            }
        }

        return $sql;
    }
}

// PRETTY_NESTED_ARRAYS,0

?>