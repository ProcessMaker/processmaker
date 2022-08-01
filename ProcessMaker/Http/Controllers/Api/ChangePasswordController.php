<?php

namespace ProcessMaker\Http\Controllers\Api;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Models\User;

class ChangePasswordController extends Controller
{
    public function update(Request $request)
    {
        $user = Auth::user();

        // On the rare occasion an authenticated user
        // can't be found, then bail out
        if (! $user instanceof User) {
            return response()->json([], 422);
        }

        // Same if we don't have the required fields
        if (! $request->has(['password', 'confpassword'])) {
            return response()->json([], 422);
        }

        // Make sure the password and password
        // confirmation are the equivalent
        $request->validate([
            'password' => User::passwordRules($user),
            'confpassword' => ['same:password'],
        ]);

        $user->setAttribute('password', Hash::make($request->json('password')));
        $user->setAttribute('force_change_password', 0);

        try {
            $user = $user->save();
        } catch (Exception $exception) {
            $user = false;
        }

        return response()->json(['success' => $user ? 'ok' : 'false'], 200);
    }
}
