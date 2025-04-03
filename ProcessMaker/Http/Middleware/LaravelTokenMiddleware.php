<?php

namespace ProcessMaker\Http\Middleware;

use Closure;
use Illuminate\Contracts\Encryption\Encrypter as EncrypterContract;
use Illuminate\Contracts\Session\Session;
use Illuminate\Http\Request;
use Illuminate\Session\SessionManager;

class LaravelTokenMiddleware extends \Illuminate\Cookie\Middleware\EncryptCookies
{
    /**
     * The session manager.
     *
     * @var SessionManager
     */
    protected $manager;

    /**
     * The callback that can resolve an instance of the cache factory.
     *
     * @var callable|null
     */
    protected $cacheFactoryResolver;

    /**
     * The encrypter instance.
     *
     * @var EncrypterContract
     */
    protected $encrypter;

    /**
     * Create a new session middleware.
     *
     * @param  SessionManager  $manager
     * @param  callable|null  $cacheFactoryResolver
     * @return void
     */
    public function __construct(SessionManager $manager, ?callable $cacheFactoryResolver, EncrypterContract $encrypter)
    {
        $this->manager = $manager;
        $this->cacheFactoryResolver = $cacheFactoryResolver;
        $this->encrypter = $encrypter;
    }

    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @param  Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // Check if session is configured
        if (!$this->sessionConfigured()) {
            return $next($request);
        }

        // Load session by id
        $session = $this->loadSessionId($request);

        // Start session with session id
        $session = $this->startSession($request, $session);

        // Get CSRF token from session
        $token = $session->get('_token');

        // Check if CSRF token belongs to the current session
        if ($token !== $request->header('X-CSRF-TOKEN')) {
            // Session was invalidated, 401
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return $next($request);
    }

    /**
     * Start the session for the given request.
     *
     * @param  Request  $request
     * @param  Session  $session
     * @return Session
     */
    protected function startSession(Request $request, $session)
    {
        return tap($session, function ($session) use ($request) {
            // only start session if it is not already started
            $session->start();
        });
    }

    /**
     * Get the session implementation from the manager.
     *
     * @param  Request  $request
     * @return Session
     */
    public function loadSessionId(Request $request)
    {
        return tap($this->manager->driver(), function ($session) use ($request) {
            $sessionIdFromCookie = $this->validateValue(
                $session->getName(),
                $this->decryptCookie(
                    $session->getName(),
                    $request->cookies->get($session->getName())
                )
            );
            $session->setId($sessionIdFromCookie);
        });
    }

    /**
     * Determine if a session driver has been configured.
     *
     * @return bool
     */
    protected function sessionConfigured()
    {
        return !is_null($this->manager->getSessionConfig()['driver'] ?? null);
    }
}
