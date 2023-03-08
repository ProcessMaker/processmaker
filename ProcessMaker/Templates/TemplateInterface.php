<?php

namespace ProcessMaker\Templates;

use Illuminate\Http\JsonResponse;

/**
 * Summary of TemplateInterface
 */
interface TemplateInterface
{
    /**
     * Summary of save
     * @param mixed $request
     * @return JsonResponse
     */
    public function save($request) : JsonResponse;

    public function view() : bool;

    public function edit() : bool;

    public function destroy() : bool;

    public function getManifest(string $type, int $id) : object;
}
