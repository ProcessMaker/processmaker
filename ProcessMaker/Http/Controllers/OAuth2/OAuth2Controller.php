<?php
namespace ProcessMaker\Http\Controllers\OAuth2;

use Igaster\LaravelTheme\Facades\Theme;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Exception\OAuthServerException;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Model\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Handles OAuth2 related requests for authentication and authorization
 * @package ProcessMaker\Http\Controllers\OAuth2
 */
class OAuth2Controller extends Controller
{
    /**
     * Provides Access Tokens for OAuth2 Clients
     * @param AuthorizationServer $server
     * @param ServerRequestInterface $request The request to handle
     * @param ResponseInterface $response The response object to return
     * @return Response The response to send to the client
     */
    public function token(AuthorizationServer $server, ServerRequestInterface $request, ResponseInterface $response)
    {
        try {
            // Pass our request and response into our OAuth2 Authorization server and return the modified response
            return $server->respondToAccessTokenRequest($request, $response);
        } catch (OAuthServerException $exception) {
            return $exception->generateHttpResponse($response);
        }
    }

    /**
     * If not logged in, will request the user authorize the requested client to access processmaker on their behalf
     * @param AuthorizationServer $server
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|\Illuminate\View\View|ResponseInterface
     */
    public function getAuthorization(AuthorizationServer $server, ServerRequestInterface $request, ResponseInterface $response)
    {
        try {
            $authRequest = $server->validateAuthorizationRequest($request);
            if (!Auth::user()) {
                $redirectParam = $request->getUri()->getPath() . '?' . $request->getUri()->getQuery();
                return redirect('/' . app()->getLocale() . '/' . Theme::get() . '/login/login?u=' . urlencode($redirectParam));
            }
            // Now we have a user
            // Fetch our UserEntity
            $user = User::where('USR_ID', Auth::id())->first();
            if (!$user) {
                /**
                 * @todo Replace with translatable string
                 */
                throw new OAuthServerException("Invalid User");
            }
            $authRequest->setUser($user);

            // Return our oauth2 view for authorization

            return view('oauth2.authorize', [
                'client' => $authRequest->getClient(),
                'user' => $authRequest->getUser(),
                'scopes' => $authRequest->getScopes(),
                'redirect_uri' => '/oauth2/authorize?' . $request->getUri()->getQuery()
            ]);
        } catch (OAuthServerException $exception) {
            // All instances of OAuthServerException can be formatted into a HTTP response
            return $exception->generateHttpResponse($response);
        }
    }

    /**
     * Handles authorization request, checking to see if the user did approve the client access or not
     * @param AuthorizationServer $server
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function postAuthorization(AuthorizationServer $server, ServerRequestInterface $request, ResponseInterface $response)
    {
        try {
            $authRequest = $server->validateAuthorizationRequest($request);
            if (! Auth::user()) {
                /**
                 * @todo Replace with translation
                 */
                throw new OAuthServerException("User session not valid.");
            }
            // Verify this user exists.
            $user = User::where('USR_ID', Auth::id())->first();
            if (!$user) {
                throw new OAuthServerException("Invalid User");
            }
            $authRequest->setUser($user);

            // Determine if user Approved
            if (array_key_exists('approve', $request->getParsedBody())) {
                $authRequest->setAuthorizationApproved(true);
            } else {
                $authRequest->setAuthorizationApproved(false);
            }
            return $server->completeAuthorizationRequest($authRequest, $response);
        } catch (OAuthServerException $exception) {
            // All instances of OAuthServerException can be formatted into a HTTP response
            return $exception->generateHttpResponse($response);
        }
    }
}
