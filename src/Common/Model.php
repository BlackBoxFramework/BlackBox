<?php

namespace Common;

use Common\Collection;

/**
 * Abstract Model Class
 *
 * @author James Pegg <jamescpegg@gmail.com>
 */
abstract class Model
	Implements \Common\ModelInterface
{
	protected static $table 	= null;
	protected static $variables = [];
	protected static $indexes 	= [];

	final public static function find(array $query) 
	{
		// Variables
		$database 	= ObjectContainer::getObject('MongoDatabase');
		$model 		= get_called_class();

		if (!is_null(static::$table)) {
			$cursor = $database->{static::$table}->find($query);

			$models = [];

			foreach ($cursor as $data) {
				if (!is_null($data)) {
					$models[] = new $model($data);
				}			
			}

			return new Collection($models);
		}
	}

	final public static function findOne(array $query) 
	{
		// Variables
		$database 	= ObjectContainer::getObject('MongoDatabase');
		$model 		= get_called_class();

		if (!is_null(static::$table)) {
			$data = $database->{static::$table}->findOne($query);

			if (!is_null($data)) {
				return new $model($data);
			} else {
				return false;
			}
		}
	}

	final public function save()
	{
		// Variables
		$database 	= ObjectContainer::getObject('MongoDatabase');

		if (!is_null(static::$table)) {
			$database->{static::$table}->save(self::$variables);
		}
	}

	final public function delete()
	{
		// Variables
		$database 	= ObjectContainer::getObject('MongoDatabase');

		if (!is_null(static::$table) && isset($this->_id)) {
			$database->{static::$table}->remove(['_id' => $this->_id]);
		}		
	}

	final public function __construct(array $properties = [])
	{
		foreach ($properties as $key => $value) {
			$this->$key = $value;
		}
	}

	public function __get($name)
	{
		if (is_string($name) && isset(static::$variables[$name])) {
			return static::$variables[$name];
		}
	}

	public function __set($name, $value)
	{
		if (is_string($name)) {
			static::$variables[$name] = $value;
		}
	}

	public function __isset($name)
	{
		if (is_string($name) && isset(static::$variables[$name])) {
			return true;
		}
	}
}