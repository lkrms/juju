<?php

/**
 * Stores information about indexes in database tables.
 *
 * @package juju_core
 * @author Luke Arms <luke@arms.to>
 * @copyright Copyright (c) 2015 Luke Arms
 */
class jj_schema_IndexInfo
{
    public $IndexName;

    public $Unique;

    public $Columns = array();
}

?>