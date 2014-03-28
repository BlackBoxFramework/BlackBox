<?php

namespace Common\ActiveRecord;

use Common\ObjectContainer;
use Common\ActiveRecord\ModelCollection;
use Common\Event;

/**
 * Abstract Model Class
 *
 * @author James Pegg <jamescpegg@gmail.com>
 */
abstract class Model
	extends Event
{
	/**
	 * Database table
	 * @var string
	 */
	protected static $table 	= null;

	/**
	 * Table indexes
	 * @var array
	 */
	protected static $indexes 	= [];

	/**
	 * Protected variables
	 * @var array
	 */
	protected static $protected = [];

	/**
	 * Required variables
	 * @var array
	 */
	protected static $required  = [];

	/**
	 * Set variables
	 * @var array
	 */
	protected 		 $variables = [];	

	/**
	 * Fetch the current connection
	 * @return \MongoClient
	 */
	final private static function connection()
	{
		return ObjectContainer::getObject('MongoDatabase');
	}

	/**
	 * Find a model based on provided constraints
	 * @param  array $constraints
	 * @return ModelCollection
	 * @throws InvalidArgumentException If model table is empty or null
	 */
	final public static function find(array $constraints = []) 
	{
		// Variables
		$database = self::connection();

		// Trigger Event
		self::trigger('find', $constraints);		

		if (!is_null(static::$table)) {
			$cursor = $database->{static::$table}->find($constraints);

			$models = new ModelCollection();

			foreach ($cursor as $data) {
				if (!is_null($data)) {
					$models[] = new static($data);
				}			
			}

			return $models;
		} else {
			throw new \InvalidArgumentException('Model table can not be empty or null.');
		}
	}

	/**
	 * Saves current state of the model
	 * @throws InvalidArgumentException If model table is empty or null
	 */
	final public function save()
	{
		// Variables
		$database = self::connection();

		// Trigger Event
		self::trigger('save', [$this]);

		if (!is_null(static::$table) &&
			empty(array_diff_key(array_flip(static::$required), $this->variables))) {
			$database->{static::$table}->save($this->variables);
		} else {
			throw new \InvalidArgumentException('Model table can not be empty or null.');
		}
	}

	/**
	 * Deletes model from database
	 * @throws InvalidArgumentException If model table is empty or null
	 */
	final public function delete()
	{
		// Variables
		$database = self::connection();

		// Trigger Event
		self::trigger('delete', [$this]);		

		if (!is_null(static::$table) && isset($this->_id)) {
			$database->{static::$table}->remove(['_id' => $this->id()]);
		} else {
			throw new \InvalidArgumentException('Model table can not be empty or null.');
		}	
	}

	/**
	 * Returns the model's ID
	 * @return string
	 */
	final public function id()
	{
		return $this->variables['_id'];
	}

	/**
	 * Enforces all the index rules of the model
	 */
	final public static function enforceIndexes()
	{
		// Variables
		$database = self::connection();

		$database->{static::$table}->deleteIndexes();

		foreach (static::$indexes as $index => $opts) {

			if (is_numeric($index)) {
				$index = $opts;
				$opts = [];
			}

			$index = [$index => 1];

			$opts = array_fill_keys(array_keys(array_flip($opts)), 1);

			if (is_string($index) || is_array($index)) {
				$database->{static::$table}->ensureIndex($index, $opts);
			}
		}
	}	

	/**
	 * Model constructor
	 * @param array $properties Properties to be applied
	 */
	final public function __construct(array $properties = [])
	{
		foreach ($properties as $key => $value) {
			$this->variables[$key] = $value;
		}
	}

	/**
	 * Returns a model property or linked object
	 * @param  string $name
	 * @return mixed
	 * @throws InvalidArgumentException If arguments are invalid
	 */
	public function __get($name)
	{
		if (in_array($name, static::$protected) && $this->isPublic($name)) {
			throw new \LogicException("Variable {$name} is protected.");
		}

		if (is_string($name) && isset($this->variables[$name])) {

			$model = ucfirst($name) . 'Model';

			// Fetch a reference / relationships
			if ($this->variables[$name] instanceof \MongoId) {
				return $model::find(['_id' => $this->variables[$name]]);
			} elseif (is_array($this->variables[$name]) && array_search_object($this->variables[$name], '\MongoId')) {
				return $model::find(['_id' => ['$in' => $this->variables[$name]]]);
			}

			return $this->variables[$name];
		} else {
			throw new \InvalidArgumentException("Expecting a String, '{$name}' given.");
		}
	}

	/**
	 * Sets a model property or object relationship
	 * @param string $name
	 * @param mixed $value
	 * @throws InvalidArgumentException If arguments are invalid
	 */
	public function __set($name, $value)
	{
		// Create a reference / relationship if setting a model
		if (is_string($name) && $this->isPublic($name)) {

			if (is_object($value)) {
				if (is_subclass_of($value, __CLASS__)) {
					$value = $value->id();
				} elseif ($value instanceof ModelCollection) {
					$models = [];

					foreach ($value as $model) {
						$models[] = $model->id();
					}

					$value = $models;
				}
			}
		
			$this->variables[$name] = $value;
		} else {
			throw new \InvalidArgumentException("Expecting a String, '{$name}' given.");
		}
	}

	/**
	 * Returns true if variable is set
	 * @param  string  $name
	 * @return boolean
	 * @throws InvalidArgumentException If arguments are invalid
	 */
	public function __isset($name)
	{
		if (is_string($name) && isset($this->variables[$name])) {
			return true;
		} else {
			throw new \InvalidArgumentException("Expecting a String, '{$name}' given.");
		}
	}

	/**
	 * Unsets a variable
	 * @param string $name
	 * @throws InvalidArgumentException If arguments are invalid
	 */
	public function __unset($name)
	{
		if (is_string($name) && isset($this->variables[$name]) && $this->isPublic($name)) {
			unset($this->variables[$name]);
		} else {
			throw new \InvalidArgumentException("Expecting a String, '{$name}' given.");
		}
	}

	/**
	 * Returns true of variable is NOT protected
	 * @param  string  $name
	 * @return boolean
	 */
	private function isPublic($name)
	{
		if (in_array($name, static::$protected)) {
			throw new \LogicException("Variable {$name} is protected.");
		} else {
			return true;
		}	
	}

	/**
	 * Method Filter Defaults
	 * @return array
	 */
	public static function getFilters() 	{ return []; }
	public static function postFilters() 	{ return []; }
	public static function deleteFilters() 	{ return []; }
	public static function putFilters() 	{ return []; }
}