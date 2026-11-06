<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/Support/helpers.php';

use App\Support\Config;
use App\Support\Database;

Config::loadEnv(__DIR__ . '/../.env');
date_default_timezone_set(Config::get('app.timezone', 'Asia/Kolkata'));
Database::connect(require __DIR__ . '/../config/database.php');

