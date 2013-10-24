<?php

namespace WebServices\Exceptions;

use \Exception;

/**
 * The HaltException is a standard way of implementing 404 errors
 *
 * @author James Pegg <jamescpegg@gmail.com>
 */
class HaltException
	extends Exception
{
	const NOTFOUND = 404;

	public function __construct($type, Exception $previous = NULL)
	{
		switch ($type) {
			case self::NOTFOUND:
				
				http_response_code(404);
				die();

				break;
			
			default:
				# code...
				break;
		}
		parent::__construct($message, 1, $previous);
	}

	public function __toString() {
		return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
	}
}
