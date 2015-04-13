<?php

/**
 * Internal use only.
 *
 * @package juju_core
 * @author Luke Arms <luke@arms.to>
 * @copyright Copyright (c) 2012-2014 Luke Arms
 */
class jj_orm_schema_CompilerClass
{
    public $TableName;

    public $ClassName;

    public $ClassNamespace;

    public $BaseClass;

    public $SkipPhp = false;

    public $SkipSql = false;

    public $ReadOnly = false;

    public $Properties = array();

    public $PrimaryKey = array();

    protected $IsPrepared = false;

    public $FullTableName;

    public $TableExists;

    protected $FullClassName;

    protected $ClassPath;

    protected $CompiledClassName;

    protected $CompiledClassPath;

    /**
     * @var jj_orm_schema_Compiler
     */
    private $_compiler;

    public function __construct(jj_orm_schema_Compiler $compiler, $tableName)
    {
        $this->_compiler       = $compiler;
        $this->TableName       = $tableName;
        $this->ClassName       = jj_Common::GetCamelCase($tableName);
        $this->ClassNamespace  = $compiler->SchemaNamespace;
        $this->BaseClass       = $compiler->BaseClass;
    }

    public function Prepare()
    {
        if ($this->IsPrepared)
        {
            return;
        }

        if ( ! $this->SkipPhp)
        {
            $this->FullClassName  = str_replace(".", "_", $this->ClassNamespace) . "_" . $this->ClassName;
            $this->ClassPath      = jj_Autoload::GetClassPath($this->FullClassName, false);

            if (is_null($this->ClassPath))
            {
                throw new jj_Exception("Error: unable to determine path for class {$this->FullClassName} defined in schema {$this->_compiler->SchemaName}.");
            }

            $this->CompiledClassName  = "jj_orm_base_" . $this->FullClassName;
            $this->CompiledClassPath  = jj_Autoload::GetClassPath($this->CompiledClassName, false);
        }

        if ( ! $this->SkipSql)
        {
            $provider             = $this->_compiler->GetProvider();
            $this->FullTableName  = $this->_compiler->TablePrefix . $this->TableName;
            $this->TableExists    = $provider->HasTable($this->FullTableName);
        }

        foreach ($this->Properties as $prop)
        {
            $prop->Prepare();
        }

        $this->IsPrepared = true;
    }

    /**
     * @return jj_orm_schema_Compiler
     */
    public function GetCompiler()
    {
        return $this->_compiler;
    }
}

?>