<?php

namespace Common;

/**
 * Simple file caching class.
 * 
 * @author James Pegg <jamescpegg@gmail.com>
 *
 * @todo  Make cache truly agnostic by separating template 
 * dependency logic into a different class (and allowing that
 * to register checks as closures)
 */
class Cache
{
	/**
	 * Getters & Setters
	 */
	use \Common\Traits\Mutators;

	/**
	 * Unique cache identifier
	 * @var string
	 */
	private $identifier;

	/**
	 * Timestamp of when the original file was last modified
	 * @var integer
	 */
	private $timestamp;

	/**
	 * Array of dependencies and timestamps
	 * @var array
	 */
	private $dependencies = [];

	/**
	 * @param string $identifier
	 * @param integer $timestamp
	 * @throws InvalidArgumentException If arguments are invalid
	 */
	public function __construct($identifier, $timestamp)
	{
		if (is_string($identifier)) {
			$this->identifier = md5($identifier);
		} else {
			throw new \InvalidArgumentException("Expecting a String, '{$identifier}' given.");
		}

		if (is_numeric($timestamp)) {
			$this->timestamp = $timestamp;
		} else {
			throw new \InvalidArgumentException("Expecting an Integer, '{$timestamp}' given.");
		}
	}

	/**
	 * Absolute path of the cached file
	 * @return string
	 */
	public function getPath()
	{
		return CACHE_DIR . DIRECTORY_SEPARATOR . $this->identifier . '.' . $this->timestamp;
	}

	/**
	 * Returns the content of the cached file
	 * @param  String $content
	 * @return string
	 */
	public function getContent()
	{
		if (isset($this->content)) {
			return file_get_contents($this->path);
		}
	}

	/**
	 * Sets the content of the cache
	 * @param string $content
	 * @throws LogicException If the cache file wasn't locked
	 * @throws InvalidArgumentException If arguments were invalid
	 */
	public function setContent($content)
	{
		// Unset old cached files
		array_walk(glob(CACHE_DIR . DIRECTORY_SEPARATOR . $this->identifier . '*'), function($file) {
			unlink($file);
		});

		// Set new cache content
		if (is_string($content)) {

			$file = fopen($this->path, 'w+');

			if (flock($file, LOCK_EX)) {
				ftruncate($file, 0);
				fwrite($file, $content);
				fflush($file);
				flock($file, LOCK_UN);

				$this->storeDependencies();

			} else {
				throw new \LogicException('Cache was unable to lock file');
			}
		} else {
			throw new \InvalidArgumentException("Expecting a String, '{$content}' given.");
		}

	}

	/**
	 * Checks if the cache file is readable (i.e. has content available)
	 * @return boolean
	 */
	public function hasContent()
	{
		if (is_readable($this->path) && $this->checkDependencies()) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Returns the dependency path
	 * @return string
	 */
	public function getDependencyPath()
	{
		return CACHE_DIR . DIRECTORY_SEPARATOR . 'Dependecies' . DIRECTORY_SEPARATOR . $this->identifier;
	}

	/**
	 * Add a dependency to the current cache
	 * @param string $identifier
	 * @param integer $timestamp
	 */
	public function addDependency($identifier, $timestamp)
	{
		if (is_string($identifier) && is_numeric($timestamp)) {
			$this->dependencies[$identifier] = $timestamp;
		} else {
			throw new \InvalidArgumentException("Expecting a String, '{$identifier}' given.");
		}
	}

	/**
	 * Stores the current cache dependencies
	 * @return null
	 */
	public function storeDependencies()
	{
		// Create directory if it doesn't exist.
		$directory = dirname($this->dependencyPath);

		if (!is_dir($directory)) {
			mkdir($directory, 0755, true);
		}

		// Store dependencies
		$file = fopen($this->dependencyPath, 'w+');
		fwrite($file, serialize($this->dependencies));

		fclose($file);
	}

	/**
	 * Checks current dependencies
	 * @return bool Returns false if a dependency has changed
	 */
	public function checkDependencies()
	{
		$dependencies = unserialize(file_get_contents($this->dependencyPath));

		foreach ($dependencies as $file => $timestamp) {

			// This is a template dependency, so check modified time of template
			if (strpos(dirname($file), TEMPLATE_DIR) === 0 && filemtime($file) != $timestamp) {
				return false;
			}
		}

		return true;
	}	
}