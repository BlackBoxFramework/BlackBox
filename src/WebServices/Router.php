<?php

namespace WebServices;

use \stdClass;

/**
 * The router's main job is to store all current application
 * routes and then figure out which one matches the request.
 *
 * @author James Pegg <jamescpegg@gmail.com>
 */
class Router
{
	/**
	 * Getters & Setters
	 */
	use \Common\Traits\Mutators;

	/**
	 * Array of routes
	 * @var array
	 */
	private $routes = [];

	/**
	 * Current route
	 * @var array
	 */
	private $route 	= NULL;

	/**
	 * Set the resolved route.
	 * @param stdClass $data
	 */
	public function setRoute(stdClass $data)
	{
		$this->route = $data;
	}

	/**
	 * Returns the resolved route.
	 * @return stdClass
	 */
	public function getRoute()
	{
		return ($this->hasRoute() ? $this->route : false);
	}

	/**
	 * Checks if the route has been set.
	 * @return boolean
	 */
	public function hasRoute()
	{
		return (is_null($this->route) ? false : true);
	}

	/**
	 * Add a route to the stack.
	 * @param string $route_uri
	 * @param object $data
	 */
	public function addRoute($route_uri, stdClass $data, $method = 'GET')
	{
		// Override method if provided in data
		if (isset($data->method)) {
			$method = strtoupper($data->method);
		}

		$this->routes[$method][$route_uri] = $data;

		// Nested routes
		if (isset($data->children)) {

			foreach ($data->children as $child_uri => $child_data) {

				$this->addRoute($route_uri . $child_uri, $child_data);

			}

			// Delete the now unnecessary child data.
			unset($this->routes[$route_uri]->children);
		}
	}

	/**
	 * Find the route which matches the provided URI
	 * @param  string $request_uri
	 * @return stdClass
	 */
	public function resolve($request_uri)
	{
		if (isset($this->routes[METHOD][$request_uri])) {

			// Basic static route was found
			return $this->route = $this->routes[METHOD][$request_uri];

		} else {

			// Loop through each route until one can be found
			foreach ($this->routes[METHOD] as $route => $data) {

				// Only return the route if it's a named route and could be resolved.
				if ((strpos($route, ':')  ||
					 strpos($route, '#')) &&
					 $this->resolveDynamicRoute($route, $data, $request_uri)) {
					return $this->getRoute();
				}
			}

			// Route couldn't be resolved.
			return false;
		}
	}

	/**
	 * Resolve a dynamic route
	 * @param  string $route
	 * @param  stdClass $data
	 * @param  string $request_uri
	 * @return boolean
	 */
	private function resolveDynamicRoute($route, stdClass $data, $request_uri)
	{

		// Split the route into parts
		$parts = array_values(array_filter(explode('/', $route)));

		// Loop through the parts and build a regular expression
		foreach ($parts as $key => $part) {

			switch ($part) {

				// Alphanumeric
				case strpos($part, ':'):
					
					$part = str_replace(':', '', $part);
					$parts[$key] = "(?P<{$part}>[a-z0-9-]*)";

					break;
				
				// Integer Only
				case strpos($part, '#'):
					
					$part = str_replace('#', '', $part);
					$parts[$key] = "(?P<{$part}>\d+)";					

					break;
			}

		}

		// Build regex pattern
		$pattern = '#^\/' . implode('\/', $parts) . '$#';

		preg_match($pattern, $request_uri, $variables);

		// If route matches request, return true
		if ($variables) {

			// Store the variables in the route object
			foreach ($variables as $key => $value) {
				if (is_int($key)) {
					unset($variables[$key]);
				}
			}

			$data->variables = $variables;

			$this->setRoute($data);

			return true;
		}

		return false;

	}
}
