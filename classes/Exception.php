<?php

/**
 * Adds extra smarts to PHP's exception handling.
 *
 * @package juju_core
 * @author Luke Arms <luke@arms.to>
 * @copyright Copyright (c) 2012-2013 Luke Arms
 */
class jj_Exception extends Exception
{
    /**
     * General error.
     */
    const CODE_GENERAL_ERROR = 0;

    /**
     * Assertion failure.
     */
    const CODE_ASSERTION_FAILURE = 1;

    /**
     * Error in configuration.
     */
    const CODE_CONFIG_ERROR = 2;

    /**
     * Whether or not to log this exception to the database.
     *
     * @var boolean
     */
    protected $log;

    /**
     * Creates a new exception object.
     *
     * @param string $message The exception's message.
     * @param integer $code The exception's code.
     * @param boolean $log Whether or not to log this exception to the database.
     */
    public function __construct($message, $code = jj_Exception::CODE_GENERAL_ERROR, $log = true)
    {
        parent::__construct($message, $code);
        $this->log = $log;

        if ($log)
        {
            self::LogException($this);
        }
    }

    /**
     *
     * @return boolean
     */
    public function getLog()
    {
        return $this->log;
    }

    /**
     * Logs the given exception to the database.
     *
     * @param Exception $ex The exception to log.
     */
    public static function LogException(exception $ex)
    {
        try
        {
            if (class_exists("jj_data_Connection"))
            {
                $conn  = new jj_data_Connection();
                $cmd   = $conn->BuildInsertCommand("log_exceptions", array(
    "message"   => $ex->getMessage(),
    "code"      => $ex->getCode(),
    "file_name" => $ex->getFile(),
    "file_line" => $ex->getLine(),
    "trace"     => $ex->getTraceAsString(),
    "thrown"    => new jj_DateTime(),
    "username"  => jj_security_User::GetCurrentUsername(),
    "remote_ip" => $_SERVER["REMOTE_ADDR"]
));
                $cmd->ExecuteNonQuery();

                return;
            }
        }
        catch (exception $ex2)
        {
            trigger_error($ex2->getMessage(), E_USER_WARNING);
        }

        trigger_error($ex->getMessage(), E_USER_WARNING);
    }
}

?>