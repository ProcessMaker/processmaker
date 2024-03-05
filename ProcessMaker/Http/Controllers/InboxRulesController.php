<?php

namespace ProcessMaker\Http\Controllers;

class InboxRulesController extends Controller
{
    public function index()
    {
        return view('inbox-rules.index');
    }
}
