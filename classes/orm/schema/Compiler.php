<?php

namespace jj\orm\schema;

/**
 * Compiles ORM schemas to PHP and/or SQL.
 *
 * @package juju_core
 * @author Luke Arms <luke@arms.to>
 * @copyright Copyright (c) 2012-2015 Luke Arms
 */
class Compiler
{
    public $SchemaName;

    public $SchemaNamespace;

    public $BaseClass = "jj\orm\BaseObject";

    public $TablePrefix = "";

    public $Classes = array();

    /**
     * @var \jj\schema\BaseProvider
     */
    private $_provider;

    private static $_schemas = array();

    /**
     * Returns a new schema compiler instance.
     *
     * @param string $schemaFile Full path to schema file.
     * @param \jj\data\Connection $conn Connection to schema's target database.
     */
    public function __construct($schemaFile, \jj\data\Connection $conn)
    {
        \jj\Assert::FileExists($schemaFile, "schemaFile");
        \jj\Assert::IsNotNull($conn, "conn");
        $this->_provider = \jj\schema\BaseProvider::ByConnection($conn);

        // load schema file and prepare
        $schema = file_get_contents($schemaFile);

        if ($schema === false)
        {
            throw new \jj\Exception("Error: unable to read $schemaFile.");
        }

        if (strtolower(pathinfo($schemaFile, PATHINFO_EXTENSION)) == "json")
        {
            $json = json_decode($schema, true);

            if (json_last_error() != JSON_ERROR_NONE)
            {
                throw new \jj\Exception("Error: invalid JSON in $schemaFile.");
            }
        }
        else
        {
            $json = yaml_parse($schema);

            if ($json === false)
            {
                throw new \jj\Exception("Error: invalid YAML in $schemaFile.");
            }
        }

        if ( ! isset($json["name"]) || ! is_string($json["name"]) || empty($json["name"]))
        {
            throw new \jj\Exception("Error: invalid value for schema.name in $schemaFile.");
        }

        if ( ! isset($json["namespace"]) || ! is_string($json["namespace"]) || empty($json["namespace"]))
        {
            throw new \jj\Exception("Error: invalid value for schema.namespace in $schemaFile.");
        }

        if ( ! isset($json["tables"]) || ! is_array($json["tables"]) || empty($json["tables"]))
        {
            throw new \jj\Exception("Error: invalid value for schema.tables in $schemaFile.");
        }

        \jj\Assert::IsValidIdentifier($json["name"], "schema.name in $schemaFile", array("."));
        \jj\Assert::IsValidIdentifier($json["namespace"], "schema.namespace in $schemaFile", array("\\"));
        $this->SchemaName       = $json["name"];
        $this->SchemaNamespace  = $json["namespace"];

        if (isset($json["baseClass"]))
        {
            \jj\Assert::IsValidIdentifier($json["baseClass"], "schema.baseClass in $schemaFile", array("\\"));
            $this->BaseClass = $json["baseClass"];
        }

        if (isset($json["tablePrefix"]))
        {
            \jj\Assert::IsString($json["tablePrefix"], "schema.tablePrefix in $schemaFile");

            if ( ! empty($json["tablePrefix"]))
            {
                \jj\Assert::IsValidIdentifier($json["tablePrefix"], "schema.tablePrefix in $schemaFile");
            }

            $this->TablePrefix = $json["tablePrefix"];
        }

        $i = 0;

        foreach ($json["tables"] as $table)
        {
            if ( ! isset($table["name"]) || ! is_string($table["name"]) || empty($table["name"]))
            {
                throw new \jj\Exception("Error: invalid value for schema.tables[$i].name in $schemaFile.");
            }

            if ( ! isset($table["columns"]) || ! is_array($table["columns"]) || empty($table["columns"]))
            {
                throw new \jj\Exception("Error: invalid value for schema.tables[$i].columns in $schemaFile.");
            }

            \jj\Assert::IsValidIdentifier($table["name"], "schema.tables[$i].name in $schemaFile");
            $class = new CompilerClass($this, $table["name"]);

            if (isset($table["skipPhp"]))
            {
                \jj\Assert::IsBoolean($table["skipPhp"], "schema.tables[$i].skipPhp in $schemaFile");
                $class->SkipPhp = $table["skipPhp"];
            }

            if (isset($table["skipSql"]))
            {
                \jj\Assert::IsBoolean($table["skipSql"], "schema.tables[$i].skipSql in $schemaFile");
                $class->SkipSql = $table["skipSql"];
            }

            if (isset($table["phpName"]))
            {
                \jj\Assert::IsValidIdentifier($table["phpName"], "schema.tables[$i].phpName in $schemaFile");
                $class->ClassName = $table["phpName"];
            }

            if (isset($table["namespace"]))
            {
                \jj\Assert::IsValidIdentifier($table["namespace"], "schema.tables[$i].namespace in $schemaFile", array("\\"));
                $class->ClassNamespace = $table["namespace"];
            }

            if (isset($table["baseClass"]))
            {
                \jj\Assert::IsValidIdentifier($table["baseClass"], "schema.tables[$i].baseClass in $schemaFile", array("\\"));
                $class->BaseClass = $table["baseClass"];
            }

            if (isset($table["readOnly"]))
            {
                \jj\Assert::IsBoolean($table["readOnly"], "schema.tables[$i].readOnly in $schemaFile");
                $class->ReadOnly = $table["readOnly"];
            }

            $j = 0;

            foreach ($table["columns"] as $column)
            {
                if ( ! isset($column["name"]) || ! is_string($column["name"]) || empty($column["name"]))
                {
                    throw new \jj\Exception("Error: invalid value for schema.tables[$i].columns[$j].name in $schemaFile.");
                }

                if ( ! isset($column["type"]) || ! is_string($column["type"]) || empty($column["type"]))
                {
                    throw new \jj\Exception("Error: invalid value for schema.tables[$i].columns[$j].type in $schemaFile.");
                }

                \jj\Assert::IsValidIdentifier($column["name"], "schema.tables[$i].columns[$j].name in $schemaFile");
                $prop          = new CompilerProperty($class, $column["name"]);
                $noPrimaryKey  = false;

                switch ($column["type"])
                {
                    case "varchar":
                    case "nvarchar":

                        if ( ! isset($column["size"]) || ! is_integer($column["size"]) || $column["size"] < 1)
                        {
                            throw new \jj\Exception("Error: invalid value for schema.tables[$i].columns[$j].size in $schemaFile.");
                        }

                        $prop->Size = $column["size"];

                        if (isset($column["defaultValue"]))
                        {
                            \jj\Assert::IsString($column["defaultValue"], "schema.tables[$i].columns[$j].defaultValue in $schemaFile");
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
                            \jj\Assert::IsNumeric($column["defaultValue"], "schema.tables[$i].columns[$j].defaultValue in $schemaFile");
                            \jj\Assert::IsInteger($column["defaultValue"] + 0, "schema.tables[$i].columns[$j].defaultValue in $schemaFile");
                            $prop->DefaultValue = $column["defaultValue"] + 0;
                        }

                        if (isset($column["autoIncrement"]))
                        {
                            \jj\Assert::IsBoolean($column["autoIncrement"], "schema.tables[$i].columns[$j].autoIncrement in $schemaFile");
                            $prop->AutoIncrement = $column["autoIncrement"];
                        }

                        break;

                    case "decimal":
                    case "float":
                    case "double":

                        if ( ! isset($column["size"]) || ! is_integer($column["size"]) || $column["size"] < 1)
                        {
                            throw new \jj\Exception("Error: invalid value for schema.tables[$i].columns[$j].size in $schemaFile.");
                        }

                        if ( ! isset($column["scale"]) || ! is_integer($column["scale"]) || $column["scale"] < 0)
                        {
                            throw new \jj\Exception("Error: invalid value for schema.tables[$i].columns[$j].scale in $schemaFile.");
                        }

                        $prop->Size   = $column["size"];
                        $prop->Scale  = $column["scale"];

                        if (isset($column["defaultValue"]))
                        {
                            \jj\Assert::IsNumeric($column["defaultValue"], "schema.tables[$i].columns[$j].defaultValue in $schemaFile");
                            $prop->DefaultValue = $column["defaultValue"] + 0;
                        }

                        $noPrimaryKey = true;

                        break;

                    case "datetime":

                        if (isset($column["defaultValue"]))
                        {
                            \jj\Assert::IsDateString($column["defaultValue"], "schema.tables[$i].columns[$j].defaultValue in $schemaFile");
                            $prop->DefaultValue = $column["defaultValue"];
                        }

                        $noPrimaryKey = true;

                        break;

                    case "boolean":

                        if (isset($column["defaultValue"]))
                        {
                            \jj\Assert::IsBoolean($column["defaultValue"], "schema.tables[$i].columns[$j].defaultValue in $schemaFile");
                            $prop->DefaultValue = $column["defaultValue"];
                        }

                        $noPrimaryKey = true;

                        break;

                    case "objectSet":

                        if (isset($column["objectStorageTable"]))
                        {
                            \jj\Assert::IsValidIdentifier($column["objectStorageTable"], "schema.tables[$i].columns[$j].objectStorageTable in $schemaFile");
                            $prop->ObjectStorageTable = $column["objectStorageTable"];
                        }

                        $noPrimaryKey = true;

                    case "object":

                        if ( ! isset($column["objectType"]) || ! is_string($column["objectType"]) || empty($column["objectType"]))
                        {
                            throw new \jj\Exception("Error: invalid value for schema.tables[$i].columns[$j].objectType in $schemaFile.");
                        }

                        \jj\Assert::IsValidIdentifier($column["objectType"], "schema.tables[$i].columns[$j].objectType in $schemaFile", array("."));
                        $prop->ObjectTypeName = $column["objectType"];

                        if (isset($column["objectStorageColumns"]))
                        {
                            if ( ! is_array($column["objectStorageColumns"]) || empty($column["objectStorageColumns"]))
                            {
                                throw new \jj\Exception("Error: invalid value for schema.tables[$i].columns[$j].objectStorageColumns in $schemaFile.");
                            }

                            $k = 0;

                            foreach ($column["objectStorageColumns"] as $storageColumn)
                            {
                                \jj\Assert::IsValidIdentifier($storageColumn, "schema.tables[$i].columns[$j].objectStorageColumns[$k] in $schemaFile");
                                $k++;
                            }

                            $prop->ObjectStorageColumns = $column["objectStorageColumns"];
                        }

                        break;

                    default:

                        throw new \jj\Exception("Error: invalid value for schema.tables[$i].columns[$j].type in $schemaFile.");
                }

                $prop->DataType = $column["type"];

                if (isset($column["phpName"]))
                {
                    \jj\Assert::IsValidIdentifier($column["phpName"], "schema.tables[$i].columns[$j].phpName in $schemaFile");
                    $prop->PropertyName = $column["phpName"];
                }

                if ( ! $noPrimaryKey && isset($column["primaryKey"]))
                {
                    \jj\Assert::IsBoolean($column["primaryKey"], "schema.tables[$i].columns[$j].primaryKey in $schemaFile");
                    $class->PrimaryKey[] = $prop;
                }

                if (isset($column["required"]))
                {
                    \jj\Assert::IsBoolean($column["required"], "schema.tables[$i].columns[$j].required in $schemaFile");
                    $prop->Required = $column["required"];
                }

                if (isset($column["lazyLoad"]))
                {
                    \jj\Assert::IsBoolean($column["lazyLoad"], "schema.tables[$i].columns[$j].lazyLoad in $schemaFile");
                    $prop->LazyLoad = $column["lazyLoad"];
                }

                $class->Properties[$prop->ColumnName] = $prop;
                $j++;
            }

            if (isset($table["indexes"]))
            {
                if ( ! is_array($table["indexes"]) || empty($table["indexes"]))
                {
                    throw new \jj\Exception("Error: invalid value for schema.tables[$i].indexes in $schemaFile.");
                }

                $j = 0;

                foreach ($table["indexes"] as $index)
                {
                    if ( ! isset($index["name"]) || ! is_string($index["name"]) || empty($index["name"]))
                    {
                        throw new \jj\Exception("Error: invalid value for schema.tables[$i].indexes[$j].name in $schemaFile.");
                    }

                    if ( ! isset($index["columns"]) || ! is_array($index["columns"]) || empty($index["columns"]))
                    {
                        throw new \jj\Exception("Error: invalid value for schema.tables[$i].indexes[$j].columns in $schemaFile.");
                    }

                    \jj\Assert::IsValidIdentifier($index["name"], "schema.tables[$i].indexes[$j].name in $schemaFile");
                    $ind = new CompilerIndex($class, $index["name"]);

                    foreach ($index["columns"] as $column)
                    {
                        if ( ! array_key_exists($column, $class->Properties) || in_array($class->Properties[$column]->DataType, array("objectSet")))
                        {
                            throw new \jj\Exception("Error: undefined column in schema.tables[$i].indexes[$j].columns in $schemaFile.");
                        }

                        $ind->Columns[] = $class->Properties[$column];
                    }

                    if (isset($index["unique"]))
                    {
                        \jj\Assert::IsBoolean($index["unique"], "schema.tables[$i].indexes[$j].unique in $schemaFile");
                        $ind->Unique = $index["unique"];
                    }

                    $class->Indexes[$ind->IndexName] = $ind;
                    $j++;
                }
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
                    // references to classes in previously defined schemas are accepted
                    if (($offset = strrpos($prop->ObjectTypeName, ".")) !== false)
                    {
                        $schemaName      = substr($prop->ObjectTypeName, 0, $offset);
                        $objectTypeName  = substr($prop->ObjectTypeName, $offset + 1);

                        if ( ! isset(self::$_schemas[$conn->Id][$schemaName]->Classes[$objectTypeName]))
                        {
                            throw new \jj\Exception("Error: invalid value for schema.tables[{$class->TableName}].columns[{$prop->ColumnName}].objectType in $schemaFile.");
                        }

                        $prop->ObjectType = self::$_schemas[$conn->Id][$schemaName]->Classes[$objectTypeName];
                    }
                    else
                    {
                        if ( ! isset($this->Classes[$prop->ObjectTypeName]))
                        {
                            throw new \jj\Exception("Error: invalid value for schema.tables[{$class->TableName}].columns[{$prop->ColumnName}].objectType in $schemaFile.");
                        }

                        $prop->ObjectType = $this->Classes[$prop->ObjectTypeName];
                    }
                }
            }
        }

