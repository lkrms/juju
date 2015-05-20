<?php

namespace jj\orm;

/**
 * Base class for ORM objects.
 *
 * @package juju_core
 * @author Luke Arms <luke@arms.to>
 * @copyright Copyright (c) 2012-2015 Luke Arms
 */
abstract class BaseObject
{
    protected $_isNew = true;

    protected $_isModified = false;

    protected $_isDeleted = false;

    public function Save()
    {
    }

    public function Delete()
    {
    }

    public function IsNew()
    {
        return $this->_isNew;
    }

    public function IsModified()
    {
        return $this->_isModified;
    }

    public function IsDeleted()
    {
        return $this->_isDeleted;
    }
}

?>