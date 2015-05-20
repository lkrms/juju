<?php

namespace jj\data;

/**
 * Implement for control over how your objects are stored in the database.
 *
 * @package juju_core
 * @author Luke Arms <luke@arms.to>
 * @copyright Copyright (c) 2012-2015 Luke Arms
 */
interface IFormat
{
    public function DataFormat(Connection $conn);
}

?>