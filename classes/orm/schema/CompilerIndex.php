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
        }

        $this->IsPrepared = true;
    }
}

?>