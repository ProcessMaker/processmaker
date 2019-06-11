<?php
namespace ProcessMaker\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ValidateExternalConnection
{
    /**
     * The names of the attributes that should not be trimmed.
     *
     * @var array
     */
    protected $except = [
        'password',
        'password_confirmation',
    ];

    public function handle(Request $request, Closure $next)
    {
        try {
            DB::connection('data')->getPdo();
        }
        catch (\Exception $e) {
            return redirect('/unavailable');
        }

        return $next($request);
    }
}