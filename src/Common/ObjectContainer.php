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
	public static function getObject($name)
	{
		if (isset(self::$objects[$name])) {
			return self::$objects[$name];
		} else {
			throw new \Exception("Object {$name} is not available", 1);
		}
	}

	/**
	 * Store an object
	 * @param string $name   
	 * @param object $object
	 */
	public static function setObject($name, $object) {
		if (is_string($name) &&
			(is_object($object) ||
			is_array($object))) {
			self::$objects[$name] = $object;
		}
	}

	public static function setObjects(array $objects) {

		foreach ($objects as $name => $object) {
			self::setObject($name, $object);
		}
	}

	/**
	 * Checks if object is set
	 * @param  string  $name
	 * @return boolean
	 */
	public static function hasObject($name) {
		if (isset(self::$objects[$name])) {
			return true;
		} else {
			return false;
		}		
	}
}
