<?php
/**
 * Defines global constants for common use throughout
 * BlackBox.
 *
 * @author James Pegg <jamescpegg@gmail.com>
 */

/**
 * Common Constants (Applicable to all services)
 */
define('PROJECT_DIR', 	str_replace(DIRECTORY_SEPARATOR . 'Public', '', getcwd()));
define('FILTER_DIR', 	PROJECT_DIR . DIRECTORY_SEPARATOR . 'Filter');
define('MODEL_DIR', 	PROJECT_DIR . DIRECTORY_SEPARATOR . 'Model');
define('TEMPLATE_DIR', 	PROJECT_DIR . DIRECTORY_SEPARATOR . 'Template');
define('CACHE_DIR', 	PROJECT_DIR . DIRECTORY_SEPARATOR . 'Cache');



/**
 * Web Service Constants
 */
if (isset($_SERVER['REQUEST_URI'])) {
	define('REQUEST_URI', explode('?', $_SERVER['REQUEST_URI'])[0]);
}



/**
 * CLI Service Constants
 */



/**
 * API Service Constants
 */


