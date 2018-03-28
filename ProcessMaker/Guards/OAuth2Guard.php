<?php
namespace ProcessMaker\Guards;

use Illuminate\Container\Container;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Http\Request;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\ResourceServer;
use ProcessMaker\Model\User;
use Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory;

/**
 * Guard to protect calls to esnure OAuth2 authenticated
 * @package ProcessMaker\Guards
 */
class OAuth2Guard
{
    /**
     * The resource server instance.
     *
     * @var \League\OAuth2\Server\ResourceServer
     */
    protected $server;

    /**
     * Create a new token guard instance.
     *
     * @param  \League\OAuth2\Server\ResourceServer  $server
     * @return void
     */
    public function __construct(ResourceServer $server)
    {
        $this->server = $server;
    }

    /**
     * Get the user for the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function user(Request $request)
    {
        if ($request->bearerToken()) {
            return $this->authenticateViaBearerToken($request);
        }
    }

    /**
     * Authenticate the incoming request via the Bearer token.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    protected function authenticateViaBearerToken($request)
    {
        $psr = (new DiactorosFactory)->createRequest($request);
        try {
            $psr = $this->server->validateAuthenticatedRequest($psr);

            // Grab the user that has our specified oauth user id

            $user = User::where('USR_UID', $psr->getAttribute('oauth_user_id'))->first();
            if (! $user) {
                return;
            }
            return $user;
        } catch (OAuthServerException $e) {
            return Container::getInstance()->make(
                ExceptionHandler::class
            )->report($e);
        }
    }
}
