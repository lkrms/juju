<?php

namespace jj\orm\schema;

/**
 * Internal use only.
 *
 * @package juju_core
 * @author Luke Arms <luke@arms.to>
 * @copyright Copyright (c) 2012-2015 Luke Arms
 */
class CompilerClass
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

    public $Indexes = array();

    protected $IsPrepared = false;

    public $FullTableName;

    public $TableExists;

    protected $FullClassName;

    protected $ClassPath;

    protected $CompiledClassName;

    protected $CompiledClassPath;

    /**
     * @var Compiler
     */
    private $_compiler;

    public function __construct(Compiler $compiler, $tableName)
    {
        $this->_compiler       = $compiler;
        $this->TableName       = $tableName;
        $this->ClassName       = \jj\Common::GetCamelCase($tableName);
        $this->ClassNamespace  = $compiler->SchemaNamespace;
        $this->BaseClass       = $compiler->BaseClass;
    }

    public function Prepare()
    {
        if ($this->IsPrepared)
        {
            return;
        }

        $this->FullTableName = $this->_compiler->TablePrefix . $this->TableName;

        if ( ! $this->SkipPhp)
        {
            $this->FullClassName  = $this->ClassNamespace . "\\" . $this->ClassName;
            $this->ClassPath      = \jj\Autoload::GetClassPath($this->FullClassName, false);

            if (is_null($this->ClassPath))
            {
                throw new \jj\Exception("Error: unable to determine path for class {$this->FullClassName} defined in schema {$this->_compiler->SchemaName}.");
            }

            $this->CompiledClassName  = $this->FullClassName . "_entity";
            $this->CompiledClassPath  = \jj\Autoload::GetClassPath($this->CompiledClassName, false);
        }

        if ( ! $this->SkipSql)
        {
            $provider           = $this->_compiler->GetProvider();
            $this->TableExists  = $provider->HasTable($this->FullTableName);
        }

        foreach ($this->Properties as $prop)
        {
            $prop->Prepare();
        }

        foreach ($this->Indexes as $ind)
        {
            $ind->Prepare();
        }

        $this->IsPrepared = true;
    }

    private function CheckWritable($classPath)
    {
        $dir = dirname($classPath);

        if ( ! file_exists($dir))
        {
            if ( ! @mkdir($dir, 0777, true))
            {
                throw new \jj\Exception("Error: $dir could not be created.");
            }
        }

        \jj\Assert::IsWritable($dir, "dir");

        if (file_exists($classPath))
        {
            \jj\Assert::IsWritable($classPath, "classPath");
        }
    }

    /**
     * @return Compiler
     */
    public function GetCompiler()
    {
        return $this->_compiler;
    }

    public function GetSql()
    {
        $sql = array();

        if ( ! $this->SkipSql)
        {
            $provider  = $this->_compiler->GetProvider();
            $extraSql  = array();

            if ( ! $this->TableExists)
            {
                $columns = array();

                foreach ($this->Properties as $prop)
                {
                    $columns   = array_merge($columns, $prop->GetNewColumns());
                    $extraSql  = array_merge($extraSql, $prop->GetObjectSetSql());
                }

                $sql[] = $provider->GetCreateTableSql($this->FullTableName, $columns);
            }
            else
            {
                foreach ($this->Properties as $prop)
                {
                    $columns = $prop->GetChangedColumns();

                    foreach ($columns as $column)
                    {
                        $sql[] = $provider->GetAlterColumnSql($this->FullTableName, $column);
                    }

                    $columns = $prop->GetNewColumns();

                    foreach ($columns as $column)
                    {
                        $sql[] = $provider->GetCreateColumnSql($this->FullTableName, $column);
                    }

                    $extraSql = array_merge($extraSql, $prop->GetObjectSetSql());
                }
            }

            foreach ($this->Indexes as $ind)
            {
                if ($ind->IndexExists && ! $ind->IndexIsCurrent)
                {
                    $sql[] = $provider->GetDropIndexSql($this->FullTableName, $ind->IndexName);
                }

                if ( ! $ind->IndexExists || ! $ind->IndexIsCurrent)
                {
                    $sql[] = $provider->GetCreateIndexSql($this->FullTableName, $ind->GetIndexInfo());
                }
            }

            $sql = array_merge($sql, $extraSql);
        }

        return $sql;
    }
}

?>