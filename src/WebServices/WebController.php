<?php

namespace WebServices;

use Common\Exceptions\BlackBoxException;
use Common\FilterStack;
use Common\Router;
use Common\ObjectContainer;
use WebServices\Assets;
use WebServices\Redirect;
use WebServices\Route;
use WebServices\View;
use WebServices\Exceptions\HaltException;

/**
 * The "Web Controller" deals specifically with regular HTTP requests
 * made through a browser. The main function of the web controller
 * is to resolve the current request using the `router` and then to
 * load applicable `filters` and `models` into the view.
 *
 * @author James Pegg <jamescpegg@gmail.com>
 */
class WebController
	implements \Common\ServiceInterface
{

	/**
	 * Application routes
	 * @var stdClass
	 */
	private $routes;

	/**
	 * Resolved route
	 * @var stdClass
	 */
	public $route;

	/**
	 * Application settings
	 * @var stdClass
	 */
	private $config;

	/**
	 * Sets routes and config variables
	 * @param stdClass $routes
	 * @param stdClass $config
	 */
	public function __construct(\stdClass $routes, \stdClass $config)
	{
		$this->routes = $routes;
		$this->config = $config;
	}

	/**
	 * Run the service
	 * @return null
	 */
	public function run()
	{

		if (!$this->redirects(REQUEST_URI)) {

			$route 		= $this->resolveRequest(REQUEST_URI);
			$models 	= $this->loadModels($route);
			$filters 	= $this->loadFilters($route);
			$view 		= $this->makeView($route, $models, $filters);

			$this->assets($view->compiler);

			$view->show();
		}

	}

	/**
	 * Compiles project assets
	 * @return null
	 */
	private function assets(\WebServices\Compiler $compiler)
	{
		if (isset($this->config->assets)) {
			$manager = Assets::getInstance();
			$manager->registerPattern($compiler);
			$manager->compile($this->config->assets);			
		}
	}

	/**
	 * Automatic Redirects
	 * @param  string $request
	 * @return bool
	 */
	private function redirects($request)
	{
		if (is_readable(PROJECT_DIR . '/redirect.json')) {
			$redirects = json_get_contents(PROJECT_DIR . '/redirect.json', true, true);

			Redirect::fromArray($redirects, $request);
		}

		return false;
	}

	/**
	 * Resolve a given request URI
	 * @param  string $request
	 * @return stdClass
	 * @throws HaltException If router is unable to resolve request
	 */
	private function resolveRequest($request)
	{
		$router = new Router();

		foreach ($this->routes as $route_uri => $data) {
			$router->addRoute($route_uri, $data);
		}

		if (!$route = $router->resolve($request)) {
			throw new HaltException(HaltException::NOTFOUND);
		} else {
			return $this->route = $route;
		}
	}

	/**
	 * Returns an array of filters
	 * @param  stdClass $route
	 * @return array
	 * @throws BlackBoxException If a filter does not extend abstract Filter class
	 */
	private function loadFilters(\stdClass $route)
	{
		$filters = [];

		if (isset($route->filters)) {
			foreach ($route->filters as $filter) {
				FilterStack::add($filter);
			}	
		}

		return FilterStack::process();
	}

	/**
	 * Returns an array of models
	 * @param  stdClass $route
	 * @return array
	 * @throws BlackBoxException If a model does not extend abstract Model class
	 */
	private function loadModels(\stdClass $route)
	{
		$models = [];

		if (isset($route->models)) {
			foreach ($route->models as $model) {

				// Modifiers
				$modifiers = [];
				$pattern = '#\.(\w+)*#';

				preg_match_all($pattern, $model, $modifiers);

				if (!empty($modifiers)) {
					$model = str_replace($modifiers[0] , '', $model);
					unset($modifiers[0]);
				}

				$modifiers = $modifiers[1];

				// Variables
				$variables = [];
				$pattern = '#^(?P<model>\w+)*\((\w+)(,\w+)*\)#';
				
				preg_match($pattern, $model, $matches);

				if ($matches) {
					$model = $matches['model'];

					unset($matches['model']);
					unset($matches[0]);
					unset($matches[1]);

					foreach ($matches as $match) {
						$variables[$match] = $route->variables[$match];
					}
				}

				$class = 'Model\\' . ucfirst(strtolower($model));

				if (!is_subclass_of($class, '\Common\ActiveRecord\Model')) {
					throw new BlackBoxException(BlackBoxException::MODEL_IMPLEMENTATION, ['class' => $model]);
				}

				$object = $class::find($variables);

				foreach ($modifiers as $modifier) {
					$object = $object->$modifier();
				}

				$models[strtolower(ucfirst($model))] = $object->fetch();

				// Model Filters
				$filterMethod = strtolower(METHOD) . 'Filters';

				foreach ($class::$filterMethod() as $filter) {
					FilterStack::add($filter);
				}				
			}
		}

		// Store Models
		ObjectContainer::setObjects($models);	
		
		return $models;
	}

	/**
	 * Load the route template
	 * @param  stdClass $route
	 * @throws HaltException If template doesn't exist
	 */
	private function makeView(\stdClass $route, array $models, array $filters)
	{
		if (isset($route->template)) {
			$view = new View($route->template, $models + $filters);

			ObjectContainer::setObject('View', $view);

			return $view;
		}
	}

}