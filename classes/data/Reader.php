<?php

/**
 * Provides a mechanism for reading rows of data from a database.
 *
 * @package juju_core
 * @author Luke Arms <luke@arms.to>
 * @copyright Copyright (c) 2012-2013 Luke Arms
 */
class jj_data_Reader
{
    /**
     *
     * @var jj_data_Command
     */
    public $Command;

    public $FieldCount;

    public $RowCount;

    public $IsClosed = false;

    /**
     *
     * @var ADORecordSet
     */
    private $_rs;

    private $_fieldNames = array();

    private $_fieldIndexes = array();

    private $_fieldTransforms = array();

    private $_row = 0;

    private $_dataAvailable = false;

    private $_fields;

    /**
     * Internal use only. See {@link jj_data_Command::ExecuteReader() Command->ExecuteReader}.
     */
    public function __construct(jj_data_Command $cmd)
    {
        $this->Command     = $cmd;
        $this->_rs         = $cmd->_getRs();
        $this->FieldCount  = $this->_rs->FieldCount();
        $this->RowCount    = $this->_rs->RowCount();

        for ($i = 0; $i < $this->FieldCount; $i++)
        {
            $fieldInfo                              = $this->_rs->FetchField($i);
            $this->_fieldNames[]                    = $fieldInfo->name;
            $this->_fieldIndexes[$fieldInfo->name]  = $i;
            $transform = jj_data_Command::_getFieldTransform($this->_rs, $fieldInfo);

            if ($transform)
            {
                $this->_fieldTransforms[$i] = $transform;
            }
        }
    }

    private function AssertOpen()
    {
        if ($this->IsClosed)
        {
            throw new jj_Exception("Error: Reader object is closed.", jj_Exception::CODE_GENERAL_ERROR);
        }
    }

    private function AssertDataAvailable()
    {
        if ( ! $this->_dataAvailable)
        {
            throw new jj_Exception("Error: no more data.", jj_Exception::CODE_GENERAL_ERROR);
        }
    }

    private function AssertColumnInRange($index)
    {
        if ( ! is_int($index) || $index < 0 or $index > $this->FieldCount - 1)
        {
            throw new jj_Exception("Error: column index out of range.", jj_Exception::CODE_GENERAL_ERROR);
        }
    }

    private function ProcessTransforms( array $fields)
    {
        foreach ($this->_fieldTransforms as $i => $transform)
        {
            $fields[$i] = jj_data_Command::_doFieldTransform($this->_rs, $fields[$i], $transform);
        }

        return $fields;
    }

    /**
     * Advances the reader to the next record. Call before attempting to access the first record.
     *
     * @return boolean FALSE if there is no more data; otherwise, TRUE.
     */
    public function Read()
    {
        $this->AssertOpen();

        if ($this->_row++ && ! $this->_rs->EOF)
        {
            $this->_rs->MoveNext();
        }

        if ($this->_rs->EOF)
        {
            $this->_dataAvailable = false;

            return false;
        }

        $this->_fields         = $this->ProcessTransforms($this->_rs->fields);
        $this->_dataAvailable  = true;

        return true;
    }

    /**
     * Returns the value in the given column.
     *
     * @param mixed $column The name or index of the column to return.
     * @return mixed
     */
    public function GetValue($column)
    {
        $this->AssertOpen();
        $this->AssertDataAvailable();

        if (is_string($column))
        {
            $column = $this->GetIndex($column);
        }
        else
        {
            $this->AssertColumnInRange($column);
        }

        return $this->_fields[$column];
    }

    /**
     * Returns an array containing all values in the record.
     *
     * @param boolean $useNames Whether or not to key the array on column names.
     * @return array
     */
    public function GetValues($useNames = false)
    {
        $this->AssertOpen();
        $this->AssertDataAvailable();

        if ($useNames)
        {
            return array_combine($this->_fieldNames, $this->_fields);
        }

        return $this->_fields;
    }

    /**
     * Returns the name of the column at the given index.
     *
     * @param integer $index
     * @return string
     */
    public function GetName($index)
    {
        $this->AssertOpen();
        $this->AssertColumnInRange($index);

        return $this->_fieldNames[$index];
    }

    /**
     * Returns the index of the column with the given name.
     *
     * @param string $name
     * @return integer
     */
    public function GetIndex($name)
    {
        $this->AssertOpen();

        if ( ! isset($this->_fieldIndexes[$name]))
        {
            throw new jj_Exception("Error: invalid column name.", jj_Exception::CODE_GENERAL_ERROR);
        }

        return $this->_fieldIndexes[$name];
    }

    /**
     * Closes the reader and releases any resources associated with it.
     *
     */
    public function Close()
    {
        $this->IsClosed = true;
        $this->_rs->Close();
    }
}

?>