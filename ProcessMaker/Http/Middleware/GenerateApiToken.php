<?php

namespace ProcessMaker\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Auth;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Exception\OAuthServerException;
use ProcessMaker\Model\OAuth2\OAuthClient;
use ProcessMaker\Models\OauthClientsQuery;
use ProcessMaker\Models\UsersQuery;
use Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory;

/**
 * Verifies that an API token has been generated for this user's logged in session and ensures the storage of the
 * api token in the session.
 * @package ProcessMaker\Http\Middleware
 */
class GenerateApiToken
{
    /**
     * @var AuthorizationServer The OAuth2 Authorization server reference
     */
    private $authServer;

    /**
     * GenerateApiToken constructor.
     * Container injects the OAuth2 Authorization server so we can later ensure a token is generated for the session
     * Also injects Router so we can internally route and handle OAuth2 requests
     * @param AuthorizationServer $authServer
     * @param Router $router
     */
    public function __construct(AuthorizationServer $authServer, Router $router)
    {
        $this->authServer = $authServer;
    }

    /**
     * Process request. Will ensure that if user is logged in that we have a session variable that has an api token
     * is created if needed
     * @param Request $request
     * @param Closure $next
     * @return mixed
     * @throws OAuthServerException
     */
    public function handle(Request $request, Closure $next)
    {
        // Check to see if logged in and token credentials are not generated yet
        if (Auth::user() && !session('apiToken')) {
            $user = Auth::user();

            // Grab our x-pm-local-client which represents our local web app
            $oauthClient = OAuthClient::where('CLIENT_ID', config('app.web_client_application_id'))->first();

            if (!$oauthClient) {
                throw new OAuthServerException('Unable to find internal OAuth Client', 500, 'invalid_client');
            }
            // We must generate a token and store it
            // We do this by generating faux oauth2 auth code handshaking internally
            // First create a PSR-7 Request
            $authRequest = $request->duplicate([
                'response_type' => 'code',
                'client_id' => $oauthClient->CLIENT_ID,
                'scope' => '*'
            ]);
            $authRequest->server->set('REQUEST_METHOD', 'GET');
            $authRequest->server->set('REQUEST_URI', route('oauth2-authorize', [
                'workspace' => $request->attributes->get('workspace')
            ]));
            $authRequest = (new DiactorosFactory())->createRequest($authRequest);
            $authResponse = (new DiactorosFactory())->createResponse(Response::create());
            $authRequest = $this->authServer->validateAuthorizationRequest($authRequest);
            $authRequest->setUser($user);
            // We automatically authorize this api token request
            $authRequest->setAuthorizationApproved(true);
            $response = $this->authServer->completeAuthorizationRequest($authRequest, $authResponse);
            // Grab the grant from the Loccation header
            $grant = $response->getHeader("Location");
            // Should only have one element, which includes our auth code
            $grant = parse_url($grant[0]);
            parse_str($grant['query'], $grant);
            $grant = $grant['code'];
            // Now create the request to generate the access token from the auth code
            $authRequest = $request->duplicate(null, [
                'grant_type' => 'authorization_code',
                'client_id' => $oauthClient->CLIENT_ID,
                'client_secret' => $oauthClient->CLIENT_SECRET,
                'redirect_uri' => config('app.url') . 'oauth2/grant',
                'code' => $grant
            ]);
            // Process the request
            $authRequest = (new DiactorosFactory())->createRequest($authRequest);
            $authResponse = (new DiactorosFactory())->createResponse(Response::create());
            $authResponse = $this->authServer->respondToAccessTokenRequest($authRequest, $authResponse);
            $tokenInfo = json_decode((string)$authResponse->getBody(), true);
            // Now let's store the received api token into our session
            $request->session()->put('apiToken', [
                'access_token' => $tokenInfo['access_token'],
                'expires_in' => $tokenInfo['expires_in'],
                'token_type' => $tokenInfo['token_type'],
                'scope' => '*',
                'refresh_token' => $tokenInfo['refresh_token'],
                'client_id' => $oauthClient->CLIENT_ID,
                'client_secret' => $oauthClient->CLIENT_SECRET
            ]);
        }

        return $next($request);
    }
}
