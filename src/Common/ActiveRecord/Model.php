<?php

namespace Common\ActiveRecord;

use Common\ObjectContainer;
use Common\ActiveRecord\ModelCollection;
use Common\ActiveRecord\ModelCursor;
use Common\Event;

/**
 * Abstract Model Class
 *
 * @author James Pegg <jamescpegg@gmail.com>
 */
abstract class Model
	extends Event
	implements \JsonSerializable
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
	 * Fetch the current connection
	 * @return \MongoClient
	 */
	final private static function connection()
	{
		return ObjectContainer::getObject('MongoDatabase');
	}

	/**
	 * -----------------------------------------------------------------
	 * Model Creation
	 * -----------------------------------------------------------------
	 */

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

		foreach ($constraints as $key => $constraint) {
			if (in_array($key, static::$protected)) {
				throw new \InvalidArgumentException('Can\'t search Model by protected variable.');
			}
		}

		if (!is_null(static::$table)) {
			$cursor = $database->{static::$table}->find($constraints);

			return new ModelCursor(get_called_class(), $cursor);
		} else {
			throw new \InvalidArgumentException('Model table can not be empty or null.');
		}
	}

	final public static function get($id)
	{
		return self::find(['_id' => new \MongoId($id)])->limit(1)->fetch()[0];
	}		

	/**
	 * -----------------------------------------------------------------
	 * Object Persistence
	 * -----------------------------------------------------------------
	 */

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

		// Set admin variables
		$this->updated = new \MongoDate(time());

		if (!$this->hasId()) {
			$this->created = new \MongoDate(time());
		}

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
	 * -----------------------------------------------------------------
	 * Magic Methods
	 * -----------------------------------------------------------------
	 */

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
	 * -----------------------------------------------------------------
	 * Helper Methods
	 * -----------------------------------------------------------------
	 */

	/**
	 * Returns the model's ID
	 * @return string
	 */
	final public function id()
	{
		return $this->variables['_id'];
	}

	/**
	 * Returns true if ID is set
	 * @return boolean
	 */
	final public function hasId()
	{
		return isset($this->variables['_id']);
	}

	/**
	 * Returns model updated time as a 
	 * DateTime object
	 * @return DateTime
	 */
	final public function updated()
	{
		return new \DateTime("@{$this->updated->sec}");
	}

	/**
	 * Returns model created time as a 
	 * DateTime object
	 * @return DateTime
	 */
	final public function created()
	{
		return new \DateTime("@{$this->created->sec}");
	}

	/**
	 * Returns true of variable is NOT protected
	 * @param  string  $name
	 * @return boolean
	 */
	final private function isPublic($name)
	{
		if (in_array($name, static::$protected)) {
			return false;
		} else {
			return true;
		}	
	}

	/**
	 * Workaround for when object has already been fetched.
	 * @return \Common\ActiveRecord\Model
	 */
	final public function fetch()
	{
		return $this;
	}


	/**
	 * -----------------------------------------------------------------
	 * Serialisation
	 * -----------------------------------------------------------------
	 */
	
	public function jsonSerialize()
	{
		$array = [];

		foreach ($this->variables as $name => $value) {
			if ($this->isPublic($name)) {
				$array[$name] = $value;
			}
		}

		return $array;
	}

	/**
	 * -----------------------------------------------------------------
	 * Default Filter Methods
	 * -----------------------------------------------------------------
	 */
	public static function getFilters() 	{ return []; }
	public static function postFilters() 	{ return []; }
	public static function deleteFilters() 	{ return []; }
	public static function putFilters() 	{ return []; }
}