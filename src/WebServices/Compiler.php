<?php

namespace WebServices;

use WebServices\Exceptions\HaltException;

/**
 * The compiler takes a template file and converts it
 * into proper PHP.
 *
 * @author  James Pegg <jamescpegg@gmail.com>
 */
class Compiler
{
	/**
	 * Cache instance
	 * @var \Common\Cache
	 */
	private $cache;

	/**
	 * Set the cache instance
	 * @param \Common\Cache $cache
	 */
	public function setCache(\Common\Cache $cache)
	{
		$this->cache = $cache;
	}

	/**
	 * Parse provided content
	 * @param  string $content
	 * @throws InvalidArgumentException If arguments are invalid
	 * @return string
	 */
	public function parse($content)
	{
		if (is_string($content)) {
			foreach ($this->patterns as $pattern => $callback) {
				$content =  preg_replace_callback($pattern, $callback, $content);
			}

			return $content;			
		} else {
			throw new \InvalidArgumentException("Expecting a String, '{$content}' given.");
		}

	}

	/**
	 * Register an additional pattern
	 * @param  string $pattern 
	 * @param  mixed $callback
	 * @return null
	 */
	public function register($pattern, $callback)
	{
		if (is_string($pattern)) {
			$this->patterns[$pattern] = $callback;
		} else {
			throw new \InvalidArgumentException("Expecting a String, '{$pattern}' given.");
		}	
	}

	/**
	 * Compiled sections
	 * @var array
	 */
	private $sections = [];

	/**
	 * Inbuilt patterns and callbacks.
	 * @var array
	 */
	private $patterns = 
	[
		// Control Structures
		'#{(foreach|while)\((\$(\w+))(.*?)\)}(.*?)({else}(.*?))?{/(foreach|while)}#s'	=> [__CLASS__, '_loop'],
		'#{(if|else|elseif|for|switch|case|default)(\((.*?)\))?}((\s|\n|\t)*)?#'		=> [__CLASS__, '_open'],
		'#{/(if|for|switch)}#'															=> [__CLASS__, '_end'],
		'#{/(case|default)}#' 															=> [__CLASS__, '_break'],
		'#{(break|continue)}#'															=> [__CLASS__, '_control'],

		// Variables
		'#{define\(\$(\w+), (.*)\)}#'													=> [__CLASS__, '_define'],
		'#{{(.+?)}}#' 																	=> [__CLASS__, '_echo'],

		// Internal Features
		'#{include\((.*)\)}#' 															=> [__CLASS__, '_include'],
		'#{section\((\w+)\)}(?P<content>.*?){/section}#s' 								=> [__CLASS__, '_section'],
		'#{yield\((\w+)\)}#' 															=> [__CLASS__, '_yield'],
		'#{extend\((.*)\)}#' 															=> [__CLASS__, '_extend']
	];

	/**
	 * Returns a PHP loop. Additionally can check if the variable being looped exists.
	 * @param  array  $matches
	 * @return string
	 */
	private function _loop(array $matches) {
		return 	"<?php if (isset({$matches[2]})):{$matches[1]}({$matches[2]}{$matches[4]}):?>\n" .
				$this->parse($matches[5]) .
				"<?php end{$matches[1]};else:?>" .
				(isset($matches[7]) ? $this->parse($matches[7]) : '') .
				"<?php endif;?>" ;
	}

	/**
	 * A generic opening language construct. Trims whitespace (most useful for switch statements)
	 * @param  array  $matches
	 * @return string
	 */
	private function _open(array $matches) {
		return '<?php ' . substr(trim($matches[0]), 1, -1) . ': ?>';
	}

	/**
	 * A generic closing language construct.
	 * @param  array  $matches 
	 * @return string          
	 */
	private function _end(array $matches) {
		return '<?php end' . $matches[1] . ';?>';
	}

	/**
	 * A break (for loops or switch statements)
	 * @param  array  $matches
	 * @return string
	 */
	private function _break(array $matches) {
		return '<?php break;?>';
	}

	/**
	 * Any form of control, like 'break', 'continue'
	 * @param  array  $matches
	 * @return string
	 */
	private function _control(array $matches) {
		return '<?php ' . substr(trim($matches[0]), 1, -1) . '; ?>';
	}

	/**
	 * Defines / creates a new variable.
	 * @param  array  $matches
	 * @return string
	 */
	private function _define(array $matches)
	{
		return "<?php \${$matches[1]} = {$matches[2]} ;?>";
	}

	/**
	 * Echoes a variable. Automatically applies htmlspecialchars
	 * @param  array $matches 
	 * @return string          
	 */
	private function _echo(array $matches)
	{
		return "<?= htmlspecialchars({$matches[1]}) ;?>";		
	}

	/**
	 * Include another template
	 * @param  array $matches 
	 * @return string
	 */
	private function _include(array $matches)
	{
		$template = template_file($matches[1]);

		return "<?php include('{$template}');?>";
	}

	/**
	 * Stores a section of parsed content
	 * @param  array  $matches
	 * @return string
	 */
	private function _section(array $matches)
	{
		$this->section[$matches[1]] = $this->parse($matches['content']);

		return '';
	}

	/**
	 * Yields a section of stored content
	 * @param  array  $matches [description]
	 * @return string
	 */
	private function _yield(array $matches)
	{
		if (isset($this->section[$matches[1]])) {
			return $this->section[$matches[1]];
		} else {
			return '';
		}
	}

	/**
	 * Extends another template
	 * @param  array  $matches
	 * @return string
	 */
	private function _extend(array $matches)
	{
		$template = template_file($matches[1]);

		// Add dependency to cache
		$this->cache->addDependency($template, filemtime($template));

		return $this->parse(file_get_contents($template));
	}

}