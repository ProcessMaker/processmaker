<?php

namespace ProcessMaker\ImportExport;

use DOMXPath;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use ProcessMaker\Models\Process;
use ProcessMaker\Nayra\Storage\BpmnElement;

class Utils
{
    public static function getServiceTasks(Process $process, string $implementation = null) : Collection
    {
        $serviceTasks = collect($process->getDefinitions(true)->getElementsByTagName('serviceTask'));
        if ($implementation) {
            $serviceTasks = $serviceTasks->filter(function ($serviceTask) use ($implementation) {
                return $serviceTask->getAttribute('implementation') === $implementation;
            });
        }

        return $serviceTasks;
    }

    public static function getPmConfig(BpmnElement $element) : array
    {
        return json_decode($element->getAttribute('pm:config'), true);
    }

    public static function getElementByPath($document, $path)
    {
        $xpath = new DOMXPath($document);
        $elements = $xpath->query($path);
        if ($elements->count() !== 1) {
            throw new \Exception('Invalid xpath');
        }

        return $elements->item(0);
    }

    public static function setPmConfigValue(BpmnElement &$element, string $path, $value) : void
    {
        $config = json_decode($element->getAttribute('pm:config'), true);
        Arr::set($config, $path, $value);
        $element->setAttribute('pm:config', json_encode($config));
    }
}
