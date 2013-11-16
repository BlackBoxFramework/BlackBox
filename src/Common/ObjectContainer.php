<?php

namespace Common;

/**
 * The Object Container is the framework's IoC
 *
 * @author James Pegg <jamescpegg@gmail.com>
 *
 * @todo  Implement object storage
 * @todo  Implement dependency injection
 */
class ObjectContainer
{
	use \Common\Traits\Singleton;

	/**
	 * Array of objects
	 * @var array
	 */
	private static $objects = [];

	/**
	 * Get an object
	 * @param  string $name
	 * @return object
	 * @throws Exception If object isn't stored
	 */
	public function getObject($name)
	{
		if (isset(self::$objects[$name])) {
			return self::$objects[$name];
		} else {
			throw new \Exception('Object is not stored', 1);
		}
	}

	/**
	 * Store an object
	 * @param string $name   
	 * @param object $object
	 */
	public function setObject($name, $object) {
		if (is_string($name) &&
			(is_object($object) ||
			is_array($object))) {
			self::$objects[$name] = $object;
		}
	}

	/**
	 * Checks if object is set
	 * @param  string  $name
	 * @return boolean
	 */
	public function hasObject($name) {
		if (isset(self::$objects[$name])) {
			return true;
		} else {
			return false;
		}		
	}
}
