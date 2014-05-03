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
 * @version     0.3
 * @author      James Pegg <jamescpegg@gmail.com>
 */

use \Exception;
use Common\Autoloader;
use Common\ServiceDispatcher;
use Common\ObjectContainer;
use Common\Input;

// Services
use ApiServices\ApiController;
use CommandServices\CommandController;
use WebServices\WebController;

// Sessions
session_start();

/**
 * =============================================
 *
 *             Environment Setup
 *
 * =============================================
 */

// Load common functions and global definitions
require dirname(__FILE__) . DIRECTORY_SEPARATOR . 'Common' . DIRECTORY_SEPARATOR . 'Functions.php';
require dirname(__FILE__) . DIRECTORY_SEPARATOR . 'Globals.php';

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
require dirname(__FILE__) . DIRECTORY_SEPARATOR . 'Common' . DIRECTORY_SEPARATOR . 'Autoloader.php';
Autoloader::register([__DIR__, 
                      __DIR__ . DIRECTORY_SEPARATOR . 'Alias',
                      PROJECT_DIR,
                      FILTER_DIR, 
                      MODEL_DIR,
                      TEMPLATE_DIR,
                      CACHE_DIR]);

// Load Configuration File
if (is_readable(PROJECT_DIR . '/config.json')) {
    $config = json_get_contents(PROJECT_DIR . '/config.json', true);

    if (!isset($config->{DOMAIN})) {
        throw new Exception('Domain specific configuration could not be found', 1);
    }

    // Merge settings into one object
    $config = (object) array_merge((array) $config->default, (array) $config->{DOMAIN});
} else {
    throw new Exception('Configuration file either does not exist or is not readable.', 1);
}

// Loader Routes Filer
if (is_readable(PROJECT_DIR . '/routes.json')) {
    $routes = json_get_contents(PROJECT_DIR . '/routes.json', true);
} else {
    throw new Exception('Routes file either does not exist or is not readable', 1);
}

// Set PHP Debugging
if ($config->debug) {
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
$input = Input::getInstance();

// Load MongoDB
if (isset($config->mongo_db) && extension_loaded('mongo')) {

    // Establish Connection
    $MongoClient = new MongoClient();

    // Select Database
    $MongoDatabase = $MongoClient->{$config->mongo_db};

    // Authenticate Connection
    $MongoDatabase->authenticate($config->mongo_user,
                                 $config->mongo_pwd);

    // Make the database available
    ObjectContainer::setObject('MongoDatabase', $MongoDatabase);
}

// Load the correct service (e.g. CLI, API or Web)
if (PHP_SAPI == 'cli') {

    $service = new CommandController($routes, $config);

} elseif(isset($config->api) &&
         $config->api == true && 
         substr(REQUEST_URI, 1, 3) == 'api') {

    $service = new ApiController($routes, $config);

} else {

    $service = new WebController($routes, $config);
}

ObjectContainer::setObject('Service', $service);

// Run the service
$service->run();


// End Execution - Bye!
__HALT_COMPILER();
