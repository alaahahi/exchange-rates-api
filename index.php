<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

/*
|--------------------------------------------------------------------------
| Shared-hosting front controller
|--------------------------------------------------------------------------
|
| Use this when the server document root cannot point to /public.
| Keep the real public assets inside /public — .htaccess rewrites them.
|
*/

if (file_exists($maintenance = __DIR__.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

require __DIR__.'/vendor/autoload.php';

/** @var Application $app */
$app = require_once __DIR__.'/bootstrap/app.php';

$app->usePublicPath(__DIR__.'/public');

$app->handleRequest(Request::capture());
