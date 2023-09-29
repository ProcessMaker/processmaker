<?php

namespace ProcessMaker\Http\Middleware;

use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use ProcessMaker\Models\User;

class ValidateEditUserAndPasswordPermission
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->route('user');
        $fields = $request->json()->all();
        if (($fields['username'] !== $user->getAttribute('username') || in_array('password', $fields)) &&
        !Auth::user()->hasPermission('edit-user-and-password') && !Auth::user()->is_administrator) {
            throw new AuthorizationException(__('Not authorized to update the username and password.'));
        }

        return $next($request);
    }
}
