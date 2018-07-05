<?php
namespace ProcessMaker\Http\Controllers\Request;

use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Model\Application;

class StatusController extends Controller
{

    public function status(Application $instance)
    {
        return view('request.status', ['instance' => $instance]);
    }

}