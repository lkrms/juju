<?php

namespace jj\data;

/**
 * Stores database connection information.
 *
 * @package juju_core
 * @author Luke Arms <luke@arms.to>
 * @copyright Copyright (c) 2012-2015 Luke Arms
 */
class ConnectionInfo
{
    /**
     * The connection ID to use for the default connection.
     */
    const DEFAULT_ID = -1;

    /**
     * The name to use for the default connection.
     */
    const DEFAULT_NAME = "juju_core";

    /**
     * If this instance has been populated from the core_connection table, this is its connection ID. NULL otherwise.
     *
     * @var integer
     */
    public $Id = null;

    /**
     * If this instance has been populated from the core_connection table, this is its name. NULL otherwise.
     *
     * @var string
     */
    public $Name = null;

    /**
     * One of the Connection::TYPE_* values.
     *
     * @var integer
     */
    public $Type;

    /**
     * The name of the database to open, or for SQLite, the path to the database file.
     *
     * @var string
     */
    public $Database;

    /**
     * The name or IP address of the server to connect to.
     *
     * @var string
     */
    public $Host;

    /**
     * The username to present to the server when connecting.
     *
     * @var string
     */
    public $Username;

    /**
     * The password to present to the server when connecting.
     *
     * @var string
     */
    public $Password;

    /**
     * The prefix to apply to table names when connecting.
     *
     * @var string
     */
    public $Prefix;

    /**
     * Creates a new connection info instance.
     *
     * @param integer $type One of the Connection::TYPE_* values.
     * @param string $host The name or IP address of the server to connect to.
     * @param string $database The name of the database to open, or for SQLite, the path to the database file.
     * @param string $username The username to present to the server when connecting.
     * @param string $password The password to present to the server when connecting.
     * @param string $prefix The prefix to apply to table names when connecting.
     */
    public function __construct($type, $host, $database, $username, $password, $prefix)
    {
        \jj\Assert::IsInteger($type, "type");

        switch ($type)
        {
            case Connection::TYPE_MYSQL:
            case Connection::TYPE_MSSQL:
            case Connection::TYPE_SQLITE:
            case Connection::TYPE_PGSQL:

                break;

            default:

                throw new \jj\Exception("Error: connection type unknown.", \jj\Exception::CODE_ASSERTION_FAILURE);
        }

        $this->Type      = $type;
        $this->Host      = $host;
        $this->Database  = $database;
        $this->Username  = $username;
        $this->Password  = $password;
        $this->Prefix    = $prefix;
    }

    /**
     * Returns a DSN (Data Source Name) for the connection.
     *
     * @return string
     */
    public function GetDSN()
    {
        switch ($this->Type)
        {
            case Connection::TYPE_MYSQL:

                $username  = rawurlencode($this->Username);
                $password  = rawurlencode($this->Password);

                return "mysqli://{$username}:{$password}@{$this->Host}/{$this->Database}";

            case Connection::TYPE_MSSQL:

                $username  = rawurlencode($this->Username);
                $password  = rawurlencode($this->Password);

                return "mssql://{$username}:{$password}@{$this->Host}/{$this->Database}";

            case Connection::TYPE_SQLITE:

                $path = rawurlencode($this->Database);

                return "sqlite://{$path}/";

            case Connection::TYPE_PGSQL:

                $username  = rawurlencode($this->Username);
                $password  = rawurlencode($this->Password);

                return "postgres://{$username}:{$password}@{$this->Host}/{$this->Database}";

            default:

                throw new \jj\Exception("Error: connection type unknown.", \jj\Exception::CODE_ASSERTION_FAILURE);
        }
    }

    /**
     * Creates a new connection info instance from Juju's configured database settings.
     *
     * @return ConnectionInfo
     */
    public static function GetDefault()
    {
        if ( ! (defined("JJ_DB_TYPE") && defined("JJ_DB_HOST") && defined("JJ_DB_DATABASE") && defined("JJ_DB_USERNAME") && defined("JJ_DB_PASSWORD") && defined("JJ_DB_PREFIX")))
        {
            throw new \jj\Exception("Error: database settings have not been configured correctly.", \jj\Exception::CODE_CONFIG_ERROR);
        }

        switch (JJ_DB_TYPE)
        {
            case "mysql":

                $type = Connection::TYPE_MYSQL;

                break;

            case "mssql":

                $type = Connection::TYPE_MSSQL;

                break;

            case "sqlite":

                $type = Connection::TYPE_SQLITE;

                break;

            case "pgsql":

                $type = Connection::TYPE_PGSQL;

                break;

            default:

                throw new \jj\Exception("Error: database connection type " . JJ_DB_TYPE . " not recognised.", \jj\Exception::CODE_CONFIG_ERROR);
        }

        return new ConnectionInfo($type, JJ_DB_HOST, JJ_DB_DATABASE, JJ_DB_USERNAME, JJ_DB_PASSWORD, JJ_DB_PREFIX);
    }

    /**
     * Creates a new connection info instance from settings stored in the core_connection table.
     *
     * @param integer $id The connection ID to match.
     * @return ConnectionInfo
     */
    public static function ById($id)
    {
        \jj\Assert::IsInteger($id, "id");

        if ($id == ConnectionInfo::DEFAULT_ID)
        {
            return self::GetDefault();
        }

        $conn  = new Connection();
        $dr    = $conn->ExecuteReader("select db_type, db_name, db_host, db_username, db_password, db_prefix, name from " . _getTable("core_connection") . " where id = @id", array("id" => $id));

        if ( ! $dr->Read())
        {
            $dr->Close();
            throw new \jj\Exception("Error: no connection record with ID $id.", \jj\Exception::CODE_GENERAL_ERROR);
        }

        $r = $dr->GetValues();
        $dr->Close();
        $info        = new ConnectionInfo($r[0], $r[2], $r[1], $r[3], $r[4], $r[5]);
        $info->Id    = $id;
        $info->Name  = $r[6];

        return $info;
    }

    /**
     * Creates a new connection info instance from settings stored in the core_connection table.
     *
     * @param string $name The connection name to match.
     * @return ConnectionInfo
     */
    public static function ByName($name)
    {
        \jj\Assert::IsString($name, "name");

        if ( ! strcasecmp($name, ConnectionInfo::DEFAULT_NAME))
        {
            return self::GetDefault();
        }

        $conn  = new Connection();
        $dr    = $conn->ExecuteReader("select db_type, db_name, db_host, db_username, db_password, db_prefix, id, name from " . _getTable("core_connection") . " where name = @name", array("name" => $name));

        if ( ! $dr->Read())
        {
            $dr->Close();
            throw new \jj\Exception("Error: no connection record with name $name.", \jj\Exception::CODE_GENERAL_ERROR);
        }

        $r = $dr->GetValues();
        $dr->Close();
        $info        = new ConnectionInfo($r[0], $r[2], $r[1], $r[3], $r[4], $r[5]);
        $info->Id    = $r[6];
        $info->Name  = $r[7];

        return $info;
    }
}

// PRETTY_NESTED_ARRAYS,0

?>