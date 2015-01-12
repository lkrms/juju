<?php

/**
 * Represents an instant in time.
 *
 * @package juju_core
 * @author Luke Arms <luke@arms.to>
 * @copyright Copyright (c) 2012-2013 Luke Arms
 */
class jj_DateTime implements jj_data_IFormat
{
    private $_ts;

    public function __construct($timestamp = null)
    {
        if (is_null($timestamp))
        {
            $timestamp = time();
        }
        else
        {
            jj_Assert::IsNumeric($timestamp, "timestamp");
        }

        $this->_ts = 0 + $timestamp;
    }

    /**
     * Returns the year component of the date, adjusted for the local time zone.
     *
     * @return integer
     */
    public function GetYear()
    {
        return 0 + date("Y", $this->_ts);
    }

    /**
     * Returns the month component of the date, adjusted for the local time zone.
     *
     * @return integer
     */
    public function GetMonth()
    {
        return 0 + date("n", $this->_ts);
    }

    /**
     * Returns the day component of the date, adjusted for the local time zone.
     *
     * @return integer
     */
    public function GetDay()
    {
        return 0 + date("j", $this->_ts);
    }

    /**
     * Returns the hour component of the time, adjusted for the local time zone.
     *
     * @return integer
     */
    public function GetHour()
    {
        return 0 + date("G", $this->_ts);
    }

    /**
     * Returns the minutes component of the time, adjusted for the local time zone.
     *
     * @return integer
     */
    public function GetMinute()
    {
        return 0 + date("i", $this->_ts);
    }

    /**
     * Returns the seconds component of the time, adjusted for the local time zone.
     *
     * @return integer
     */
    public function GetSecond()
    {
        return 0 + date("s", $this->_ts);
    }

    /**
     * Returns the date in the given format, adjusted for the local time zone.
     *
     * @param string $formatString Passed as-is to PHP's date() function.
     * @return string The formatted date.
     */
    public function Format($formatString)
    {
        return date($formatString, $this->_ts);
    }

    public function DataFormat(jj_data_Connection $conn)
    {
        // datetime formatting is connection-specific
        return $conn->FormatDateTime($this->_ts);
    }
}

?>