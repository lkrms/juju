<?php

/**
 * Internal use only.
 *
 * @package juju_core
 * @author Luke Arms <luke@arms.to>
 * @copyright Copyright (c) 2012-2015 Luke Arms
 */
class jj_orm_schema_CompilerIndex
{
    public $IndexName;

    public $Unique = false;

    public $Columns = array();

    public $TableName;

    protected $IsPrepared = false;

    public $IndexExists;

    public $IndexIsCurrent;

    /**
     * @var jj_orm_schema_CompilerClass
     */
    private $_class;

    /**
     * @var jj_orm_schema_Compiler
     */
    private $_compiler;

    public function __construct(jj_orm_schema_CompilerClass $class, $indexName)
    {
        $this->_class     = $class;
        $this->_compiler  = $class->GetCompiler();
        $this->IndexName  = $indexName;
    }

    public function Prepare()
    {
        if ($this->IsPrepared)
        {
            return;
        }

        if ( ! $this->_class->SkipSql)
        {
            $provider = $this->_compiler->GetProvider();

            if ($this->_class->TableExists)
            {
                $index              = $provider->GetIndex($this->TableName ? $this->TableName : $this->_class->FullTableName, $this->IndexName);
                $this->IndexExists  = ! is_null($index);

                if ($this->IndexExists)
                {
                    $this->IndexIsCurrent = $provider->IndexMatches($index, $this);
                }
                else
                {
                    $this->IndexIsCurrent = false;
                }
            }
            else
            {
                $this->IndexExists     = false;
                $this->IndexIsCurrent  = false;
            }
        }

        $this->IsPrepared = true;
    }

    public function GetIndexInfo()
    {
        $ind             = new jj_schema_IndexInfo();
        $ind->IndexName  = $this->IndexName;
        $ind->Unique     = $this->Unique;
        $ind->Columns    = $this->GetColumnNames();

        return $ind;
    }

    public function GetColumnNames()
    {
        $cols = array();

        foreach ($this->Columns as $prop)
        {
            if (is_string($prop))
            {
                $cols[] = $prop;
            }
            else
            {
                $cols = array_merge($cols, $prop->GetColumnNames());
            }
        }

        return $cols;
    }
}

?>