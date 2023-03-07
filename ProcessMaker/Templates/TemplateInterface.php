<?php

namespace ProcessMaker\Templates;

interface TemplateInterface
{
    public function save($request) : bool;

    public function view() : bool;

    public function edit() : bool;

    public function destroy() : bool;
}
