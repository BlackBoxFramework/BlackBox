<?php

namespace Common;

/**
 * Class for accessing all HTTP inputs
 *
 * @author James Pegg <jamescpegg@gmail.com>
 */
class Input
{
	use \Common\Traits\Singleton;

	/**
	 * Parsed input stream
	 * @var array
	 */
	private static $input;

	/**
	 * Singleton constructor.
	 * Parses and stores PHP input stream
	 * @return null
	 */
	private function construct()
	{
		// Parse any inputs (POST / PUT)
		parse_str(file_get_contents('php://input'), self::$input);

		// Append GET data
		self::$input += $_GET;
	}

	/**
	 * Get a variable
	 * @param  string $var
	 * @return mixed
	 */
	public static function get($var)
	{
		return self::$input[$var];
	}

	/**
	 * Check if a variable exists
	 * @param  string  $var
	 * @return boolean
	 */
	public static function has($var)
	{
		return isset(self::$input[$var]) && !(is_bool(self::$input[$var]) || is_array(self::$input[$var])) && (string) trim(self::$input[$var]) !== '';
	}

	/**
	 * Removes a variable from the input array
	 * @param  string $var
	 * @return void
	 */
	public static function delete($var)
	{
		if (self::has($var)) {
			unset(self::$input[$var]);
		}
	}

	/**
	 * Returns all of the input data
	 * @return array
	 */
	public static function all()
	{
		return self::$input;
	}

	/**
	 * Filter an input variable
	 * @param  string $var
	 * @param  integer $filter 
	 * @param  integer $flag   
	 * @return mixed
	 */
	public static function filter($var, $filter, $flag = null)
	{
		return filter_var(self::get($var), $filter, $flag);
	}

	/**
	 * Alias for returning filtered string
	 * @param  string $var 
	 * @return string      
	 */
	public static function string($var)
	{
		return (string) self::filter($var, FILTER_SANITIZE_STRING);
	}

	/**
	 * Alias for returning filtered email address
	 * @param  string $var 
	 * @return string      
	 */
	public static function email($var)
	{
		return (String) self::filter($var, FILTER_SANITIZE_EMAIL);
	}	

	/**
	 * Alias for returning filtered integer
	 * @param  string $var 
	 * @return integer    
	 */
	public static function integer($var)
	{
		return (int) self::filter($var, FILTER_SANITIZE_NUMBER_INT);
	}

	/**
	 * Alias for returning filtered float
	 * @param  string $var 
	 * @return float
	 */
	public static function float($var)
	{
		return (float) self::filter($var, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION + FILTER_FLAG_ALLOW_THOUSAND);
	}

	/**
	 * Alias for returning filtered boolean
	 * @param  string $var 
	 * @return boolean
	 */
	public static function bool($var)
	{
		return (bool) self::filter($var, FILTER_VALIDATE_BOOLEAN);
	}
}