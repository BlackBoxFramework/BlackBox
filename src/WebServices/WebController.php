<?php

namespace WebServices;

use Common\Exceptions\BlackBoxException;
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
	extends \Common\Event
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

		if (!$this->redirects(REQUEST_URI)) {
			$route 		= $this->resolveRequest(REQUEST_URI);
			$filters 	= $this->loadFilters($route);
			$models 	= $this->loadModels($route);
			$view 		= $this->makeView($route, $models, $filters);

			$view->show();
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

			// Trigger Event
			$this->trigger('route.resolved');

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

				// Instantiate Filter
				$filter = new $filter();

				// Boot the filter
				$filter->boot();

				// Make filter accesible to view
				$filters[] = $filter;
			}	
		}

		return $filters;
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

				$class = ucfirst(strtolower($model)) . 'Model';

				if (!is_subclass_of($class, '\Common\ActiveRecord\Model')) {
					throw new BlackBoxException(BlackBoxException::MODEL_IMPLEMENTATION, ['class' => $model]);
				}

				$object = $class::find($variables);

				foreach ($modifiers as $modifier) {
					$object = $object->$modifier();
				}

				$models[$model] = $object;
			}
		}
		
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

			// Trigger Event
			$this->trigger('view.show', [$route->template]);

			return new View($route->template, $models + $filters);
		}
	}

}