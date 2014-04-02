<?php 

namespace Common\ActiveRecord;

/**
 * MongoCursor Wrapper
 *
 * @author James Pegg <jamescpegg@gmail.com>
 */
class ModelCursor
{
	/**
	 * Model
	 * @var string
	 */
	private $model;

	/**
	 * MongoCursor
	 * @var \MongoCursor
	 */
	private $cursor;

	/**
	 * Class constructor
	 * @param string      $model
	 * @param MongoCursor $cursor
	 */
	public function __construct($model, \MongoCursor $cursor)
	{
		$this->model = $model;
		$this->cursor = $cursor;
	}

	/**
	 * Makes MongoCursor methods available.
	 * @param  string $method
	 * @param  array $arguments
	 * @return $this
	 */
	final public function __call($method, $arguments)
	{
		if (method_exists($this->cursor, $method)) {

			call_user_func_array([$this->cursor, $method], $arguments);

			return $this;
		}
	}

	/**
	 * Fetches data and returns a ModelCollection
	 * @return \Common\ActiveRecord\ModelCollection
	 */
	final public function fetch()
	{
		$model = $this->model;

		$models = new ModelCollection();

		foreach ($this->cursor as $data) {
			if (!is_null($data)) {
				$models[] = new $model($data);
			}			
		}

		return $models;			
	}

	/**
	 * Helper function for returning the first item
	 * @return \Common\ActiveRecord\ModelCollection
	 */
	final public function first()
	{
		return $this->limit(1)->fetch()[0];
	}
}