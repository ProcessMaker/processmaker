<?php

use Illuminate\Contracts\Console\Kernel as ConsoleKernelContract;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Http\Kernel as HttpKernelContract;
use ProcessMaker\Application;
use ProcessMaker\Console\Kernel as ConsoleKernel;
use ProcessMaker\Exception\Handler;
use ProcessMaker\Http\Kernel as HttpKernel;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\RotatingFileHandler;

/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| The first thing we will do is create a new Laravel application instance
| which serves as the "glue" for all the components of Laravel, and is
| the IoC container for the system binding all of the various parts.
|
*/

$app = new Application(
    realpath(__DIR__ . '/../')
);

/*
|--------------------------------------------------------------------------
| Bind Important Interfaces
|--------------------------------------------------------------------------
|
| Next, we need to bind some important interfaces into the container so
| we will be able to resolve them when needed. The kernels serve the
| incoming requests to this application from both the web and CLI.
|
*/
ini_set("memory_limit", -1);
$app->singleton(
    HttpKernelContract::class,
    HttpKernel::class
);

$app->singleton(
    ConsoleKernelContract::class,
    ConsoleKernel::class
);

$app->singleton(
    ExceptionHandler::class,
    Handler::class
);

/*
|--------------------------------------------------------------------------
| Return The Application
|--------------------------------------------------------------------------
|
| This script returns the application instance. The instance is given to
| the calling script so we can separate the building of the instances
| from the actual running of the application and sending responses.
|
*/

return $app;
