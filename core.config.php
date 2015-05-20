<?php

/**
 * Core settings.
 *
 * @package juju_core
 * @author Luke Arms <luke@arms.to>
 * @copyright Copyright (c) 2012-2015 Luke Arms
 */

// app developers shouldn't need to make any changes here
if ( ! isset($GLOBALS["JJ_NAMESPACES"]))
{
    $JJ_NAMESPACES = array();
}

$JJ_NAMESPACES["jj"] = JJ_ROOT . "/classes";

if ( ! isset($GLOBALS["JJ_SCHEMAS"]))
{
    $JJ_SCHEMAS = array();
}

array_unshift($JJ_SCHEMAS, array(JJ_ROOT . "/core.schema.yml", null));

// PRETTY_NESTED_ARRAYS,0

?>