        // give each class an opportunity to prepare itself for output
        foreach ($this->Classes as $class)
        {
            $class->Prepare();
        }

        if ( ! isset(self::$_schemas[$conn->Id]))
        {
            self::$_schemas[$conn->Id] = array();
        }

        self::$_schemas[$conn->Id][$this->SchemaName] = $this;
    }

    public function GetSql()
    {
        $sql = array();

        foreach ($this->Classes as $class)
        {
            $sql = array_merge($sql, $class->GetSql());
        }

        return $sql;
    }

    /**
     * Checks for changes to schema definition files. For each changed schema, identifies new, changed or deleted entities in the database and brings it up-to-date.
     *
     * @param boolean $forceCheck If true, schema entities will be checked even if their definition files are unchanged.
     */
    public static function CheckAllSchemas($forceCheck = false)
    {
        global $JJ_SCHEMAS;

        // if the cache folder is emptied, a full schema check will be forced
        $stateFile = \jj\Common::GetCacheFolder() . "/schema.state";

        // assume we've never checked any schemas
        $state         = array();
        $stateChanged  = false;

        if (file_exists($stateFile))
        {
            if ( ! is_writable($stateFile) && ! @unlink($stateFile))
            {
                throw new \jj\Exception("Error: $stateFile is not writable.");
            }

            if ( ! $forceCheck)
            {
                $state = yaml_parse(file_get_contents($stateFile));
            }
        }

        foreach ($JJ_SCHEMAS as $schema)
        {
            list ($schemaFile, $connId) = $schema;
            \jj\Assert::FileExists($schemaFile, "schema file");
            $schemaFile  = realpath($schemaFile);
            $modified    = filemtime($schemaFile);

            if ( ! isset($state[$schemaFile]) || $modified > $state[$schemaFile])
            {
                // if we need to check one schema, we check them all
                if ( ! $forceCheck)
                {
                    self::CheckAllSchemas(true);

                    return;
                }

                // connId could be NULL, a connection ID (int) or a connection name (string)
                $conn      = is_null($connId) ? new \jj\data\Connection() : (is_int($connId) ? new \jj\data\Connection(\jj\data\ConnectionInfo::ById($connId)) : new \jj\data\Connection(\jj\data\ConnectionInfo::ByName($connId)));
                $compiler  = new Compiler($schemaFile, $conn);
                $sql       = $compiler->GetSql();

                foreach ($sql as $query)
                {
                    $conn->ExecuteNonQuery($query);
                }

                // mark this schema as checked
                $state[$schemaFile]  = $modified;
                $stateChanged        = true;
            }
        }

        if ($stateChanged)
        {
            file_put_contents($stateFile, yaml_emit($state));
        }
    }

    /**
     * @return \jj\schema\BaseProvider
     */
    public function GetProvider()
    {
        return $this->_provider;
    }
}

// PRETTY_NESTED_ARRAYS,0

?>