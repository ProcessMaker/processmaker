<?php

namespace ProcessMaker\Templates;

interface TemplateInterface
{
    public function save($request) : JsonResponse;

    public function view() : bool;

    public function edit() : bool;

    public function destroy() : bool;

    public function getManifest(string $type, int $id) : object;
}
