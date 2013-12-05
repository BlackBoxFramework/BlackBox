<?php

namespace Common\ActiveRecord;

/**
 * Model Collection
 *
 * @author James Pegg <jamescpegg@gmail.com>
 */
class ModelCollection
	extends \Common\Collection
{
	/**
	 * Set property on all models
	 * @param string $name
	 * @param mixed $value
	 * @throws InvalidArgumentException If arguments are invalid
	 */
	public function __set($name, $value)
	{
		if (is_string($name)) {
			foreach ($this as $item) {
				$item->$name = $value;
			}			
		} else {
			throw new \InvalidArgumentException("Expecting a String, '{$name}' given.");
		}

	}

	/**
	 * Get property from all models
	 * @param  string $name
	 * @return array $array
	 * @throws InvalidArgumentException If arguments are invalid
	 */
	public function __get($name)
	{
		if (is_string($name)) {
			$array = [];

			foreach ($this as $item) {
				$array[] = $item->$name;
			}

			return $array;			
		} else {
			throw new \InvalidArgumentException("Expecting a String, '{$name}' given.");
		}

	}

	/**
	 * Unsets property on all models
	 * @param string $name
	 * @throws InvalidArgumentException If arguments are invalid
	 */
	public function __unset($name)
	{
		if (is_string($name)) {
			foreach ($this as $item) {
				unset($item->$name);
			}
		} else {
			throw new \InvalidArgumentException("Expecting a String, '{$name}' given.");
		}
	}

	/**
	 * Saves the current state of all models
	 * @return null
	 */
	public function save()
	{
		foreach ($this as $item) {
			$item->save();
		}
	}

	/**
	 * Deletes all models
	 * @return null
	 */
	public function delete()
	{
		foreach ($this as $key => $item) {
			$item->delete();
			unset($this[$key]);
		}
	}
}
