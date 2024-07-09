<?php

namespace ProcessMaker\Http\Controllers;

use ProcessMaker\Helpers\DefaultColumns;

class InboxRulesController extends Controller
{
    public function index()
    {
        $defaultColumns = DefaultColumns::get('tasks');

        return view('inbox-rules.index', compact('defaultColumns'));
    }
}
