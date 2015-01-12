<?php

/**
 * Implement for control over how your objects are stored in the database.
 *
 * @package juju_core
 * @author Luke Arms <luke@arms.to>
 * @copyright Copyright (c) 2012-2013 Luke Arms
 */
interface jj_data_IFormat
{
    public function DataFormat(jj_data_Connection $conn);
}

?>