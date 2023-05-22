<?php

namespace ProcessMaker\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Models\User;

class ChangePasswordController extends Controller
{
    public function edit()
    {
        $user = Auth::user();

        return view('auth.passwords.change', compact('user'));
    }
}
