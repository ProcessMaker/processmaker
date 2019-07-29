<?php

namespace ProcessMaker\Http\Controllers\Admin;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Models\Setting;

class CssOverrideController extends Controller
{
    public function edit()
    {
        if (!Auth::user()->is_administrator) {
            throw new AuthorizationException(__('Not authorized to complete this request.'));
        }

        $config = Setting::byKey('css-override');
        return view('admin.cssOverride.edit', compact('config'));
    }
}
