<?php

/**
 * Stores database connection information.
 *
 * @package juju_core
 * @author Luke Arms <luke@arms.to>
 * @copyright Copyright (c) 2012-2015 Luke Arms
 */
class jj_data_ConnectionInfo
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
     * If this instance has been populated from the _connections table, this is its connection ID. NULL otherwise.
     *
     * @var integer
     */
    public $Id = null;

    /**
     * If this instance has been populated from the _connections table, this is its name. NULL otherwise.
     *
     * @var string
     */
    public $Name = null;

    /**
     * One of the jj_data_Connection::TYPE_* values.
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
     * @param integer $type One of the jj_data_Connection::TYPE_* values.
     * @param string $host The name or IP address of the server to connect to.
     * @param string $database The name of the database to open, or for SQLite, the path to the database file.
     * @param string $username The username to present to the server when connecting.
     * @param string $password The password to present to the server when connecting.
     * @param string $prefix The prefix to apply to table names when connecting.
     */
    public function __construct($type, $host, $database, $username, $password, $prefix)
    {
        jj_Assert::IsInteger($type, "type");

        switch ($type)
        {
            case jj_data_Connection::TYPE_MYSQL:
            case jj_data_Connection::TYPE_MSSQL:
            case jj_data_Connection::TYPE_SQLITE:
            case jj_data_Connection::TYPE_PGSQL:

                break;

            default:

                throw new jj_Exception("Error: connection type unknown.", jj_Exception::CODE_ASSERTION_FAILURE);
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
            case jj_data_Connection::TYPE_MYSQL:

                $username  = rawurlencode($this->Username);
                $password  = rawurlencode($this->Password);

                return "mysqli://{$username}:{$password}@{$this->Host}/{$this->Database}";

            case jj_data_Connection::TYPE_MSSQL:

                $username  = rawurlencode($this->Username);
                $password  = rawurlencode($this->Password);

                return "mssql://{$username}:{$password}@{$this->Host}/{$this->Database}";

            case jj_data_Connection::TYPE_SQLITE:

                $path = rawurlencode($this->Database);

                return "sqlite://{$path}/";

            case jj_data_Connection::TYPE_PGSQL:

                $username  = rawurlencode($this->Username);
                $password  = rawurlencode($this->Password);

                return "postgres://{$username}:{$password}@{$this->Host}/{$this->Database}";

            default:

                throw new jj_Exception("Error: connection type unknown.", jj_Exception::CODE_ASSERTION_FAILURE);
        }
    }

    /**
     * Creates a new connection info instance from Juju's configured database settings.
     *
     * @return jj_data_ConnectionInfo
     */
    public static function GetDefault()
    {
        if ( ! (defined("JJ_DB_TYPE") && defined("JJ_DB_HOST") && defined("JJ_DB_DATABASE") && defined("JJ_DB_USERNAME") && defined("JJ_DB_PASSWORD") && defined("JJ_DB_PREFIX")))
        {
            throw new jj_Exception("Error: database settings have not been configured correctly.", jj_Exception::CODE_CONFIG_ERROR);
        }

        switch (JJ_DB_TYPE)
        {
            case "mysql":

                $type = jj_data_Connection::TYPE_MYSQL;

                break;

            case "mssql":

                $type = jj_data_Connection::TYPE_MSSQL;

                break;

            case "sqlite":

                $type = jj_data_Connection::TYPE_SQLITE;

                break;

            case "pgsql":

                $type = jj_data_Connection::TYPE_PGSQL;

                break;

            default:

                throw new jj_Exception("Error: database connection type " . JJ_DB_TYPE . " not recognised.", jj_Exception::CODE_CONFIG_ERROR);
        }

        return new jj_data_ConnectionInfo($type, JJ_DB_HOST, JJ_DB_DATABASE, JJ_DB_USERNAME, JJ_DB_PASSWORD, JJ_DB_PREFIX);
    }

    /**
     * Creates a new connection info instance from settings stored in the _connections table.
     *
     * @param integer $id The connection ID to match.
     * @return jj_data_ConnectionInfo
     */
    public static function ById($id)
    {
        jj_Assert::IsInteger($id, "id");

        if ($id == jj_data_ConnectionInfo::DEFAULT_ID)
        {
            return self::GetDefault();
        }

        $conn  = new jj_data_Connection();
        $dr    = $conn->ExecuteReader("select db_type, db_name, db_host, db_username, db_password, db_prefix, conn_name from " . _getTable("_connections") . " where conn_id = @conn_id", array("conn_id" => $id));

        if ( ! $dr->Read())
        {
            $dr->Close();
            throw new jj_Exception("Error: no connection record with ID $id.", jj_Exception::CODE_GENERAL_ERROR);
        }

        $r = $dr->GetValues();
        $dr->Close();
        $info        = new jj_data_ConnectionInfo($r[0], $r[2], $r[1], $r[3], $r[4], $r[5]);
        $info->Id    = $id;
        $info->Name  = $r[6];

        return $info;
    }

    /**
     * Creates a new connection info instance from settings stored in the _connections table.
     *
     * @param string $name The connection name to match.
     * @return jj_data_ConnectionInfo
     */
    public static function ByName($name)
    {
        jj_Assert::IsString($name, "name");

        if ( ! strcasecmp($name, jj_data_ConnectionInfo::DEFAULT_NAME))
        {
            return self::GetDefault();
        }

        $conn  = new jj_data_Connection();
        $dr    = $conn->ExecuteReader("select db_type, db_name, db_host, db_username, db_password, db_prefix, conn_id, conn_name from " . _getTable("_connections") . " where conn_name = @conn_name", array("conn_name" => $name));

        if ( ! $dr->Read())
        {
            $dr->Close();
            throw new jj_Exception("Error: no connection record with name $name.", jj_Exception::CODE_GENERAL_ERROR);
        }

        $r = $dr->GetValues();
        $dr->Close();
        $info        = new jj_data_ConnectionInfo($r[0], $r[2], $r[1], $r[3], $r[4], $r[5]);
        $info->Id    = $r[6];
        $info->Name  = $r[7];

        return $info;
    }
}

// PRETTY_NESTED_ARRAYS,0

?>