<?php

namespace Common\Traits;

/**
 * The singleton traits allows for easy implementation
 * of the singleton pattern.
 *
 * @author James Pegg <jamescpegg@gmail.com>
 */
trait Singleton{
	protected static $instance = NULL;

	final private function __construct(){}

	final public function getInstance()
	{
		if (is_null(self::$instance)) {
			$class = __CLASS__;
			self::$instance = new $class;
		}

		return self::$instance;
	}
}
