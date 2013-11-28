<?php

namespace Common;

/**
 * Abstract Filter Class
 *
 * @author James Pegg <jamescpegg@gmail.com>
 */
abstract class Filter
	extends \Common\Event
{
	abstract public function boot();
}