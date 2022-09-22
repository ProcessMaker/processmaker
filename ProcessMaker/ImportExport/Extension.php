<?php

namespace ProcessMaker\ImportExport;

class Extension
{
    public $extensions = [];

    public function register($exporterClass, $class)
    {
        if (!isset($this->extensions[$exporterClass])) {
            $this->extensions[$exporterClass] = [];
        }
        $this->extensions[$exporterClass][] = $class;
    }

    public function runExtensions($exporter, $method)
    {
        $exporterClass = get_class($exporter);

        if (!isset($this->extensions[$exporterClass])) {
            return;
        }

        foreach ($this->extensions[$exporterClass] as $class) {
            $extension = new $class($exporter->model, $exporter->manifest);
            if (method_exists($extension, $method)) {
                $extension->$method();
            }
        }
    }
}
