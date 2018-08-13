<?php
namespace ProcessMaker\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use League\OAuth2\Server\AuthorizationServer;
use ProcessMaker\Http\Controllers\Controller;
use Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory;

/**
 * When access token expires, this controller is responsible to refresh the
 * tokens and store it in the session of the user.
 */
class RefreshController extends Controller
{

    /**
     * @var AuthorizationServer The OAuth2 Authorization server reference
     */
    private $authServer;

    /**
     * RefreshController constructor.
     *
     * @param AuthorizationServer $authServer
     */
    public function __construct(AuthorizationServer $authServer)
    {
        $this->authServer = $authServer;
    }

    /**
     * Refresh the access token and return the .
     *
     * @param Request $request
     *
     * @return void
     */
    public function refreshSession(Request $request)
    {
        $tokens = session('apiToken');
        $user = Auth::user();
        $authRequest = $request->duplicate(null, [
            'grant_type' => 'refresh_token',
            'refresh_token' => $tokens['refresh_token'],
            'client_id' => $tokens['client_id'],
            'client_secret' => $tokens['client_secret'],
        ]);
        $authRequest->server->set('REQUEST_METHOD', 'GET');
        $authRequest->server->set('REQUEST_URI', route('oauth2-authorize', [
            'workspace' => $request->attributes->get('workspace')
        ]));
        // Process the request
        $authRequest = (new DiactorosFactory())->createRequest($authRequest);
        $authResponse = (new DiactorosFactory())->createResponse(Response::create());
        $authResponse = $this->authServer->respondToAccessTokenRequest($authRequest, $authResponse);
        $tokenInfo = json_decode((string) $authResponse->getBody(), true);

        //Update access token into our session
        $tokens['access_token'] = $tokenInfo['access_token'];
        $tokens['refresh_token'] = $tokenInfo['refresh_token'];
        $request->session()->put('apiToken', $tokens);
        return response()->json(['access_token' => $tokens['access_token']]);
    }
}
