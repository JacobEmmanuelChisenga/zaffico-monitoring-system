<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

// Increase PHP memory limit to prevent out of memory errors
ini_set('memory_limit', '256M');
// Increase execution time for API endpoints (especially for health checks)
ini_set('max_execution_time', '120'); // 2 minutes

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
