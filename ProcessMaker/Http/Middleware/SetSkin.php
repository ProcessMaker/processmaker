<?php
namespace ProcessMaker\Http\Middleware;

use Closure;
use Igaster\LaravelTheme\Facades\Theme;
use Illuminate\Http\Request;

/**
 * Class SetSkin
 * @package ProcessMaker\Http\Middleware
 *
 * Sets the skin requested by the request
 */
class SetSkin
{

    /**
     * Handle request. If the request has a route parameter called skin, set the skin property in our view config
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Grab the skin parameter
        $skin = $request->route('skin');

        if ($skin) {
            Theme::set($skin);
        }

        // Process next
        return $next($request);
    }
}
