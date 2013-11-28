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
	 */
	final protected function hook($event, \Closure $callback)
	{
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
	final protected function trigger($event, array $args = [])
	{
		if (isset(self::$events[$event])) {
			foreach (self::$events[$event] as $callback) {
				call_user_func_array($callback, $args);
			}
		}
	}		
}
