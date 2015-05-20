<?php

namespace jj\data;

/**
 * Represents a SQL transaction.
 *
 * @package juju_core
 * @author Luke Arms <luke@arms.to>
 * @copyright Copyright (c) 2012-2015 Luke Arms
 */
class Transaction
{
    /**
     * Fastest, but dirty reads are possible.
     */
    const ISOLATION_LEVEL_READ_UNCOMMITTED = 0;

    /**
     * No dirty reads, but non-repeatable reads are possible.
     */
    const ISOLATION_LEVEL_READ_COMMITTED = 1;

    /**
     * No non-repeatable reads, but phantom rows are possible.
     */
    const ISOLATION_LEVEL_REPEATABLE_READ = 2;

    /**
     * No phantom rows, but slowest.
     */
    const ISOLATION_LEVEL_SERIALIZABLE = 3;

    /**
     * The database connection associated with the transaction.
     *
     * @var Connection
     */
    public $Connection;

    /**
     * One of the ISOLATION_LEVEL_* values.
     *
     * @var integer
     */
    public $IsolationLevel;

    /**
     * TRUE if the transaction has been committed or rolled back.
     *
     * @var boolean
     */
    public $IsComplete = false;

    /**
     *
     * @var \ADOConnection
     */
    private $_conn;

    /**
     * Internal use only. See {@link Connection::BeginTransaction() Connection->BeginTransaction}.
     */
    public function __construct(Connection $conn, $isolationLevel)
    {
        \jj\Assert::IsNotNull($conn, "conn");
        self::AssertIsValidIsolationLevel($isolationLevel, "isolationLevel");
        $this->Connection      = $conn;
        $this->IsolationLevel  = $isolationLevel;
        $this->_conn           = $conn->_getConn();
        $this->_conn->SetTransactionMode(self::GetTransactionMode($isolationLevel));

        if ( ! $this->_conn->BeginTrans())
        {
            throw new \jj\Exception("Error: transactions are not supported by this database.", \jj\Exception::CODE_GENERAL_ERROR);
        }
    }

    private static function GetTransactionMode($isolationLevel)
    {
        $mode = "";

        switch ($isolationLevel)
        {
            case self::ISOLATION_LEVEL_READ_UNCOMMITTED:

                $mode = "READ UNCOMMITTED";

                break;

            case self::ISOLATION_LEVEL_READ_COMMITTED:

                $mode = "READ COMMITTED";

                break;

            case self::ISOLATION_LEVEL_REPEATABLE_READ:

                $mode = "REPEATABLE READ";

                break;

            case self::ISOLATION_LEVEL_SERIALIZABLE:

                $mode = "SERIALIZABLE";

                break;
        }

        return $mode;
    }

    /**
     * Asserts that the given value is a valid transaction isolation level.
     *
     * @param mixed $val The value to test.
     * @param string $name The name of the value (used if assertion fails).
     */
    public static function AssertIsValidIsolationLevel($val, $name)
    {
        \jj\Assert::IsInteger($val, $name);

        if ( ! in_array($val, array(self::ISOLATION_LEVEL_READ_UNCOMMITTED, self::ISOLATION_LEVEL_READ_COMMITTED, self::ISOLATION_LEVEL_REPEATABLE_READ, self::ISOLATION_LEVEL_SERIALIZABLE)))
        {
            throw new \jj\Exception("Error: $name is not a valid isolation level.", \jj\Exception::CODE_ASSERTION_FAILURE);
        }
    }

    /**
     * Asserts that the transaction has not yet been committed or rolled back.
     */
    public function AssertIsNotComplete()
    {
        if ($this->IsComplete)
        {
            throw new \jj\Exception("Error: transaction is complete.", \jj\Exception::CODE_ASSERTION_FAILURE);
        }
    }

    /**
     * Internal use only.
     */
    public function CheckCommand(Command $command)
    {
        \jj\Assert::IsNotNull($command, "command");
        $this->AssertIsNotComplete();

        if ($command->Connection !== $this->Connection)
        {
            throw new \jj\Exception("Error: transaction is not associated with the command's connection.", \jj\Exception::CODE_GENERAL_ERROR);
        }
    }

    /**
     * Commits the transaction.
     */
    public function Commit()
    {
        $this->AssertIsNotComplete();
        $this->_conn->CommitTrans();
        $this->IsComplete = true;
    }

    /**
     * Rolls the transaction back.
     */
    public function Rollback()
    {
        $this->AssertIsNotComplete();
        $this->_conn->RollbackTrans();
        $this->IsComplete = true;
    }
}

// PRETTY_NESTED_ARRAYS,0

?>