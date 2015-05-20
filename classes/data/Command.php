<?php

namespace jj\data;

/**
 * Represents a SQL statement to execute against a database.
 *
 * @package juju_core
 * @author Luke Arms <luke@arms.to>
 * @copyright Copyright (c) 2012-2015 Luke Arms
 */
class Command
{
    /**
     * The database connection to use.
     *
     * @var Connection
     */
    public $Connection;

    /**
     * The SQL statement to execute against the database.
     *
     * @var string
     */
    public $CommandText;

    /**
     * Data to bind the query to, keyed on parameter name.
     *
     * @var array
     */
    public $Parameters;

    /**
     * The transaction within which to execute the command.
     *
     * @var Transaction
     */
    public $Transaction;

    private $_compiledCommandText;

    private $_paramOrder;

    private $_sql;

    private $_preparedParams;

    private $_params;

    private $_rs;

    public function __construct(Connection $conn, $sql = null, array $params = null)
    {
        \jj\Assert::IsNotNull($conn, "conn");

        if ( ! is_null($sql))
        {
            \jj\Assert::IsString($sql, "sql");
        }

        $this->Connection   = $conn;
        $this->CommandText  = $sql;
        $this->Parameters   = is_null($params) ? array() : $params;
    }

    private function PrepareParameters()
    {
        $this->_preparedParams = array();

        foreach ($this->Parameters as $paramName => $param)
        {
            if (is_object($param) && $param instanceof IFormat)
            {
                $param = $param->DataFormat($this->Connection);
            }
            elseif (is_object($param) || is_array($param))
            {
                // unserialized objects and arrays get YAML'ified
                $param = yaml_emit($param);
            }

            $this->_preparedParams[$paramName] = $param;
        }
    }

    private function CompileSql()
    {
        if ($this->_compiledCommandText != $this->CommandText)
        {
            $sql                = $this->CommandText;
            $matches            = array();
            $this->_paramOrder  = array();

            // find all parameter references in the query
            preg_match_all("/@([_a-zA-Z0-9]+)/", $sql, $matches, PREG_SET_ORDER);

            for ($i = 0; $i < count($matches); $i++)
            {
                $this->_paramOrder[]  = $matches[$i][1];
                $sql                  = str_replace($matches[$i][0], "?", $sql);
            }

            $this->_sql                  = $sql;
            $this->_compiledCommandText  = $this->CommandText;
        }
    }

    private function Prepare()
    {
        // first, check that our SQL has been prepared for execution
        $this->CompileSql();

        // next, make sure our data is ready for binding
        $this->PrepareParameters();

        // finally, build our parameter array
        $this->_params = array();

        foreach ($this->_paramOrder as $paramName)
        {
            if ( ! array_key_exists($paramName, $this->_preparedParams))
            {
                throw new \jj\Exception("Error: parameter $paramName not found.", \jj\Exception::CODE_GENERAL_ERROR);
            }

            $this->_params[] = $this->_preparedParams[$paramName];
        }
    }

    private function CheckTransaction()
    {
        if ($this->Connection->HasTransaction() && ! $this->Transaction)
        {
            throw new \jj\Exception("Error: transaction in progress.", \jj\Exception::CODE_GENERAL_ERROR);
        }

        if ($this->Transaction)
        {
            $this->Transaction->CheckCommand($this);
        }
    }

    private function DoExecute()
    {
        $conn = $this->Connection->_getConn();
        $this->CheckTransaction();
        $this->Prepare();

        return $conn->Execute($this->_sql, $this->_params);
    }

    /**
     * Executes the SQL statement against the database and returns the number of rows affected.
     *
     * @return integer The number of rows affected.
     */
    public function ExecuteNonQuery()
    {
        $this->DoExecute();
        $conn  = $this->Connection->_getConn();
        $rows  = $conn->Affected_Rows();

        if ( ! $rows)
        {
            $rows = 0;
        }

        return $rows;
    }

    /**
     * Executes the query against the database and returns the value in the first column of the first row in the result set.
     *
     * @return mixed The value in the first column of the first row in the result set.
     */
    public function ExecuteScalar()
    {
        $rs = $this->DoExecute();

        if ($rs)
        {
            $val        = $rs->fields[0];
            $transform  = self::_getFieldTransform($rs, 0);

            if ($transform)
            {
                $val = self::_doFieldTransform($rs, $val, $transform);
            }

            return $val;
        }
        else
        {
            return null;
        }
    }

    /**
     * Executes the query against the database and builds a Reader object to handle the result set.
     *
     * @return Reader
     */
    public function ExecuteReader()
    {
        $rs = $this->DoExecute();

        if ($rs)
        {
            $this->_rs = $rs;

            return new Reader($this);
        }
        else
        {
            // let's hope we never get here
            throw new \jj\Exception("Error: no recordset returned.", \jj\Exception::CODE_GENERAL_ERROR);
        }
    }

    /**
     * Internal use only.
     *
     * @return \ADORecordSet
     */
    public function _getRs()
    {
        return $this->_rs;
    }

    /**
     * Internal use only.
     *
     * @return string
     */
    public static function _getFieldTransform(\ADORecordSet $rs, $field)
    {
        if ( ! is_object($field))
        {
            $field = $rs->FetchField($field);
        }

        switch ($rs->MetaType($field->type))
        {
            case "D":
            case "T":

                return "datetime";

                break;
        }

        return null;
    }

    /**
     * Internal use only.
     */
    public static function _doFieldTransform(\ADORecordSet $rs, $value, $transform)
    {
        switch ($transform)
        {
            case "datetime":

                // UTC dates are stored in the database
                $tz = date_default_timezone_get();
                date_default_timezone_set("UTC");
                $ts = new \jj\DateTime($rs->UnixTimeStamp($value));
                date_default_timezone_set($tz);

                return $ts;

                break;
        }

        return null;
    }
}

?>