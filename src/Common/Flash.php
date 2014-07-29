<?php

namespace Common;

/**
 * Simple session flash class for storing temporary
 * messages in a user session.
 * @author James Pegg <jamescpegg@gmail.com>
 */
class Flash
{
	/**
	 * Set a flash message
	 * @param string $key   The type of message (e.g. error, success)
	 * @param string $value The message
	 */
	public static function set($key, $value)
	{
		$_SESSION['flash'][$key] = $value;
	}

	/**
	 * Retrieve a flash message
	 * @param  string $key The type of message (e.g. error, success)
	 * @return string      The message
	 */
	public static function get($key)
	{
		if (self::has($key)) {
			$value = $_SESSION['flash'][$key];
			unset($_SESSION['flash'][$key]);
			return $value;
		}
	}

	/**
	 * Checks if a flash message has been set
	 * @param  string  $key The type of message (e.g. error, success)
	 * @return boolean
	 */
	public static function has($key)
	{
		return isset($_SESSION['flash'][$key]);
	}
}