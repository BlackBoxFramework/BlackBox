<?php

namespace Common;

/**
 * This is a PSR-0 compliant autoloader. It registers itself
 * to the Standard PHP Library (SPL) and then handles all
 * class autoloading
 *
 * @author James Pegg <jamescpegg@gmail.com>
 */
class Autoloader
{
	/**
	 * Registers the autoloader and sets include paths.
	 * @param  array  $include_paths
	 */
	public static function register(array $include_paths)
	{
		set_include_path(get_include_path() . ';' . implode($include_paths, ';'));
		spl_autoload_extensions('.php');
		spl_autoload_register([__NAMESPACE__ . '\Autoloader', 'loader']);

		// Registery Composer Autoloader
		$composer = PROJECT_DIR . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

		if (is_readable($composer)) {
			require $composer;
		}

	}

	/**
	 * Finds the correct class to autoload and requires the file
	 * @param  string $class
	 */
	public static function loader($class)
	{
	    $class = ltrim($class, '\\');
	    $fileName  = '';
	    $namespace = '';
	    if ($lastNsPos = strrpos($class, '\\')) {
	        $namespace = substr($class, 0, $lastNsPos);
	        $class = substr($class, $lastNsPos + 1);
	        $fileName  = str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
	    }
	    $fileName .= str_replace('_', DIRECTORY_SEPARATOR, $class) . '.php';

	    require $fileName;
	}
}
