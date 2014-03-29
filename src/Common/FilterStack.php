<?php

namespace Common;

use Common\Exceptions\BlackBoxException;

/**
 * Abstract Filter Class
 *
 * @author James Pegg <jamescpegg@gmail.com>
 */
abstract class FilterStack
{
	/**
	 * Array of registered filters
	 * @var array
	 */
	private static $filters = [];
	
	/**
	 * Add a new filter to the stack (a.k.a register a filter)
	 * @param string $filter
	 */
	public static function add($filter)
	{
		$filter = 'Filter\\' . ucfirst(strtolower($filter));

		if (!is_subclass_of($filter, '\Common\Filter')) {
			throw new BlackBoxException(BlackBoxException::FILTER_IMPLEMENTATION, ['class' => $filter]);
		}

		if (!in_array($filter, self::$filters)) {
			self::$filters[] = $filter;
		}
	}

	/**
	 * Remove a filter from the stack
	 * @param  string $filter
	 */
	public static function remove($filter)
	{
		if ($key = array_search($filter, self::$filters)) {
			unset(self::$filters[$key]);
		}
	}

	/**
	 * Process the stack and initiate all the filters.
	 * Returns an array with all the filter handles.
	 * @return array
	 */
	public static function process()
	{
		$filters = [];
		
		foreach (self::$filters as $filter) {

			$filter = new $filter();

			$filter->boot();

			$filters[] = $filter;
		}

		return $filters;
	}

}