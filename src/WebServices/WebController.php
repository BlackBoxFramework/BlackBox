<?php

namespace WebServices;

use Common\Exceptions\BlackBoxException;
use WebServices\Route;
use WebServices\Exceptions\HaltException;

/**
 * The "Web Controller" deals specifically with regular HTTP requests
 * made through a browser. The main function of the web controller
 * is to resolve the current request using the `router` and then to
 * load applicable `filters` and `models` into the view.
 *
 * @author James Pegg <jamescpegg@gmail.com>
 * 
 * @todo  Support for `filters`
 * @todo  Support for `models`
 * @todo  Support for templating
 * @todo  Support for caching (presumably in memory)
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
	private $route;

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
		$route 		= $this->resolveRequest(REQUEST_URI);
		$filters 	= $this->loadFilters($route);
		//$models 	= $this->loadModels($route);
		$view 		= $this->makeView($route);
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
			return $route;
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
				$filter = ucfirst(strtolower($filter)) . 'Filter';

				if (!is_subclass_of($filter, '\Common\Filter')) {
					throw new BlackBoxException(BlackBoxException::FILTER_IMPLEMENTATION, ['class' => $filter]);
				}

				$filters[] = new $filter();
			}	
		}

		return $filters;
	}

	/**
	 * Returns an array of models
	 * @param  stdClass $route
	 * @return array
	 * @throws BlackBoxException If a model does not extend abstract Model class
	 *
	 * @todo  This is currently a proof of concept and isn't much use until we have a ORM
	 */
	private function loadModels(\stdClass $route)
	{
		$models = [];

		if (isset($route->models)) {
			foreach ($route->models as $model) {

				$variables = [];
				$pattern = '#^(?P<model>\w+)*\((\w+)(,\w+)*\)$#';
				
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

				$model = ucfirst(strtolower($model)) . 'Model';

				if (!is_subclass_of($model, '\Common\Model')) {
					throw new BlackBoxException(BlackBoxException::MODEL_IMPLEMENTATION, ['class' => $model]);
				}

				$models[] = $model::find($variables);
			}
		}

		return $models;
	}

	/**
	 * Load the route template
	 * @param  stdClass $route
	 * @throws HaltException If template doesn't exist
	 *
	 * @todo  Create a View class and use that instead.
	 */
	private function makeView(\stdClass $route)
	{
		if (isset($route->template)) {
			$template = TEMPLATE_DIR . DIRECTORY_SEPARATOR . str_replace('.', DIRECTORY_SEPARATOR, $route->template) . '.php';

			if (is_readable($template)) {
				require $template;
			} else {
				throw new HaltException(HaltException::NOTFOUND);
			}
		}
	}

}