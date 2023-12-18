<?php

namespace ProcessMaker\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;

class SamlRequest
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->hasCookie('saml_request') && !$request->filled(['SAMLRequest', 'RelayState'])) {
            // Get the SAMLRequest and RelayState from the cookie
            $samlRequestCookie = json_decode($request->cookie('saml_request'), true);
            $saml_Request = $samlRequestCookie['SAMLRequest'];
            $relay_state = $samlRequestCookie['RelayState'];

            // Add SAMLRequest and RelayState to the query string
            $request->query->add([
                'SAMLRequest' => $saml_Request,
                'RelayState' => $relay_state,
            ]);

            // Build the query string and set it
            $queryString = http_build_query($request->query());
            $request->server->set('QUERY_STRING', $queryString);

            // Remove saml_request cookie
            Cookie::queue(Cookie::forget('saml_request'));

            // To redirect with the new query string, you can use the following:
            return redirect($request->url() . '?' . $queryString);
        }

        return $next($request);
    }
}
