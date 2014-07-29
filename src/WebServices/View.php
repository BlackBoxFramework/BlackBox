<?php

namespace WebServices;

use WebServices\Exceptions\HaltException;
use WebServices\Compiler;
use Common\Cache;
use Common\Event;

/**
 * The view is in charge of compiling and showing the template
 *
 * @author James Pegg <jamescpegg@gmail.com>
 */
class View
	extends \Common\Event
{
	/**
	 * Current template
	 * @var string
	 */
	private $template = '';

	/**
	 * Template compiler instance
	 * @var \WebServices\Compiler
	 */
	public $compiler;

	/**
	 * Stored View Properties
	 * @var array
	 */
	private $properties = [];

	/**
	 * Sets the template and any provided data (e.g. models)
	 * @param string $template
	 * @param array  $data
	 * @throws InvalidArgumentException If arguments are invalid
	 */
	public function __construct($template, array $data)
	{
		if (is_string($template)) {
			$this->template = $template;
			
			foreach ($data as $key => $value) {
				$this->$key = $value;
			}

			$this->compiler = new Compiler();
		} else {
			throw new \InvalidArgumentException("Expecting a String, '{$template}' given.");
		}
	}

	/**
	 * Set a view property
	 * @param string $name
	 * @param mixed $value
	 */
	public function __set($name, $value)
	{
		$this->properties[$name] = $value;
	}

	/**
	 * Returns a view property
	 * @param  string $name
	 * @return mixed       
	 */
	public function __get($name)
	{
		return ($this->__isset($name) ? $this->properties[$name] : false);
	}

	/**
	 * Checks if a property is set
	 * @param  string  $name
	 * @return boolean
	 */
	public function __isset($name)
	{
		return (is_null($this->properties[$name]) ? false : true);
	}

	/**
	 * Shows the template file. Checks cache for a cached version,
	 * otherwise it compiles the template.
	 * @return null
	 * @throws HaltException If template is unreadable
	 */
	public function show()
	{
		$template = template_file($this->template);

		// Trigger Event
		self::trigger('show', [$this->template]);		

		if (is_readable($template)) {

			$cache = new Cache($this->template, filemtime($template));

			if (!isset($cache->content)) {
				$this->compiler->setCache($cache);

				$cache->content = $this->compiler->parse(file_get_contents($template));
			}


			// Sandbox the template
			call_user_func_array(function($file, array $properties) {

				foreach ($properties as $name => $value) {
					$$name = $value;
				}

				require $file;
			}, [$cache->path, $this->properties]);

		} else {
			throw new HaltException(HaltException::NOTFOUND);
		}
	}
}
