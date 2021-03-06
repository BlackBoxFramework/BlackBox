<?php

namespace Common;

/**
 * Core Event Dispatcher
 *
 * @author James Pegg <jamescpegg@gmail.com>
 */
class Event
{
	/**
	 * Events Stack
	 * @var array
	 */
	static private $events = [];

	/**
	 * Hook in a new event callback
	 * @param  string  $event    
	 * @param  Closure $callback
	 * @throws InvalidArgumentException If arguments are invalid
	 */
	final public static function hook($event, \Closure $callback)
	{
		if (get_called_class() != 'Event') {
			$event = strtolower(str_replace('\\', '.', get_called_class())) . '.' . $event;
		}

		if (is_string($event)) {
			self::$events[$event][] = $callback;
		} else {
			throw new \InvalidArgumentException("Expecting a String, '{$event}' given.");
		}
	}

	/**
	 * Trigger an event
	 * @param  string $event
	 * @param  array $args
	 */
	final protected static function trigger($event, array $args = [])
	{
		$event = strtolower(str_replace('\\', '.', get_called_class())) . '.' . $event;

		if (isset(self::$events[$event])) {
			foreach (self::$events[$event] as $callback) {
				call_user_func_array($callback, $args);
			}
		}

		foreach (self::$events as $pattern => $callbacks) {
			if (fnmatch($pattern, $event)) {
				foreach ($callbacks as $callback) {
					call_user_func_array($callback, $args);
				}
			}
		}
	}	
}
