<?php

/**
 * Test Bootstrap File
 *
 * This file is loaded before PHPUnit runs tests.
 * It sets up the testing environment to avoid loading
 * services that require external dependencies like Redis.
 */

// Set APP_ENV to testing before Laravel loads
putenv('APP_ENV=testing');
$_ENV['APP_ENV'] = 'testing';
$_SERVER['APP_ENV'] = 'testing';

// Load the Composer autoloader
require __DIR__.'/../vendor/autoload.php';
