<?php

namespace ProcessMaker\Http\Controllers;

class UnavailableController extends Controller
{
    public function show()
    {
        return view('errors.unavailable');
    }
}
