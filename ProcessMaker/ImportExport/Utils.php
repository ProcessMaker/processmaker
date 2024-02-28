<?php

namespace ProcessMaker\ImportExport;

use DOMXPath;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use ProcessMaker\Exception\ExportEmptyProcessException;
use ProcessMaker\Models\Process;
use ProcessMaker\Nayra\Storage\BpmnElement;
use ProcessMaker\Repositories\BpmnDocument;

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

    public static function getPmConfig(BpmnElement $element)
    {
        return json_decode($element->getAttribute('pm:config'), true);
    }

    // public static function getPmConfigAtXPath(Process $process, string $xmlPath)
    // {
    //     $definitions = $process->getDefinitions(true);
    //     $element = self::getElementByPath($definitions, $xmlPath);
    //     return self::getPmConfig($element);
    // }

    public static function getElementByPath($document, $path)
    {
        $elements = self::getElementsByPath($document, $path);
        if ($elements->count() !== 1) {
            throw new \Exception('Invalid xpath');
        }

        return $elements->item(0);
    }

    public static function getElementsByPath($document, $path)
    {
        try {
            $xpath = new DOMXPath($document);

            return $xpath->query($path);
        } catch (Exception $e) {
            if ($e->getMessage() === 'DOMXPath::query(): Undefined namespace prefix') {
                throw new ExportEmptyProcessException($e);
            }
            throw $e;
        }
    }

    public static function getElementByMultipleTags($document, array $tags = [])
    {
        $path = '';

        foreach ($tags as $tag) {
            $path .= "//$tag|";
        }

        $xpath = new DOMXPath($document);
        // add bpmn namespace
        $xpath->registerNamespace('bpmn', BpmnDocument::BPMN_MODEL);

        return $xpath->query(rtrim($path, '|'));
    }

    public static function setPmConfigValue(BpmnElement &$element, string $path, $value) : void
    {
        $config = self::getPmConfig($element);
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

    public static function getAttributeAtXPath(Process &$process, string $xmlPath, string $attrName)
    {
        $definitions = $process->getDefinitions(true);
        $element = self::getElementByPath($definitions, $xmlPath);

        return $element->getAttribute($attrName);
    }

    public static function setPmConfigValueAtXPath(Process &$process, string $xmlPath, string $arrayPath, $value)
    {
        $definitions = $process->getDefinitions(true);
        $element = self::getElementByPath($definitions, $xmlPath);
        self::setPmConfigValue($element, $arrayPath, $value);
        $process->bpmn = $definitions->saveXml();
    }

    public static function findScreenDependent($screenConfig, string $component, string $valuePath)
    {
        if (!$screenConfig) {
            return [];
        }

        $matches = [];
        foreach ($screenConfig as $page => $config) {
            self::findItems($config['items'], $component, $valuePath, "{$page}.items", $matches);
        }

        return $matches;
    }

    private static function findItems($items, $component, $valuePath, $path, &$matches = [])
    {
        foreach ($items as $i => $item) {
            $componentPath = "{$path}.{$i}";
            if ($item['component'] === $component) {
                $value = Arr::get($item, $valuePath);
                if ($value) {
                    $matches[] = [
                        'value' => $value,
                        'path' => "{$componentPath}.{$valuePath}",
                        'component_path' => $componentPath,
                    ];
                }
            } elseif ($item['component'] === 'FormMultiColumn') {
                foreach ($item['items'] as $mci => $mcItems) {
                    self::findItems($mcItems, $component, $valuePath, "{$componentPath}.items.{$mci}", $matches);
                }
            } elseif ($item['component'] === 'FormLoop') {
                self::findItems($item['items'], $component, $valuePath, "{$componentPath}.items", $matches);
            }
        }
    }

    public static function getAssignments($model, $tags): array
    {
        $assignmentsByPath = [];

        foreach (self::getElementByMultipleTags($model->getDefinitions(true), $tags) as $element) {
            [$userIds, $groupIds] = self::getAssignmentIds($element);
            $path = $element->getNodePath();
            $assignmentsByPath[$path] = [
                'userIds' => $userIds,
                'groupIds' => $groupIds,
                'assignmentType' => optional($element->getAttributeNode('pm:assignment'))->value,
            ];
        }

        return $assignmentsByPath;
    }

    public static function getAssignmentsByPath($model, $path): array
    {
        $element = self::getElementByPath($model->getDefinitions(true), $path);
        [$userIds, $groupIds] = self::getAssignmentIds($element);

        return [
            'userIds' => $userIds,
            'groupIds' => $groupIds,
            'assignmentType' => optional($element->getAttributeNode('pm:assignment'))->value,
        ];
    }

    private static function getAssignmentIds($element): array
    {
        $userIds = explode(',', optional($element->getAttributeNode('pm:assignedUsers'))->value);
        $groupIds = explode(',', optional($element->getAttributeNode('pm:assignedGroups'))->value);

        return [$userIds, $groupIds];
    }
}
