<?php

namespace ProcessMaker\Templates;

use Illuminate\Http\Response;

interface TemplateInterface
{
    public function save($request) : Response;

    public function view() : bool;

    public function edit() : bool;

    public function destroy() : bool;

    public function getManifest(string $type, int $id) : object;
}
