<?php

namespace ProcessMaker\Http\Controllers;

use Exception;
use Illuminate\Support\Facades\Log;
use ProcessMaker\Models\ProcessRequest;

class TestController extends Controller
{
    /**
     * Get the list of users.
     *
     * @return \Illuminate\View\View|\Illuminate\Contracts\View
     */
    public function test()
    {
        ini_set('memory_limit', -1);

        $ruta = "/home/marco/Downloads/data149.json";
        $fichero = fopen($ruta,"r");
        //Ya en mi_arreglo tienes el arreglo original

        $request = ProcessRequest::find(1);
        $request->data = fread($fichero,filesize($ruta));
        $request->save();

    }
}
