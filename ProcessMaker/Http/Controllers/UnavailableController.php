<?php

namespace ProcessMaker\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Support\Facades\Request;
use ProcessMaker\Events\ScreenBuilderStarting;
use ProcessMaker\Managers\ScreenBuilderManager;
use ProcessMaker\Models\Notification;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Traits\SearchAutocompleteTrait;

class UnavailableController extends Controller
{

    public function show()
    {
        return view('errors.unavailable');
    }
}
