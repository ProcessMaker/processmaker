<?php

namespace ProcessMaker\Templates;

use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

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

    public function edit($request) : JsonResponse;

    public function update($request) : JsonResponse;

    public function configure(int $id) : array;

    public function destroy(int $id) : bool;

    public function getManifest(string $type, int $id) : array;
}
