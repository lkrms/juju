<?php

namespace jj;

/**
 * Provides various methods to assist with class autoloading.
 *
 * @package juju_core
 * @author Luke Arms <luke@arms.to>
 * @copyright Copyright (c) 2012-2015 Luke Arms
 */
abstract class Autoload
{
    /**
     * Returns the path to the code file for the given class.
     *
     * @param string $className The name of the class to locate.
     * @param boolean $mustExist Whether or not to return the path even if it doesn't exist.
     * @return string NULL if the class could not be located.
     */
    public static function GetClassPath($className, $mustExist = true)
    {
        global $JJ_NAMESPACES;

        // check if we've cached the location of this class, otherwise locate it and cache
        $locationFile = Common::GetCacheFolder() . "/" . str_replace("\\", "--", $className) . ".location";

        if ( ! (file_exists($locationFile) && file_exists($filename = file_get_contents($locationFile))))
        {
            $namespace      = explode("\\", $className);
            $classFile      = array_pop($namespace);
            $rootNamespace  = array_shift($namespace);
            $classPath      = implode("/", $namespace);
            $path           = "";

            if ($classPath)
            {
                $classPath .= "/";
            }

            if ($rootNamespace && isset($JJ_NAMESPACES[$rootNamespace]))
            {
                $path = $JJ_NAMESPACES[$rootNamespace] . "/{$classPath}{$classFile}.php";
            }

            if ( ! $path || ($mustExist && ! file_exists($path)))
            {
                return null;
            }

            if (file_exists($path))
            {
                $filename = realpath($path);

                if (file_exists($locationFile) && ! is_writable($locationFile) && ! @unlink($locationFile))
                {
                    throw new Exception("Error: $locationFile is not writable.");
                }

                file_put_contents($locationFile, $filename);
            }
            else
            {
                // don't cache non-existent class paths
                $filename = $path;
            }
        }

        return $filename;
    }

    public static function LoadClass($className)
    {
        $path = self::GetClassPath($className);

        if ($path)
        {
            require_once ($path);
        }
    }
}

spl_autoload_register('jj\Autoload::LoadClass');

?>