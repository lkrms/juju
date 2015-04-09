<?php

/**
 * Provides various helper methods that don't belong anywhere else.
 *
 * @package juju_core
 * @author Luke Arms <luke@arms.to>
 * @copyright Copyright (c) 2012-2015 Luke Arms
 */
abstract class jj_Common
{
    private static $_cacheFolder;

    /**
     * Checks that the cache folder exists and is writable, then returns its full path.
     *
     * @return string The full path to the cache folder.
     */
    public static function GetCacheFolder()
    {
        if ( ! self::$_cacheFolder)
        {
            $folder = JJ_ROOT . "/.cache";
            jj_Assert::IsWritable($folder, "cache folder");
            self::$_cacheFolder = $folder;
        }

        return self::$_cacheFolder;
    }

    /**
     * Searches for the given value in the given array, and removes it if found.
     *
     * @param mixed $value The value to search for.
     * @param array $array The array to search.
     * @return boolean Whether or not the value was found in the array.
     */
    public static function ArraySearchSplice($value, array & $array)
    {
        $key = array_search($value, $array);

        if ($key !== false)
        {
            unset($array[$key]);

            return true;
        }

        return false;
    }

    /**
     * Searches the given array for a value that matches the given pattern, and removes it if found.
     *
     * @param string $pattern The pattern to match.
     * @param array $array The array to search.
     * @return string The value (if found), or NULL.
     */
    public static function ArrayMatchSplice($pattern, array & $array)
    {
        jj_Assert::IsString($pattern, "pattern");
        $found = false;

        foreach ($array as $key => $value)
        {
            if (is_string($value) && preg_match($pattern, $value))
            {
                $found = true;

                break;
            }
        }

        if ($found)
        {
            unset($array[$key]);

            return $value;
        }

        return null;
    }

    /**
     * Searches the given array for a value with the given key, and removes it if found.
     *
     * @param string $key The key to search for.
     * @param array $array The array to search.
     * @return string The value (if found), or NULL.
     */
    public static function ArrayKeySplice($key, array & $array)
    {
        jj_Assert::IsString($key, "key");
        $value = null;

        if (array_key_exists($key, $array))
        {
            $value = $array[$key];
            unset($array[$key]);
        }

        return $value;
    }

    /**
     * Removes values that match the given keys from the given array.
     *
     * @param array $keys Keys to match.
     * @param array $array The array to search.
     * @return array The modified array.
     */
    public static function ArrayMultiSplice( array $keys, array $array)
    {
        foreach ($keys as $key)
        {
            if (array_key_exists($key, $array))
            {
                unset($array[$key]);
            }
        }

        return $array;
    }

    /**
     * Adds a connection-specific prefix to the given table name.
     *
     * See also _getTable in the global context.
     *
     * @param string $table Base table name.
     * @param jj_data_Connection $conn Database connection.
     * @return string Full table name.
     */
    public static function GetTableName($table, jj_data_Connection $conn = null)
    {
        if (is_null($conn))
        {
            return JJ_DB_PREFIX . $table;
        }
        else
        {
            return $conn->Prefix . $table;
        }
    }

    /**
     * Converts the given value (Y, N or NULL) to its boolean equivalent.
     *
     * @param string $val The value to convert.
     * @param boolean $default The value to return if $val is NULL or invalid.
     * @return boolean
     */
    public static function YesNoToBoolean($val, $default = false)
    {
        if (is_null($val))
        {
            return $default;
        }
        else
        {
            jj_Assert::IsString($val, "val");
        }

        if ( ! strcasecmp($val, "Y"))
        {
            return true;
        }

        if ( ! strcasecmp($val, "N"))
        {
            return false;
        }

        return $default;
    }

    /**
     * Converts the given boolean to its Y, N or NULL equivalent. If $val and $default are a match, NULL is returned.
     *
     * @param boolean $val The value to convert.
     * @param boolean $default The meaning of NULL.
     * @return string
     */
    public static function BooleanToYesNo($val, $default = false)
    {
        if (is_null($val))
        {
            return null;
        }
        else
        {
            jj_Assert::IsBoolean($val, "val");
        }

        if ($val == $default)
        {
            return null;
        }

        return $val ? "Y" : "N";
    }

    /**
     * Parses tokens in the given string.
     *
     * @param string $val The string with tokens to parse, e.g. "SELECT * FROM #TABLE:_users#"
     * @param jj_data_Connection $conn The relevant database connection, if applicable.
     * @return string The parsed string.
     */
    public static function ParseTokens($val, jj_data_Connection $conn = null)
    {
        $matches = array();
        preg_match_all("/#([_a-zA-Z0-9]+):(.*?)#/", $val, $matches, PREG_SET_ORDER);

        for ($i = 0; $i < count($matches); $i++)
        {
            $replace = "";

            switch ($matches[$i][1])
            {
                case "TABLE":

                    $replace = _getTable($matches[$i][2], $conn);

                    break;

                default:

                    throw new jj_Exception("Error: {$matches[$i][1]} is not a valid token type.", jj_Exception::CODE_GENERAL_ERROR);
            }

            $val = str_replace($matches[$i][0], $replace, $val);
        }

        return $val;
    }

    /**
     * Converts the given string to CamelCase.
     *
     * @param string $val The string to convert.
     * @param string $delimiter The delimiter to use when splitting the string into words.
     */
    public static function GetCamelCase($val, $delimiter = "_")
    {
        jj_Assert::IsString($val, "val");
        jj_Assert::IsString($delimiter, "delimiter");
        jj_Assert::IsNotEmpty($delimiter, "delimiter");

        // split
        $words = explode($delimiter, $val);

        // re-assemble
        $cc = "";

        foreach ($words as $word)
        {
            $cc .= ucfirst(strtolower($word));
        }

        return $cc;
    }
}

?>