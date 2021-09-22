<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Models\User;

class ChangePasswordController extends Controller
{
    public function update(Request $request)
    {
        $user = User::findOrFail(Auth::user()->id);

        $fields = $request->json()->all();

        if (isset($fields['password'])) {
            $user->password = Hash::make($fields['password']);
            $user->force_change_password = 0;
            $user->save();
        }

        return response()->json(['success' => 'ok'], 200);
    }
}
