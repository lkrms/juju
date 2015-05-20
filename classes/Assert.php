<?php

namespace jj;

/**
 * Just a set of simple assertion methods.
 *
 * @package juju_core
 * @author Luke Arms <luke@arms.to>
 * @copyright Copyright (c) 2012-2015 Luke Arms
 */
abstract class Assert
{
    /**
     * Asserts that the given value is not null.
     *
     * @param mixed $val The value to test.
     * @param string $name The name of the value (used if assertion fails).
     */
    public static function IsNotNull($val, $name)
    {
        if (is_null($val))
        {
            throw new Exception("Error: $name cannot be null.", Exception::CODE_ASSERTION_FAILURE);
        }
    }

    /**
     * Asserts that the given value is not empty. Implies that it must not be null.
     *
     * @param mixed $val The value to test.
     * @param string $name The name of the value (used if assertion fails).
     */
    public static function IsNotEmpty($val, $name)
    {
        if (empty($val))
        {
            throw new Exception("Error: $name cannot be empty.", Exception::CODE_ASSERTION_FAILURE);
        }
    }

    /**
     * Asserts that the given value is a boolean.
     *
     * @param mixed $val The value to test.
     * @param string $name The name of the value (used if assertion fails).
     */
    public static function IsBoolean($val, $name)
    {
        if ( ! is_bool($val))
        {
            throw new Exception("Error: $name must be a boolean.", Exception::CODE_ASSERTION_FAILURE);
        }
    }

    /**
     * Asserts that the given value is an integer.
     *
     * @param mixed $val The value to test.
     * @param string $name The name of the value (used if assertion fails).
     */
    public static function IsInteger($val, $name)
    {
        if ( ! is_int($val))
        {
            throw new Exception("Error: $name must be an integer.", Exception::CODE_ASSERTION_FAILURE);
        }
    }

    /**
     * Asserts that the given value is a string.
     *
     * @param mixed $val The value to test.
     * @param string $name The name of the value (used if assertion fails).
     */
    public static function IsString($val, $name)
    {
        if ( ! is_string($val))
        {
            throw new Exception("Error: $name must be a string.", Exception::CODE_ASSERTION_FAILURE);
        }
    }

    /**
     * Asserts that the given value is a number or numeric string.
     *
     * @param mixed $val The value to test.
     * @param string $name The name of the value (used if assertion fails).
     */
    public static function IsNumeric($val, $name)
    {
        if ( ! is_numeric($val))
        {
            throw new Exception("Error: $name must be numeric.", Exception::CODE_ASSERTION_FAILURE);
        }
    }

    /**
     * Asserts that the given value is a date string that can be parsed by PHP's strtotime() function.
     *
     * @param mixed $val The value to test.
     * @param string $name The name of the value (used if assertion fails).
     */
    public static function IsDateString($val, $name)
    {
        Assert::IsString($val, $name);

        // the easiest way to perform this test is to attempt the conversion
        $ts = strtotime($val);

        if ($ts === false || $ts == -1)
        {
            throw new Exception("Error: $name must be a date string.", Exception::CODE_ASSERTION_FAILURE);
        }
    }

    /**
     * Asserts that the given value is a valid identifier, i.e. a string containing at least one alphanumeric character or underscore.
     *
     * @param mixed $val The value to test.
     * @param string $name The name of the value (used if assertion fails).
     * @param array $extras Additional characters/strings that are considered legal.
     */
    public static function IsValidIdentifier($val, $name, array $extras = null)
    {
        Assert::IsString($val, $name);

        if (is_array($extras))
        {
            $val = str_replace($extras, "", $val);
        }

        if ( ! preg_match("/^[_a-zA-Z0-9]+$/", $val))
        {
            throw new Exception("Error: $name ($val) must be a valid identifier.", Exception::CODE_ASSERTION_FAILURE);
        }
    }

    /**
     * Asserts that the given value is in the given array.
     *
     * @param mixed $val The value to test.
     * @param array $array An array of acceptable values.
     * @param string $name The name of the value (used if assertion fails).
     */
    public static function IsInArray($val, array $array, $name)
    {
        if ( ! in_array($val, $array))
        {
            throw new Exception("Error: $name must be valid.", Exception::CODE_ASSERTION_FAILURE);
        }
    }

    /**
     * Asserts that the given value corresponds to the name of an existing file.
     *
     * @param mixed $val The value to test.
     * @param string $name The name of the value (used if assertion fails).
     */
    public static function FileExists($val, $name)
    {
        Assert::IsString($val, $name);

        if ( ! file_exists($val))
        {
            throw new Exception("Error: $name ($val) does not exist.", Exception::CODE_ASSERTION_FAILURE);
        }
    }

    /**
     * Asserts that the given value corresponds to the name of a file or folder that is writable.
     *
     * @param mixed $val The value to test.
     * @param string $name The name of the value (used if assertion fails).
     */
    public static function IsWritable($val, $name)
    {
        Assert::IsString($val, $name);

        if ( ! is_writable($val))
        {
            throw new Exception("Error: $name ($val) is not writable.", Exception::CODE_ASSERTION_FAILURE);
        }
    }

    /**
     * Asserts that the given value corresponds to the name of a defined class.
     *
     * @param mixed $val The value to test.
     * @param string $name The name of the value (used if assertion fails).
     */
    public static function ClassExists($val, $name)
    {
        Assert::IsString($val, $name);

        if ( ! class_exists($val))
        {
            throw new Exception("Error: $name ($val) is not defined.", Exception::CODE_ASSERTION_FAILURE);
        }
    }

    /**
     * Asserts that the given value corresponds to the name of a class that inherits from the given class.
     *
     * @param mixed $val The value to test.
     * @param string $class The name of the class.
     * @param string $name The name of the value (used if assertion fails).
     */
    public static function ClassExtends($val, $class, $name)
    {
        Assert::IsString($val, $name);

        if ( ! is_subclass_of($val, $class))
        {
            throw new Exception("Error: $name ($val) does not inherit from $class.", Exception::CODE_ASSERTION_FAILURE);
        }
    }

    /**
     * Asserts that the given value corresponds to the name of a class that matches or inherits from the given class.
     *
     * @param mixed $val The value to test.
     * @param string $class The name of the class.
     * @param string $name The name of the value (used if assertion fails).
     */
    public static function ClassIsOrExtends($val, $class, $name)
    {
        Assert::IsString($val, $name);

        if ( ! is_a($val, $class))
        {
            throw new Exception("Error: $name ($val) does not inherit from $class.", Exception::CODE_ASSERTION_FAILURE);
        }
    }

    /**
     * Asserts that the given value corresponds to the name of a class that implements the given interface.
     *
     * @param mixed $val The value to test.
     * @param string $interface The name of the interface.
     * @param string $name The name of the value (used if assertion fails).
     */
    public static function ClassImplementsInterface($val, $interface, $name)
    {
        Assert::ClassExists($val, $name);
        $class = new \ReflectionClass($val);

        if ( ! $class->implementsInterface($interface))
        {
            throw new Exception("Error: $name ($val) does not implement interface $interface.", Exception::CODE_ASSERTION_FAILURE);
        }
    }

    /**
     * Asserts that the given value is an instance of the given class.
     *
     * @param mixed $val The value to test.
     * @param string $class The name of the class.
     * @param string $name The name of the value (used if assertion fails).
     */
    public static function IsInstanceOf($val, $class, $name)
    {
        if ( ! is_object($val) || ! is_a($val, $class))
        {
            throw new Exception("Error: $name is not an instance of $class.", Exception::CODE_ASSERTION_FAILURE);
        }
    }
}

?>