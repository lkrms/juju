<?php

namespace jj;

/**
 * Adds extra smarts to PHP's exception handling.
 *
 * @package juju_core
 * @author Luke Arms <luke@arms.to>
 * @copyright Copyright (c) 2012-2015 Luke Arms
 */
class Exception extends \Exception
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
    public function __construct($message, $code = Exception::CODE_GENERAL_ERROR, $log = true)
    {
        parent::__construct($message, $code);
        $this->log = $log;

        if ($log)
        {
            self::LogException($this);
        }
    }

    /**
     * @return boolean
     */
    public function getLog()
    {
        return $this->log;
    }

    /**
     * Logs the given exception to the database.
     *
     * @param \Exception $ex The exception to log.
     */
    public static function LogException(\Exception $ex)
    {
        try
        {
            // TODO: add exception logging
            return;
        }
        catch (\Exception $ex2)
        {
            trigger_error($ex2->getMessage(), E_USER_WARNING);
        }

        // if logging to the database fails, display the error to the user
        trigger_error($ex->getMessage(), E_USER_WARNING);
    }
}

?>