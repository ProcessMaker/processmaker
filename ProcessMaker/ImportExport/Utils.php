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

    public static function setAttributeAtXPath(Process &$process, string $xmlPath, string $attrName, $value) : void
    {
        $definitions = $process->getDefinitions(true);
        $element = self::getElementByPath($definitions, $xmlPath);
        $element->setAttribute($attrName, $value);
        $process->bpmn = $definitions->saveXml();
    }

    public static function setPmConfigValueAtXPath(Process &$process, string $xmlPath, string $arrayPath, $value)
    {
        $definitions = $process->getDefinitions(true);
        $element = self::getElementByPath($definitions, $xmlPath);
        self::setPmConfigValue($element, $arrayPath, $value);
        $process->bpmn = $definitions->saveXml();
    }

    public static function findScreenDependent(array $config, string $component, string $path)
    {
        $matches = [];
        foreach ($config as $page => $config) {
            foreach ($config['items'] as $i => $item) {
                if ($item['component'] === $component) {
                    $value = Arr::get($item, $path);
                    if ($value) {
                        $matches[] = [
                            'value' => $value,
                            'path' => "${page}.items.${i}.${path}",
                        ];
                    }
                }
            }
        }

        return $matches;
    }
}
