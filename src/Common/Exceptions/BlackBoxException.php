<?php

namespace Common\Exceptions;

use \Exception;

/**
 * Handles all sorts of framework errors.
 *
 * @author James Pegg <jamescpegg@gmail.com>
 */
class BlackBoxException
	extends Exception
{
	const FILTER_IMPLEMENTATION = 'There was a problem implementing the following class ; {class}. Please read the documentation carefully.';

	public function __construct($message, array $context = [], Exception $previous = NULL)
	{
		parent::__construct(interpolate($message, $context), 1, $previous);
	}

	public function __toString() {
		return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
	}
}
