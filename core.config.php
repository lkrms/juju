<?php

/**
 * Core settings.
 *
 * @package juju_core
 * @author Luke Arms <luke@arms.to>
 * @copyright Copyright (c) 2012-2015 Luke Arms
 */

// app developers shouldn't need to make any changes here
if ( ! isset($GLOBALS["JJ_CLASS_MAP"]))
{
    $JJ_CLASS_MAP = array();
}

$JJ_CLASS_MAP["jj"] = JJ_ROOT . "/classes";

?>