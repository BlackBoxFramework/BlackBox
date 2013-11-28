<?php

namespace Common\Traits;

/**
 * Basic getter & setter pattern
 */
trait Mutators{
	/**
	 * Prevent arbitrary setting of object properties
	 * @param string $name
	 * @param mixed $value
	 */
	public function __set($name, $value)
	{
		return $this->propertyMethod('set', $name, $value);
	}

	/**
	 * Prevent arbitrary access to object properties
	 * @param  string $name
	 * @return mixed
	 */
	public function __get($name)
	{
		return $this->propertyMethod('get', $name);
	}

	public function __isset($name)
	{
		return $this->propertyMethod('has', $name);
	}

	/**
	 * Checks whether the property method exists.
	 * @param  string $prefix
	 * @param  string $name
	 * @param  mixed $value
	 * @return mixed
	 * @throws LogicException If the method doesn't exist 
	 * @throws InvalidArgumentException If arguments are invalid
	 */
	private function propertyMethod($prefix, $name, $value = null)
	{
		if (is_string($name)) {
			$method = $prefix . ucfirst($name);

			if (method_exists($this, $method)) {
				return $this->$method($value);
			} else {
				throw new \LogicException("Unable to {$name} property.");
			}			
		} else {
			throw new \InvalidArgumentException("Expecting a String, '{$name}' given.");
		}			

	}
}
