<?php

namespace ProcessMaker\Http\Controllers\Auth;

use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Controller;

class ChangePasswordController extends Controller
{
    public function edit()
    {
        return view('auth.passwords.change');
    }
}
