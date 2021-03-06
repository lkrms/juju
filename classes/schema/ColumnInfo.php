<?php

namespace jj\schema;

/**
 * Stores information about columns in database tables.
 *
 * @package juju_core
 * @author Luke Arms <luke@arms.to>
 * @copyright Copyright (c) 2015 Luke Arms
 */
class ColumnInfo
{
    public $ColumnName;

    public $DataType;

    public $DefaultValue;

    public $Required;

    public $Size;

    public $Scale;

    public $AutoIncrement;

    public $PrimaryKey;
}

?>