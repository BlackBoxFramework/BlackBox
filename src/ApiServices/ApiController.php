<?php

namespace ApiServices;

use \Exception;
use Common\FilterStack;
use Common\Router;
use Common\Redirect;
use Input;

/**
 * API Controller
 *
 * @author James Pegg <jamescpegg@gmail.com>
 *
 * @todo rate limiting
 * @todo caching
 */
class ApiController
	implements \Common\ServiceInterface
{
	/**
	 * Currently displayed page. Default 1
	 * @var integer
	 */
    private $page       = 1;

    /**
     * Number of skipped records
     * @var integer
     */
    private $skip       = 0;

    /**
     * Page length
     * @var integer
     */
    private $length     = 10;

    /**
     * Result sorting
     * @var array
     */
    private $sort       = [];

    /**
     * Model constraints
     * @var array
     */
    private $find       = [];

    /**
     * Search constraints
     * @var array
     */
    private $search     = [];

    /**
     * Redirect URL
     * @var string
     */
	private $redirect;

	/**
	 * Loads initial API wide filters and processes a few
	 * standard input variables.
	 * @param stdClass $routes 
	 * @param stdClass $config
	 */
	public function __construct(\stdClass $routes, \stdClass $config)
	{
		// Register API wide filters
		if (isset($config->api_filters)) {
			foreach ($config->api_filters as $filter) {
				FilterStack::add($filter);
			}
		}

		// Save input variables
		if (Input::has('find')) {
			$this->find = Input::get('find');
		}

		if (Input::has('search')) {
			$this->search = Input::get('search');
		}

		if (Input::has('page')) {
			$this->page = Input::integer('page');
			$this->skip = $this->length * ($this->page - 1);
		}

		if (Input::has('sort')) {
			$this->sort = Input::get('sort');
		}

		if (Input::has('redirect')) {
			$this->redirect = Input::get('redirect');
			Input::delete('redirect');
		}		

	}

	/**
	 * Run the service
	 * @return null
	 */
	public function run()
	{
		if ($route = $this->resolveRoute(REQUEST_URI)) {
			$method = $route->method;
			$model = 'Model\\' . ucfirst(strtolower($route->variables['model']));

			if (!is_subclass_of($model, '\Common\ActiveRecord\Model')) {
				$this->error('Object does not exist.');
			}

			// Authenticate the service
			if (!$model::auth('api')) {
				$this->error('Model not accesible via API');
			}

			$this->loadFilters($model);

			// Execute the route
			$this->$method($model, $route->variables);
		}		
	}

	/**
	 * Sets up the api URL structure and resolves the route
	 * @param  string $request
	 * @return object
	 */
	private function resolveRoute($request)
	{
		$router = new Router();

		$router->addRoute('/api/:model', 				(object) ['method' => 'listModel'], ['GET']);
		$router->addRoute('/api/:model/:id', 			(object) ['method' => 'getModel'], ['GET']);
		$router->addRoute('/api/:model', 				(object) ['method' => 'createModel'], ['POST']);
		$router->addRoute('/api/:model/:id', 			(object) ['method' => 'updateModel'], ['PUT']);
		$router->addRoute('/api/:model/:id', 			(object) ['method' => 'deleteModel'], ['DELETE']);

		return $router->resolve($request);
	}

	/**
	 * Loads and runs model filters
	 * @param  object $model
	 * @return null
	 */
	private function loadFilters($model)
	{
		$filterMethod = strtolower(METHOD) . 'Filters';

		foreach ($model::$filterMethod() as $filter) {
			FilterStack::add($filter);
		}

		FilterStack::process();
	}

	/**
	 * Redirects the API request
	 * @return null
	 */
	private function redirect()
	{
		if (!empty($this->redirect)) {
			Redirect::temporary($this->redirect, true,  FILTER_FLAG_HOST_REQUIRED);
		}
	}

	/**
	 * Respond with an error
	 * @param  string $error
	 * @return null
	 */
	private function error($error)
	{
		http_response_code(404);
		$this->response(['error' => $error]);
		die();
	}

	/**
	 * Responds with JSON encoded data
	 * @param  mixed $data
	 * @return null
	 */
	private function response($data)
	{
		echo json_encode($data, JSON_PRETTY_PRINT);
	}

	/**
	 * -----------------------------------------------------------------
	 * Route Methods
	 * -----------------------------------------------------------------
	 */

	/**
	 * List models
	 * @param  string $model
	 * @param  array $variables
	 * @return null
	 */
	private function listModel($model, $variables)
	{
		try {

			$find = $this->find;

			if (!empty($this->search)) {
				foreach ($this->search as $key => $pattern) {
					$find[$key] = new \MongoRegex($pattern);
				}
			}

			$model = $model::find($find)->limit($this->length)
										->skip($this->skip)
										->sort($this->sort)
										->fetch();

			$this->response($model);
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
	}

	/**
	 * Show a specific model
	 * @param  string $model
	 * @param  array $variables
	 * @return null
	 */
	private function getModel($model, $variables)
	{
		try {
			$model = $model::get($variables['id']);
			$this->response($model);
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
	}

	/**
	 * Create a new model
	 * @param  string $model
	 * @param  array $variables
	 * @return null
	 */
	private function createModel($model, $variables)
	{
		try {
			$model = new $model(Input::all());
			$model->save();
			$this->redirect();
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
	}

	/**
	 * Update an existing model
	 * @param  string $model
	 * @param  array $variables
	 * @return null
	 */
	private function updateModel($model, $variables)
	{
		try {
			$model = $model::get($variables['id']);

			foreach (Input::all() as $property => $value) {
				$model->$property = $value;
			}

			$model->save();

			$this->redirect();
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
	}

	/**
	 * Delete a model
	 * @param  string $model
	 * @param  array $variables
	 * @return null
	 */
	private function deleteModel($model, $variables)
	{
		try {
			$model = $model::get($variables['id']);
			$model->delete();
			$this->redirect();
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
	}

}