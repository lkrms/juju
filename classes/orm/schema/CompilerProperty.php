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
    public $FieldName;

    public $PropertyName;

    public $DataType;

    public $DefaultValue = null;

    public $Required = false;

    public $Size;

    public $Scale;

    public $ValueSet;

    public $AutoIncrement = false;

    public $LazyLoad = false;

    public $ObjectTypeName;

    /**
     * @var jj_orm_schema_CompilerClass
     */
    public $ObjectType;

    public $ObjectStorageTable;

    /**
     * @var jj_orm_schema_CompilerClass
     */
    private $_class;

    /**
     * @var jj_orm_schema_Compiler
     */
    private $_compiler;

    public function __construct(jj_orm_schema_CompilerClass $class, $fieldName)
    {
        $this->_class        = $class;
        $this->_compiler     = $class->GetCompiler();
        $this->FieldName     = $fieldName;
        $this->PropertyName  = jj_Common::GetCamelCase($fieldName);
    }

    public function Prepare()
    {
    }
}

?>