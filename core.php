<?php

/**
 * Juju core.
 *
 * @package juju_core
 * @author Luke Arms <luke@arms.to>
 * @copyright Copyright (c) 2012-2015 Luke Arms
 */

// should be defined by the app, but just in case...
if ( ! defined("JJ_ROOT"))
{
    define("JJ_ROOT", dirname(__file__));
}

// load core settings
require_once (JJ_ROOT . "/core.config.php");

// load __autoload dependencies
require_once ($JJ_CLASS_MAP["jj"] . "/Autoload.php");
require_once ($JJ_CLASS_MAP["jj"] . "/Assert.php");
require_once ($JJ_CLASS_MAP["jj"] . "/Common.php");
require_once ($JJ_CLASS_MAP["jj"] . "/Exception.php");

// load ADOdb
require_once (JJ_ROOT . "/lib/adodb5/adodb-exceptions.inc.php");
require_once (JJ_ROOT . "/lib/adodb5/adodb.inc.php");

// fields are retrieved by index in jj_data_*
$ADODB_FETCH_MODE = ADODB_FETCH_NUM;

// initialise timezone
if (defined("JJ_DEFAULT_TIMEZONE"))
{
    date_default_timezone_set(JJ_DEFAULT_TIMEZONE);
}

/**
 * Attempts to load the given class dynamically.
 *
 * @param string $className The name of the class to load.
 */
function __autoload($className)
{
    $path = jj_Autoload::GetClassPath($className);

    if ($path)
    {
        require_once ($path);
    }
}

/**
 * Shorthand for {@link jj_Common::GetTableName() Common->GetTableName}.
 *
 * @param string $table Base table name.
 * @param jj_data_Connection $conn Database connection.
 * @return string Full table name.
 */
function _getTable($table, jj_data_Connection $conn = null)
{
    return jj_Common::GetTableName($table, $conn);
}

/**
 * Shorthand for {@link jj_data_Connection::GetParameter() Connection->GetParameter}.
 *
 * @param string $field Field name.
 * @param jj_data_Connection $conn Database connection.
 * @return string Parameter identifier.
 */
function _getParam($field, jj_data_Connection $conn = null)
{
    if (is_null($conn))
    {
        $conn = new jj_data_Connection();
    }

    return $conn->GetParameter($field);
}

/**
 * Shorthand for {@link jj_Common::YesNoToBoolean() Common->YesNoToBoolean}.
 *
 * @param string $val The value to convert.
 * @param boolean $default The value to return if $val is NULL or invalid.
 * @return boolean
 */
function _yn2bool($val, $default = false)
{
    return jj_Common::YesNoToBoolean($val, $default);
}

/**
 * Shorthand for {@link jj_Common::BooleanToYesNo() Common->BooleanToYesNo}.
 *
 * @param boolean $val The value to convert.
 * @param boolean $default The meaning of NULL.
 * @return string
 */
function _bool2yn($val, $default = false)
{
    return jj_Common::BooleanToYesNo($val, $default);
}

// required to maintain user sessions
session_start();

// perform any required schema updates
jj_orm_schema_Compiler::CheckAllSchemas();

?>