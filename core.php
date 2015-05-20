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
require_once ($JJ_NAMESPACES["jj"] . "/Autoload.php");
require_once ($JJ_NAMESPACES["jj"] . "/Assert.php");
require_once ($JJ_NAMESPACES["jj"] . "/Common.php");
require_once ($JJ_NAMESPACES["jj"] . "/Exception.php");

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
 * Shorthand for {@link jj\Common::GetTableName() Common->GetTableName}.
 *
 * @param string $table Base table name.
 * @param jj\data\Connection $conn Database connection.
 * @return string Full table name.
 */
function _getTable($table, jj\data\Connection $conn = null)
{
    return jj\Common::GetTableName($table, $conn);
}

/**
 * Shorthand for {@link jj\data\Connection::GetParameter() Connection->GetParameter}.
 *
 * @param string $field Field name.
 * @param jj\data\Connection $conn Database connection.
 * @return string Parameter identifier.
 */
function _getParam($field, jj\data\Connection $conn = null)
{
    if (is_null($conn))
    {
        $conn = new jj\data\Connection();
    }

    return $conn->GetParameter($field);
}

// required to maintain user sessions
session_start();

// perform any required schema updates
jj\orm\schema\Compiler::CheckAllSchemas();

?>