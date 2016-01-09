<?php

namespace Massaman\Mongo;

/**
 * This is the mongo db connection singleton instance class. It is only concerned
 * with accessing the mongo db and working with array data, no object creation here
 */
class Utils
{
    /**
	 * Convert a string to camel case eg. first_name -> FirstName
 	 * @param string $str
	 * @return string
	 */
	public function snakeCaseToCamelCase($str) {
        $str = strtr($str, '_', ' '); // replace underscores with spaces to create words
        $str = ucwords($str); // upper case all the words
        return str_replace(' ', '', $str); // remove the spaces
    }

    /**
     * Convert camel case to snake case eg. FirstName -> first_name
 	 * @param string $str
	 * @return string
     */
    public function camelCaseToSnakeCase($str) {
        $str = preg_replace('/[A-Z]/', '_\0', $str); // add underscores before uppercase letters
        $str = strtolower($str); // convert all to lower case
        return ltrim($str, '_'); // remove trailing underscore at start
    }
}
