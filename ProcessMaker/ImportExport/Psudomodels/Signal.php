<?php

namespace ProcessMaker\ImportExport\Psudomodels;

use Illuminate\Support\Arr;
use ProcessMaker\ImportExport\SignalHelper;
use ProcessMaker\Managers\SignalManager;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\SignalData;

class Signal extends Psudomodel
{
    public $id;

    public $name;

    public $detail;

    public $global = false;

    public function __construct($attr = false)
    {
        if ($attr) {
            throw new \Exception('No attrs allowed');
        }
    }

    public function __get($name)
    {
        if ($name === 'uuid') {
            return 'signal-' . $this->id;
        }

        return null;
    }

    public function getAttributes()
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'detail' => $this->detail,
            'global' => $this->global,
        ];
    }

    public function fill($signalInfo)
    {
        $this->id = $signalInfo['id'];
        $this->name = $signalInfo['name'];
        $this->detail = $signalInfo['detail'];
        $this->global = Arr::get($signalInfo, 'global', false);
    }

    public function save()
    {
        if ($this->global) {
            $signalHelper = app()->make(SignalHelper::class);
            $globalSignals = $signalHelper->getGlobalSignals();

            if (!$globalSignals->has($this->id)) {
                $signal = new SignalData($this->id, $this->name, $this->detail);
                SignalManager::addSignal($signal);
            }
        }
    }

    public function getCasts()
    {
        return [];
    }

    public static function unguard()
    {
    }

    public static function reguard()
    {
    }

    public static function inProcess(Process $process)
    {
        $signals = [];
        $signalHelper = app()->make(SignalHelper::class);
        $globalSignals = $signalHelper->getGlobalSignals();
        foreach ($signalHelper->signalsInProcess($process) as $signalInfo) {
            $signal = self::fromSignalInfo($signalInfo);
            $signal->global = $globalSignals->has($signal->id);
            $signals[] = $signal;
        }

        return $signals;
    }

    public static function fromSignalInfo($signalInfo)
    {
        $signal = new self();
        $signal->id = $signalInfo['id'];
        $signal->name = $signalInfo['name'];
        $signal->detail = $signalInfo['detail'];

        return $signal;
    }

    public static function removeFromProcess(string $signalId, Process $process)
    {
        $xml = $process->getDefinitions(true);

        foreach ($xml->getElementsByTagName('signalEventDefinition') as $element) {
            if ($element->getAttribute('signalRef') === $signalId) {
                $element->removeAttribute('signalRef');
            }
        }

        foreach ($xml->getElementsByTagName('signal') as $element) {
            if ($element->getAttribute('id') === $signalId) {
                $element->parentNode->removeChild($element);
            }
        }

        $process->bpmn = $xml->saveXML();
    }
}
