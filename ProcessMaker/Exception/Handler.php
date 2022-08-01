<?php

namespace ProcessMaker\Exception;

use Throwable;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Response;
use Illuminate\Routing\Route;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route as RouteFacade;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Our general exception handler
 */
class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        \Illuminate\Auth\AuthenticationException::class,
        \Illuminate\Auth\Access\AuthorizationException::class,
        \Symfony\Component\HttpKernel\Exception\HttpException::class,
        \Illuminate\Database\Eloquent\ModelNotFoundException::class,
        \Illuminate\Session\TokenMismatchException::class,
        \Illuminate\Validation\ValidationException::class,
    ];

    /**
     * Report our exception. If in testing with verbosity, it will also dump exception information to the console
     *
     * @param  Throwable  $exception
     *
     * @throws Throwable
     */
    public function report(Throwable $exception)
    {
        if (App::environment() == 'testing' && env('TESTING_VERBOSE')) {
            // If we're verbose, we should print ALL Exceptions to the screen
            echo $exception->getMessage()."\n";
            echo $exception->getFile().': Line: '.$exception->getLine()."\n";
            echo $exception;
        }
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $exception
     * @return \Illuminate\Http\Response
     */
    public function render($request, Throwable $exception)
    {
        $prefix = '';
        $route = $request->route();

        if ($route) {
            if ($exception instanceof NotFoundHttpException) {
                return RouteFacade::respondWithRoute($this->getFallbackRoute($route));
            }

            if ($exception instanceof ModelNotFoundException) {
                return RouteFacade::respondWithRoute($this->getFallbackRoute($route));
            }
        }

        return parent::render($request, $exception);
    }

    /**
     * Determine which fallback route should be used.
     *
     * @param  \Illuminate\Routing\Route  $route
     * @return string
     */
    private function getFallbackRoute(Route $route)
    {
        $prefixes = [];
        $prefixes[] = explode('.', $route->getName())[0];
        if (isset($route->computedMiddleware) && count($route->computedMiddleware)) {
            if (in_array('api', $route->computedMiddleware) || in_array('auth:api', $route->computedMiddleware)) {
                $prefixes[] = 'api';
            }
        }

        foreach ($prefixes as $prefix) {
            if (RouteFacade::has("$prefix.fallback")) {
                return "$prefix.fallback";
            }
        }

        return 'fallback';
    }

    /**
     * Convert an authentication exception into an unauthenticated response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Auth\AuthenticationException  $exception
     * @return \Illuminate\Http\Response
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if ($request->expectsJson()) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        return redirect()->guest('login');
    }

    /**
     * Convert the given exception to an array.
     *
     * @note This is overridding Laravel's default exception handler in order to handle binary data in message
     *
     * @param  \Throwable  $e
     * @return array
     */
    protected function convertExceptionToArray(Throwable $e)
    {
        return config('app.debug') ? [
            'message' => utf8_encode($e->getMessage()),
            'exception' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => collect($e->getTrace())->map(function ($trace) {
                return Arr::except($trace, ['args']);
            })->all(),
        ] : [
            'message' => $this->isHttpException($e) ? $e->getMessage() : 'Server Error',
        ];
    }
}
