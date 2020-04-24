<?php

namespace FlynIO;

class Utils
{
    /**
     * Returns the given element in an array if it exists, else default.
     *
     * @param array $arr
     * @param mixed $key
     * @param mixed $default
     * @return mixed
     */
    public static function arrayGet(array $arr, $key, $default)
    {
        return isset($arr[$key]) ? $arr[$key] : $default;
    }

    /**
     * Load and return a given file. Provide given variables to it.
     *
     * @param string $filepath
     * @param array $vars       Any variables accessible to the file being returned
     * @return string
     */
    public static function requireWith(string $filepath, array $vars = []): string
    {
        extract($vars);
        ob_start();
            require $filepath;
        return ob_get_clean();
    }
}
