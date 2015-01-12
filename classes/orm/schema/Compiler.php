<?php

/**
 * Compiles ORM schemas to PHP and/or SQL.
 *
 * @package juju_core
 * @author Luke Arms <luke@arms.to>
 * @copyright Copyright (c) 2012-2014 Luke Arms
 */
class jj_orm_schema_Compiler
{
    public $SchemaName;

    public $SchemaNamespace;

    public $BaseClass = "jj_orm_BaseObject";

    public $TablePrefix;

    public $Classes = array();

    /**
     * Returns a new schema compiler instance.
     *
     * @param string $schemaFile Full path to schema file.
     */
    public function __construct($schemaFile)
    {
        jj_Assert::FileExists($schemaFile, "schemaFile");
        $schema = file_get_contents($schemaFile);

        if ($schema === false)
        {
            throw new jj_Exception("Error: unable to read $schemaFile.");
        }

        $json = json_decode($schema, true);

        if (json_last_error() != JSON_ERROR_NONE)
        {
            throw new jj_Exception("Error: invalid JSON in $schemaFile.");
        }

        if ( ! isset($json["name"]) || ! is_string($json["name"]) || empty($json["name"]))
        {
            throw new jj_Exception("Error: invalid value for schema.name in $schemaFile.");
        }

        if ( ! isset($json["namespace"]) || ! is_string($json["namespace"]) || empty($json["namespace"]))
        {
            throw new jj_Exception("Error: invalid value for schema.namespace in $schemaFile.");
        }

        if ( ! isset($json["tables"]) || ! is_array($json["tables"]) || empty($json["tables"]))
        {
            throw new jj_Exception("Error: invalid value for schema.tables in $schemaFile.");
        }

        jj_Assert::IsValidIdentifier($json["name"], "schema.name in $schemaFile", array("."));
        jj_Assert::IsValidIdentifier($json["namespace"], "schema.namespace in $schemaFile", array("."));
        $this->SchemaName       = $json["name"];
        $this->SchemaNamespace  = $json["namespace"];

        if (isset($json["baseClass"]))
        {
            jj_Assert::IsValidIdentifier($json["baseClass"], "schema.baseClass in $schemaFile");
            $this->BaseClass = $json["baseClass"];
        }

        if (isset($json["tablePrefix"]))
        {
            jj_Assert::IsString($json["tablePrefix"], "schema.tablePrefix in $schemaFile");

            if ( ! empty($json["tablePrefix"]))
            {
                jj_Assert::IsValidIdentifier($json["tablePrefix"], "schema.tablePrefix in $schemaFile");
            }

            $this->TablePrefix = $json["tablePrefix"];
        }

        $i = 0;

        foreach ($json["tables"] as $table)
        {
            if ( ! isset($table["name"]) || ! is_string($table["name"]) || empty($table["name"]))
            {
                throw new jj_Exception("Error: invalid value for schema.tables[$i].name in $schemaFile.");
            }

            if ( ! isset($table["columns"]) || ! is_array($table["columns"]) || empty($table["columns"]))
            {
                throw new jj_Exception("Error: invalid value for schema.tables[$i].columns in $schemaFile.");
            }

            jj_Assert::IsValidIdentifier($table["name"], "schema.tables[$i].name in $schemaFile");
            $class = new jj_orm_schema_CompilerClass($this, $table["name"]);

            if (isset($table["skipPhp"]))
            {
                jj_Assert::IsBoolean($table["skipPhp"], "schema.tables[$i].skipPhp in $schemaFile");
                $class->SkipPhp = $table["skipPhp"];
            }

            if (isset($table["skipSql"]))
            {
                jj_Assert::IsBoolean($table["skipSql"], "schema.tables[$i].skipSql in $schemaFile");
                $class->SkipSql = $table["skipSql"];
            }

            if (isset($table["phpName"]))
            {
                jj_Assert::IsValidIdentifier($table["phpName"], "schema.tables[$i].phpName in $schemaFile");
                $class->ClassName = $table["phpName"];
            }

            if (isset($table["namespace"]))
            {
                jj_Assert::IsValidIdentifier($table["namespace"], "schema.tables[$i].namespace in $schemaFile", array("."));
                $class->ClassNamespace = $table["namespace"];
            }

            if (isset($table["baseClass"]))
            {
                jj_Assert::IsValidIdentifier($table["baseClass"], "schema.tables[$i].baseClass in $schemaFile");
                $class->BaseClass = $table["baseClass"];
            }

            if (isset($table["readOnly"]))
            {
                jj_Assert::IsBoolean($table["readOnly"], "schema.tables[$i].readOnly in $schemaFile");
                $class->ReadOnly = $table["readOnly"];
            }

            $j = 0;

            foreach ($table["columns"] as $column)
            {
                if ( ! isset($column["name"]) || ! is_string($column["name"]) || empty($column["name"]))
                {
                    throw new jj_Exception("Error: invalid value for schema.tables[$i].columns[$j].name in $schemaFile.");
                }

                if ( ! isset($column["type"]) || ! is_string($column["type"]) || empty($column["type"]))
                {
                    throw new jj_Exception("Error: invalid value for schema.tables[$i].columns[$j].type in $schemaFile.");
                }

                jj_Assert::IsValidIdentifier($column["name"], "schema.tables[$i].columns[$j].name in $schemaFile");
                $prop          = new jj_orm_schema_CompilerProperty($class, $column["name"]);
                $noPrimaryKey  = false;

                switch ($column["type"])
                {
                    case "varchar":
                    case "nvarchar":

                        if ( ! isset($column["size"]) || ! is_integer($column["size"]) || $column["size"] < 1)
                        {
                            throw new jj_Exception("Error: invalid value for schema.tables[$i].columns[$j].size in $schemaFile.");
                        }

                        $prop->Size = $column["size"];

                        if (isset($column["defaultValue"]))
                        {
                            jj_Assert::IsString($column["defaultValue"], "schema.tables[$i].columns[$j].defaultValue in $schemaFile");
                            $prop->DefaultValue = $column["defaultValue"];
                        }

                        break;

                    case "text":
                    case "ntext":
                    case "blob":

                        $noPrimaryKey = true;

                        break;

                    case "int":
                    case "bigint":

                        if (isset($column["defaultValue"]))
                        {
                            jj_Assert::IsNumeric($column["defaultValue"], "schema.tables[$i].columns[$j].defaultValue in $schemaFile");
                            jj_Assert::IsInteger($column["defaultValue"] + 0, "schema.tables[$i].columns[$j].defaultValue in $schemaFile");
                            $prop->DefaultValue = $column["defaultValue"] + 0;
                        }

                        if (isset($column["autoIncrement"]))
                        {
                            jj_Assert::IsBoolean($column["autoIncrement"], "schema.tables[$i].columns[$j].autoIncrement in $schemaFile");
                            $prop->AutoIncrement = $column["autoIncrement"];
                        }

                        break;

                    case "decimal":
                    case "float":
                    case "double":

                        if ( ! isset($column["size"]) || ! is_integer($column["size"]) || $column["size"] < 1)
                        {
                            throw new jj_Exception("Error: invalid value for schema.tables[$i].columns[$j].size in $schemaFile.");
                        }

                        if ( ! isset($column["scale"]) || ! is_integer($column["scale"]) || $column["scale"] < 0)
                        {
                            throw new jj_Exception("Error: invalid value for schema.tables[$i].columns[$j].scale in $schemaFile.");
                        }

                        $prop->Size   = $column["size"];
                        $prop->Scale  = $column["scale"];

                        if (isset($column["defaultValue"]))
                        {
                            jj_Assert::IsNumeric($column["defaultValue"], "schema.tables[$i].columns[$j].defaultValue in $schemaFile");
                            $prop->DefaultValue = $column["defaultValue"] + 0;
                        }

                        $noPrimaryKey = true;

                        break;

                    case "datetime":

                        if (isset($column["defaultValue"]))
                        {
                            jj_Assert::IsDateString($column["defaultValue"], "schema.tables[$i].columns[$j].defaultValue in $schemaFile");
                            $prop->DefaultValue = $column["defaultValue"];
                        }

                        $noPrimaryKey = true;

                        break;

                    case "enum":

                        if ( ! isset($column["valueSet"]) || ! is_string($column["valueSet"]) || empty($column["valueSet"]))
                        {
                            throw new jj_Exception("Error: invalid value for schema.tables[$i].columns[$j].valueSet in $schemaFile.");
                        }

                        $prop->ValueSet = explode(",", $column["valueSet"]);

                        if (isset($column["defaultValue"]))
                        {
                            jj_Assert::IsString($column["defaultValue"], "schema.tables[$i].columns[$j].defaultValue in $schemaFile");
                            jj_Assert::IsInArray($column["defaultValue"], $prop->ValueSet, "schema.tables[$i].columns[$j].defaultValue in $schemaFile");
                            $prop->DefaultValue = $column["defaultValue"];
                        }

                        $noPrimaryKey = true;

                        break;

                    case "boolean":

                        if (isset($column["defaultValue"]))
                        {
                            jj_Assert::IsBoolean($column["defaultValue"], "schema.tables[$i].columns[$j].defaultValue in $schemaFile");
                            $prop->DefaultValue = $column["defaultValue"];
                        }

                        $noPrimaryKey = true;

                        break;

                    case "objectSet":

                        if (isset($column["objectStorageTable"]))
                        {
                            jj_Assert::IsValidIdentifier($column["objectStorageTable"], "schema.tables[$i].columns[$j].objectStorageTable in $schemaFile");
                            $prop->ObjectStorageTable = $column["objectStorageTable"];
                        }

                        $noPrimaryKey = true;

                    case "object":

                        if ( ! isset($column["objectType"]) || ! is_string($column["objectType"]) || empty($column["objectType"]))
                        {
                            throw new jj_Exception("Error: invalid value for schema.tables[$i].columns[$j].objectType in $schemaFile.");
                        }

                        jj_Assert::IsValidIdentifier($column["objectType"], "schema.tables[$i].columns[$j].objectType in $schemaFile");
                        $prop->ObjectTypeName = $column["objectType"];

                        break;

                    default:

                        throw new jj_Exception("Error: invalid value for schema.tables[$i].columns[$j].type in $schemaFile.");
                }

                $prop->DataType = $column["type"];

                if (isset($column["phpName"]))
                {
                    jj_Assert::IsValidIdentifier($column["phpName"], "schema.tables[$i].columns[$j].phpName in $schemaFile");
                    $prop->PropertyName = $column["phpName"];
                }

                if ( ! $noPrimaryKey && isset($column["primaryKey"]))
                {
                    jj_Assert::IsBoolean($column["primaryKey"], "schema.tables[$i].columns[$j].primaryKey in $schemaFile");
                    $class->PrimaryKey[] = $prop;
                }

                if (isset($column["required"]))
                {
                    jj_Assert::IsBoolean($column["required"], "schema.tables[$i].columns[$j].required in $schemaFile");
                    $prop->Required = $column["required"];
                }

                if (isset($column["lazyLoad"]))
                {
                    jj_Assert::IsBoolean($column["lazyLoad"], "schema.tables[$i].columns[$j].lazyLoad in $schemaFile");
                    $prop->LazyLoad = $column["lazyLoad"];
                }

                $class->Properties[$prop->FieldName] = $prop;
                $j++;
            }

            $this->Classes[$class->TableName] = $class;
            $i++;
        }

        // resolve any references between classes
        foreach ($this->Classes as $class)
        {
            foreach ($class->Properties as $prop)
            {
                if (isset($prop->ObjectTypeName))
                {
                    if ( ! isset($this->Classes[$prop->ObjectTypeName]))
                    {
                        throw new jj_Exception("Error: invalid value for schema.tables[{$class->TableName}].columns[{$prop->FieldName}].objectType in $schemaFile.");
                    }

                    $prop->ObjectType = $this->Classes[$prop->ObjectTypeName];
                }
            }
        }

        // give each class an opportunity to prepare itself for output
        foreach ($this->Classes as $class)
        {
            $class->Prepare();
        }
    }
}

// PRETTY_NESTED_ARRAYS,0

?>