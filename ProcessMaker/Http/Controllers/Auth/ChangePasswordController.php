<?php

namespace ProcessMaker\Http\Controllers\Auth;

use Illuminate\Support\Facades\Auth;
use ProcessMaker\Http\Controllers\Controller;

class ChangePasswordController extends Controller
{
    public function edit()
    {
        $user = Auth::user();

        return view('auth.passwords.change', compact('user'));
    }
}
