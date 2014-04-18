<?php

namespace WebServices;

/**
 * Asset Manager
 *
 * Primarily concatenates project assets and makes a
 * compiled file available for templates.
 *
 * @author James Pegg <jamescpegg@gmail.com>
 */
class Assets
{
	use \Common\Traits\Singleton;

	/**
	 * Array of asset extensions
	 * @var array
	 */
	private static $extensions 	= [];

	/**
	 * Array of compiled asset locations
	 * @var array
	 */
	private static $assets 		= [];

	/**
	 * Checks whether assets need compiling and 
	 * concatenates them if they do.
	 * @param  array  $assets
	 * @return null
	 */
	public function compile(array $assets)
	{

		// Sort assets by extension and find the most recently changed file
		foreach ($assets as $asset) {
			$ext = pathinfo($asset, PATHINFO_EXTENSION);

			if (!isset(self::$extensions[$ext])) {
				self::$extensions[$ext] = 0;
			}

			if (filemtime(PROJECT_DIR . $asset) > self::$extensions[$ext]) {
				self::$extensions[$ext] = filemtime(PROJECT_DIR . $asset);
			}

		}

		foreach (self::$extensions as $extension => $timestamp) {
			$current = glob(PROJECT_DIR . '/Public/assets/main.*.' . $extension);

			// Compile if asset doesn't exist or if the most recently changed file is more recent than the current compiled asset
			if (empty($current) || (!empty($current) && explode('.', $current[0])[1] < $timestamp)) {
				$location = PROJECT_DIR . "/Public/assets/main.{$timestamp}.{$extension}";

				$asset = fopen($location, 'w+');

				foreach ($assets as $file) {
					if (is_readable(PROJECT_DIR . $file) && pathinfo($file, PATHINFO_EXTENSION) == $extension) {
						fwrite($asset, file_get_contents(PROJECT_DIR . $file));
					}
				}

				fclose($asset);

				// Delete old asset file
				if (isset($current[0])) {
					unlink($current[0]);
				}

			} else {
				$location = $current[0];
			}

			// Store location of asset
			self::$assets[$extension] = str_replace(PROJECT_DIR . '/Public', '', $location);
		}
	}

	public function registerPattern(\WebServices\Compiler $compiler)
	{
		$compiler->register('#{asset\((?P<type>.*?)\)}#', function($matches){
			return "<?= Assets::{$matches['type']}() ;?>";
		});
	}

	/**
	 * Overloaded method for returning any extension type (e.g. CSS, JS)
	 * @param  string $name      
	 * @param  array $arguments
	 * @return string
	 */
	public static function __callStatic($name, $arguments)
	{
		if (isset(self::$assets[$name])) {
			return self::$assets[$name];
		}
	}
}