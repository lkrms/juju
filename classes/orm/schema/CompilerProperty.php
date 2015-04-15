<?php

/**
 * Internal use only.
 *
 * @package juju_core
 * @author Luke Arms <luke@arms.to>
 * @copyright Copyright (c) 2012-2014 Luke Arms
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

    protected $IsPrepared = false;

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

        if ( ! $this->_class->SkipPhp)
        {
        }

        if ( ! $this->_class->SkipSql)
        {
            if ($this->_class->TableExists)
            {
                $provider            = $this->_compiler->GetProvider();
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
            else
            {
                $this->ColumnExists     = false;
                $this->ColumnIsCurrent  = false;
            }
        }

        $this->IsPrepared = true;
    }
}

?>