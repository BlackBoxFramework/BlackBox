<?php
/**
 * This file contains a collection of globally available helper classes.
 */

/**
 * Fetches a JSON file and converts it into a PHP variable.
 * @param  string  $filename         Name of the file to be read
 * @param  boolean $use_include_path Use the include path to search for the file
 * @param  boolean $assoc            When TRUE, returned objects will be converted into associative arrays
 * @param  integer $depth            User specified recursion depth.
 * @return mixed
 * @author James Pegg <jamescpegg@gmail.com>
 */
function json_get_contents($filename, $use_include_path = false, $assoc = false, $depth = 512)
{
	return json_decode(file_get_contents($filename, $use_include_path), $assoc, $depth);
}

/**
 * Interpolates a string and inserts context.
 * @param  string $message
 * @param  array  $context
 * @return string
 * @link   https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-3-logger-interface.md
 */
function interpolate($message, array $context = array())
{
	if (is_string($message)) {
		// Build a replacement array with braces around the context keys
		$replace = [];
		foreach ($context as $key => $val) {
			$replace['{' . $key . '}'] = $val;
		}

		// interpolate replacement values into the message and return
		return strtr($message, $replace);
	} else {
		trigger_error('Message is not a string.');
	}
}

/**
 * Searches an array for a type of object.
 * @param  array  $array
 * @param  string $object
 * @return boolean
 */
function array_search_object(array $array, $object)
{	
	if (is_string($object)) {
		foreach ($array as $value) {
			if ($value instanceof $object) {
				return true;
			}
		}		
	}

	return false;
}
