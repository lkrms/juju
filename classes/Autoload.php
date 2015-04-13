<?php

/**
 * Provides various methods to assist with class autoloading.
 *
 * @package juju_core
 * @author Luke Arms <luke@arms.to>
 * @copyright Copyright (c) 2012-2015 Luke Arms
 */
abstract class jj_Autoload
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
        global $JJ_CLASS_MAP;

        // check if we've cached the location of this class, otherwise locate it and cache
        $locationFile = jj_Common::GetCacheFolder() . "/{$className}.location";

        if ( ! (file_exists($locationFile) && file_exists($filename = file_get_contents($locationFile))))
        {
            // juju classes use underscores to simulate namespacing, e.g. jj_namespace_SomeClass
            $namespace      = explode("_", $className);
            $classFile      = array_pop($namespace);
            $fullNamespace  = implode(".", $namespace);
            $module         = array_shift($namespace);
            $classPath      = implode("/", $namespace);
            $path           = "";

            if ($fullNamespace && isset($JJ_CLASS_MAP[$fullNamespace]))
            {
                // this picks up classes at the root of modules too
                $path = $JJ_CLASS_MAP[$fullNamespace] . "/{$classFile}.php";
            }
            elseif ($module && $classPath && isset($JJ_CLASS_MAP[$module]))
            {
                $path = $JJ_CLASS_MAP[$module] . "/{$classPath}/{$classFile}.php";
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
                    throw new jj_Exception("Error: $locationFile is not writable.");
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
}

?>