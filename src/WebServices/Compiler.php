<?php

namespace WebServices;

use WebServices\Exceptions\HaltException;

/**
 * The compiler takes a template file and converts it
 * into proper PHP.
 *
 * @author  James Pegg <jamescpegg@gmail.com>
 * 
 * @todo  If / Else Statements
 * @todo  While
 * @todo  Switch Statements
 * @todo  Default responses for loops (e.g. foreach / else)
 */
class Compiler
{
	/**
	 * Compiled content
	 * @var string
	 */
	private $content;

	/**
	 * Cache object
	 * @var \Common\Cache
	 */
	private $cache;	

	/**
	 * Array of sections
	 * @var array
	 */
	private $sections = [];
	
	/**
	 * Array of methods and patterns
	 * @var array
	 */
	private $methods = [
							'define' 		=> '#{define\((?P<variable>\w+), (?P<value>.*)\)}#',
							'foreach'		=> '#{foreach (?P<iterable>\w+) as (?P<variable>\w+)}(?P<contents>.*?)({else}(?P<else>.*?))?{/foreach}#s',
							'echoEscape' 	=> '#{{{(?P<variable>.*)}}}#',
							'echo'			=> '#{{(?P<variable>.*)}}#',
							'asset'			=> '#{asset\((?P<type>.*?)\)}#',
							'section'		=> '#{section\((?P<section>\w+)\)}(?P<content>.*?){/section}#s',
							'yield'			=> '#{yield\((?P<section>\w+)\)}#',
							'extends'		=> '#{extends\((?P<template>.*)\)}#'
						];

	/**
	 * Sets cache object
	 * @param CommonCache $cache
	 */
	public function __construct(\Common\Cache $cache)
	{
		$this->cache = $cache;
	}

	/**
	 * Parses a give string.
	 * @param  string $content Content to be parsed
	 * @param  string $scope   Current variable scope
	 * @return string
	 */
	public function parse($content, $scope = 'this->')
	{
		$this->content = $content;

		// Loop through all methods & patterns
		foreach ($this->methods as $method => $pattern) {
			$this->content = preg_replace_callback($pattern, function ($matches) use ($scope, $method) {
				$method = "{$method}Callback";
				return $this->$method($matches, $scope);
			}, $this->content);
		}

		return $this->content;
	}

	/**
	 * Defines a variable
	 * @param  array $matches
	 * @param  string $scope
	 * @return string
	 */
	public function defineCallback(array $matches, $scope)
	{
		// Checks if it's a string or a reference than existing variable
		if (preg_match('#\'(.*)\'#', $matches['value'])) {
			$value = $matches['value'];
		} else {
			$value = '$' . $scope . str_replace('.', '->', $matches['value']);
		}

		return '<?php $' . $scope . $matches['variable'] . ' = ' . $value . ';?>';
	}

	/**
	 * Create a foreach loop
	 * @param  array $matches
	 * @param  string $scope
	 * @return string
	 */
	public function foreachCallback(array $matches, $scope)
	{
		$foreach = '<?php foreach ($' . $scope . $matches['iterable'] . ' as $' . $matches['variable'] . '): ?>' . $this->parse($matches['contents'], '') . '<?php endforeach;?>';
		
		// If fallback is provided, use the loopElse method
		if (isset($matches['else'])) {
			$foreach = $this->loopElse($matches, $scope, $foreach);
		}

		return $foreach;
	}

	/**
	 * Escape and echo a variable
	 * @param  array $matches
	 * @param  string $scope
	 * @return string
	 */
	public function echoEscapeCallback(array $matches, $scope)
	{
		return '<?= htmlspecialchars($' . $scope . str_replace('.', '->', $matches['variable']) . ') ;?>';
	}

	/**
	 * Echo a variable
	 * @param  array $matches
	 * @param  string $scope
	 * @return string
	 */
	public function echoCallback(array $matches, $scope)
	{
		return '<?= $' . $scope . str_replace('.', '->', $matches['variable']) . ' ;?>';
	}

	/**
	 * Echo a compiled asset
	 * @param  array $matches
	 * @param  string $scope
	 * @return string          
	 */
	public function assetCallback(array $matches, $scope)
	{
		return "<?= Assets::{$matches['type']}() ;?>";
	}

	/**
	 * Stores a section
	 * @param  array $matches
	 * @return string
	 */
	public function sectionCallback(array $matches)
	{
		$this->section[$matches['section']] = $this->parse($matches['content']);

		return '';
	}

	/**
	 * Fetches a section
	 * @param  array $matches
	 * @return string
	 */
	public function yieldCallback(array $matches)
	{
		if (isset($this->section[$matches['section']])) {
			return $this->section[$matches['section']];
		} else {
			return '';
		}
	}

	/**
	 * Extends a template
	 * @param  array $matches
	 * @return string
	 */
	public function extendsCallback(array $matches)
	{
		$template = $matches['template'];
		$template = str_replace('.', DIRECTORY_SEPARATOR, $template) . '.php';
		$template = TEMPLATE_DIR . DIRECTORY_SEPARATOR . $template;

		// Add dependency to cache
		$this->cache->addDependency($template, filemtime($template));

		return $this->parse(file_get_contents($template));
	}

	/**
	 * Creates an if statement
	 * @param  string $statement
	 * @param  string $true
	 * @param  string $false
	 * @return string
	 */
	private function ifStatement($statement, $true, $false)
	{
		return '<?php if (' . $statement . '):?>' . $true . '<?php else:?>' . $false . '<?php endif;?>' ;
	}

	/**
	 * Wraps a loop in an if statement
	 * @param  array  $matches
	 * @param  string $scope
	 * @param  string $loop
	 * @return string
	 */
	private function loopElse(array $matches, $scope, $loop)
	{
		return 	$this->ifStatement('count($' . $scope . $matches['iterable'] . ') > 0', $loop, $this->parse($matches['else'], ''));
	}

}