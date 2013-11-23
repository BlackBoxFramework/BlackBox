<?php
/**
 * ==============================================
 *
 *             BlackBox Framework
 *
 * ==============================================
 *
 * BlackBox Framework is a single file, portable, 
 * scalable and high performance PHP framework.
 *
 * @copyright   Copyright 2013 James Pegg Designs
 * @link        https://github.com/BlackBoxFramework/BlackBox
 * @license     https://github.com/BlackBoxFramework/BlackBox/blob/master/LICENSE
 * @version     0.1
 * @author      James Pegg <jamescpegg@gmail.com>
 */

use \Exception;
use Common\Autoloader;
use Common\ServiceDispatcher;
use Common\ObjectContainer;

// Services
use ApiServices\ApiController;
use CommandServices\CommandController;
use WebServices\WebController;


/**
 * =============================================
 *
 *             Environment Setup
 *
 * =============================================
 */

// Load common functions and global definitions
require 'Common\\Functions.php';
require 'Globals.php';

// PHP Version Check
if (!version_compare(PHP_VERSION, '5.5.0', '>=')) {
    throw new Exception('BlackBox is only compatibly with PHP 5.5.0 or higher.', 1);
}

// PHP Extension Check
if(!empty($extensions = array_diff([], get_loaded_extensions()))) {
    throw new Exception('The following PHP extensions must be enabled : ' . implode(', ', $extensions), 1);
} else {
    unset($extensions);
}

// Check that all required directories are readable
if (!is_readable(FILTER_DIR) ||
    !is_readable(MODEL_DIR)  ||
    !is_readable(TEMPLATE_DIR)) {
    throw new Exception('Not all project directories as readable. Please make sure they exist and have the correct permissions.', 1);  
}

// Autoloader Setup
require 'Common\\Autoloader.php';
Autoloader::register([__DIR__, 
                      __DIR__ . DIRECTORY_SEPARATOR . 'Alias',
                      PROJECT_DIR,
                      FILTER_DIR, 
                      MODEL_DIR,
                      TEMPLATE_DIR,
                      CACHE_DIR]);

// Load Configuration File
if (is_readable(PROJECT_DIR . '/config.json')) {
    $config = json_get_contents('/config.json', true);
} else {
    throw new Exception('Configuration file either does not exist or is not readable.', 1);
}

// Loader Routes Filer
if (is_readable(PROJECT_DIR . '/routes.json')) {
    $routes = json_get_contents('/routes.json', true);
} else {
    throw new Exception('Routes file either does not exist or is not readable', 1);
}

// Set PHP Debugging
if ((isset($_SERVER['HTTP_HOST']) && $config->$_SERVER['HTTP_HOST']->debug) ||
    ($config->default->debug)) {
    ini_set('display_errors', 1);
}


/**
 * =============================================
 *
 *            Core Initialisation
 *
 * =============================================
 */

// Load singletons
$objectContainer = ObjectContainer::getInstance();

// Load MongoDB
if (extension_loaded('mongo')) {

    // Establish Connection
    $MongoClient = new MongoClient();

    // Select Database
    $MongoDatabase = $MongoClient->{$config->default->mongo_db};

    // Authenticate Connection
    $MongoDatabase->authenticate($config->default->mongo_user,
                                 $config->default->mongo_pwd);

    // Make the database available
    ObjectContainer::setObject('MongoDatabase', $MongoDatabase);
}

// Load the correct service (e.g. CLI, API or Web)
if (PHP_SAPI == 'cli') {

    $service = new CommandController($routes, $config);

} elseif(isset($config->default->api) &&
         isset($config->default->api_url) &&
         $config->default->api == true && 
         $config->default->api_url == $_SERVER['HTTP_HOST']) {

    $service = new ApiController($routes, $config);

} else {

    $service = new WebController($routes, $config);
}

// Run the service
$service->run();


// End Execution - Bye!
__HALT_COMPILER();
