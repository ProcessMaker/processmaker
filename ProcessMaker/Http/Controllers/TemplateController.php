<?php

namespace ProcessMaker\Http\Controllers;

use Illuminate\Http\Request;
use ProcessMaker\Models\Process;
use ProcessMaker\Templates\ProcessTemplate;

class TemplateController extends Controller
{
    protected array $types = [
        'process' => [Process::class, ProcessTemplate::class],
    ];

    /**
     * @param string $type
     * @param Request $request
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function edit(string $type, Request $request)
    {
        new $this->types[$type][1]->edit($request);
    }

    /**
     * @param string $type
     * @param Request $request
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function import(string $type, Request $request)
    {
        return view('templates.import', compact('type'));
    }

    /**
     * @param string $type
     * @param Request $request
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function configure(string $type, $request)
    {
        [$template, $addons, $categories] = (new $this->types[$type][1])->configure($request);

        return view('templates.configure', compact(['template', 'addons', 'categories']));
    }
}
