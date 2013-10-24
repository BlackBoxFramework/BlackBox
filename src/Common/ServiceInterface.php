<?php

namespace Common;

/**
 * Service Interface
 *
 * @author James Pegg <jamescpegg@gmail.com>
 */
interface ServiceInterface
{
	public function __construct(\stdClass $routes, \stdClass $config);
	public function run();
}