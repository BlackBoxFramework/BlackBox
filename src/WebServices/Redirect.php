<?php

namespace WebServices;

/**
 * The redirect class handles permanent (301) and temporary (302)
 * redirects. It can also take an array of redirects and redirect
 * a request.
 *
 * @author James Pegg <jamescpegg@gmail.com>
 */
class Redirect
{
	/**
	 * Permanent redirect
	 * @param  string $url
	 */
	public static function permanent($url, $flags = NULL)
	{
		http_response_code(301);
		self::location($url, $flags);
	}

	/**
	 * Temporary redirect
	 * @param  string $url
	 */
	public static function temporary($url, $flags = NULL)
	{
		http_response_code(302);
		self::location($url, $flags);
	}

	/**
	 * Takes an array of redirects and processes them.
	 * @param  array  $redirects
	 * @param  string $request
	 * @throws Exception If unsupported redirect type is given
	 */
	public static function fromArray(array $redirects, $request)
	{
		if (isset($redirects[$request])) {
			$redirect = $redirects[$request];

			if (is_string($redirect)) {
				self::temporary($redirect);
			} elseif (is_array($redirect)) {
				$url = key($redirect);

				switch ($redirect[$url]) {
					case '302':
						self::temporary($url);
						break;

					case '301':
						self::permanent($url);
						break;

					default:
						throw new \Exception("Redirect type {$redirect[$url]} not currently supported.", 1);
						break;
				}
			}
		}
	}

	/**
	 * Set header location
	 * @param  string $url
	 * @throws InvalidArgumentException If arguments are invalid
	 */
	private static function location($url, $flags)
	{
		if (is_string($url) && filter_var($url, FILTER_VALIDATE_URL, $flags)) {
			header("Location: {$url}");
			die();			
		} else {
			throw new \InvalidArgumentException("Expecting a String, '{$url}' given.");
		}

	}
}
