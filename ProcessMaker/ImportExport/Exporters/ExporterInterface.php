<?php

namespace ProcessMaker\ImportExport\Exporters;

interface ExporterInterface
{
    public function export() : void;

    public function import() : bool;

    public function uuid() : string;

    // public function save() : bool;
}
