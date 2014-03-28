<?php

/**
 * ==============================================
 *
 *        BlackBox Framework Build Script
 *
 * ==============================================
 *
 * This file is required to package the full 
 * BlackBox Framework into a single PHP Archive.
 *
 * For full notes on how to use the build script,
 * please visit:
 *
 * https://github.com/BlackBoxFramework/BlackBox
 * 
 * @author  James Pegg <jamescpegg@gmail.com>
 */

// Delete the existing archive
@unlink('blackbox.phar');

$phar = new Phar('blackbox.phar');

$root = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'src';

$iterator = new RecursiveIteratorIterator (new RecursiveDirectoryIterator ($root), RecursiveIteratorIterator::SELF_FIRST);

// Add every file in the source directory to the PHAR
foreach ($iterator as $file) {
    if ( preg_match ('/\\.php$/i', $file) ) {
    	exec('php -l ' . $file);
        $phar->addFromString (substr ($file, strlen ($root) + 1), php_strip_whitespace ($file));
    }
}

// Set Boot.php as our main point of entry.
$phar->setStub($phar->createDefaultStub('Boot.php'));
