<?php

namespace WebServices;

use WebServices\Exceptions\HaltException;
use WebServices\Compiler;
use Common\Cache;

/**
 * The view is in charge of compiling and showing the template
 *
 * @author James Pegg <jamescpegg@gmail.com>
 */
class View
{
	/**
	 * Current template
	 * @var string
	 */
	private $template = '';

	/**
	 * Sets the template and any provided data (e.g. models)
	 * @param string $template
	 * @param array  $data
	 */
	public function __construct($template, array $data)
	{
		if (is_string($template)) {
			$this->template = $template;
			
			foreach ($data as $key => $value) {
				$this->$key = $value;
			}			
		} else {
			throw new \InvalidArgumentException("Expecting a String, '{$template}' given.");
		}


	}

	/**
	 * Shows the template file. Checks cache for a cached version,
	 * otherwise it compiles the template.
	 * @return null
	 */
	public function show()
	{
		$template = $this->template;
		$template = str_replace('.', DIRECTORY_SEPARATOR, $template) . '.php';
		$template = TEMPLATE_DIR . DIRECTORY_SEPARATOR . $template;

		if (is_readable($template)) {

			$cache = new Cache($this->template, filemtime($template));

			//if (!isset($cache->content)) {
				$compiler = new Compiler($cache);

				$cache->content = $compiler->parse(file_get_contents($template));
			//}

			require $cache->path;
		} else {
			throw new HaltException(HaltException::NOTFOUND);
		}
	}
}
