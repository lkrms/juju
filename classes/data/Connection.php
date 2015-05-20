<?php

namespace jj\data;

/**
 * Represents a connection to a database.
 *
 * @package juju_core
 * @author Luke Arms <luke@arms.to>
 * @copyright Copyright (c) 2012-2015 Luke Arms
 */
class Connection
{
    /**
     * MySQL.
     */
    const TYPE_MYSQL = 0;

    /**
     * Microsoft SQL Server.
     */
    const TYPE_MSSQL = 1;

    /**
     * SQLite.
     */
    const TYPE_SQLITE = 2;

    /**
     * PostgreSQL.
     */
    const TYPE_PGSQL = 3;

    /**
     * If the connection represents an entry in the _connections table, this is its connection ID. NULL otherwise.
     *
     * @var integer
     */
    public $Id;

    /**
     * If the connection represents an entry in the _connections table, this is its name. NULL otherwise.
     *
     * @var string
     */
    public $Name;

    /**
     * One of the TYPE_* values.
     *
     * @var integer
     */
    public $Type;

    /**
     * Table name prefix for the connection.
     *
     * @var string
     */
    public $Prefix;

    /**
     *
     * @var \ADOConnection
     */
    private $_conn;

    /**
     *
     * @var ConnectionInfo
     */
    private $_info;

    /**
     *
     * @var Transaction
     */
    private $_trans;

    /**
     * Returns a new connection instance.
     *
     * @param ConnectionInfo $info If null, ConnectionInfo::GetDefault will be used to supply connection info.
     */
    public function __construct(ConnectionInfo $info = null)
    {
        if (is_null($info))
        {
            $info = ConnectionInfo::GetDefault();
        }

        $this->_conn = ADONewConnection($info->GetDSN());

        if ( ! $this->_conn)
        {
            throw new \jj\Exception("Error: could not connect to the database.", \jj\Exception::CODE_GENERAL_ERROR);
        }

        $this->_info   = $info;
        $this->Id      = $info->Id;
        $this->Name    = $info->Name;
        $this->Type    = $info->Type;
        $this->Prefix  = $info->Prefix;
    }

    /**
     * Internal use only.
     *
     * @return \ADOConnection
     */
    public function _getConn()
    {
        return $this->_conn;
    }

    /**
     * Returns the ConnectionInfo object associated with this connection.
     *
     * @return ConnectionInfo
     */
    public function GetInfo()
    {
        return $this->_info;
    }

    /**
     * Executes a query against the database and returns the number of rows affected.
     *
     * @param string $sql The query to execute.
     * @param array $params Parameters for the query, keyed on parameter name.
     * @param Transaction $trans The transaction to participate in (optional).
     * @return integer The number of rows affected.
     */
    public function ExecuteNonQuery($sql, $params = null, Transaction $trans = null)
    {
        $cmd = new Command($this, $sql, $params);

        if ($trans)
        {
            $cmd->Transaction = $trans;
        }

        return $cmd->ExecuteNonQuery();
    }

    /**
     * Executes a query against the database and returns the value in the first column of the first row in the result set.
     *
     * @param string $sql The query to execute.
     * @param array $params Parameters for the query, keyed on parameter name.
     * @param Transaction $trans The transaction to participate in (optional).
     * @return mixed The value in the first column of the first row in the result set.
     */
    public function ExecuteScalar($sql, $params = null, Transaction $trans = null)
    {
        $cmd = new Command($this, $sql, $params);

        if ($trans)
        {
            $cmd->Transaction = $trans;
        }

        return $cmd->ExecuteScalar();
    }

    /**
     * Executes a query against the database and returns a Reader object to handle the result set.
     *
     * @param string $sql The query to execute.
     * @param array $params Parameters for the query, keyed on parameter name.
     * @param Transaction $trans The transaction to participate in (optional).
     * @return Reader A new Reader object.
     */
    public function ExecuteReader($sql, $params = null, Transaction $trans = null)
    {
        $cmd = new Command($this, $sql, $params);

        if ($trans)
        {
            $cmd->Transaction = $trans;
        }

        return $cmd->ExecuteReader();
    }

    /**
     * Builds an INSERT query to execute against the database.
     *
     * @param string $table The name of the table to which data will be added.
     * @param array $vals Values to insert, keyed on field name.
     * @return Command A new Command object, ready to execute.
     */
    public function BuildInsertCommand($table, array $vals)
    {
        $fields  = array();
        $values  = array();
        $params  = array();

        foreach ($vals as $field => $value)
        {
            $fields[]        = $field;
            $values[]        = "@" . $field;
            $params[$field]  = $value;
        }

        $fields  = implode(", ", $fields);
        $values  = implode(", ", $values);

        return new Command($this, "insert into $table ($fields) values ($values)", $params);
    }

    /**
     * Prepares the given timestamp for binding to a SQL statement.
     *
     * UTC times are enforced.
     *
     * @param integer $timestamp The Unix timestamp to convert.
     * @return string The converted timestamp, ready for binding as a datetime value.
     */
    public function FormatDateTime($timestamp)
    {
        // we want UTC dates to be stored in the database
        $tz = date_default_timezone_get();
        date_default_timezone_set("UTC");
        $datetime = $this->_conn->BindTimeStamp($timestamp);
        date_default_timezone_set($tz);

        return $datetime;
    }

    /**
     * Formats the given field name for use as a parameter in a query against the database.
     *
     * @param string $field Field name.
     * @return string Parameter identifier.
     */
    public function GetParameter($field)
    {
        return "@" . $field;
    }

    /**
     * Starts a transaction on the database.
     *
     * @param integer $isolationLevel One of the Transaction::ISOLATION_LEVEL_* values.
     * @return Transaction A new Transaction object.
     */
    public function BeginTransaction($isolationLevel = Transaction::ISOLATION_LEVEL_REPEATABLE_READ)
    {
        if ( ! $this->HasTransaction())
        {
            $trans         = new Transaction($this, $isolationLevel);
            $this->_trans  = $trans;

            return $trans;
        }

        throw new \jj\Exception("Error: connection already has an associated transaction.", \jj\Exception::CODE_GENERAL_ERROR);
    }

    /**
     * Returns TRUE if the connection is managing a pending transaction.
     *
     * @return boolean
     */
    public function HasTransaction()
    {
        return $this->_trans && ! $this->_trans->IsComplete ? true : false;
    }
}

?>