<?php

declare(strict_types=1);

// ToolLab Invoice - Bootstrap & Front Controller

define('APP_START', microtime(true));

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/Support/helpers.php';

use App\Support\Config;
use App\Support\Request;
use App\Support\Router;

// Load environment configuration
Config::loadEnv(__DIR__ . '/../.env');

// Error reporting
if (Config::get('app.debug', false)) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(0);
}

// Timezone
date_default_timezone_set(Config::get('app.timezone', 'Asia/Kolkata'));

// Load web routes
require_once __DIR__ . '/../routes/web.php';

// Dispatch HTTP request
$request = new Request();
$response = Router::dispatch($request);
$response->send();
