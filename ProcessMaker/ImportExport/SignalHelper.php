<?php

namespace ProcessMaker\ImportExport;

use Illuminate\Support\Collection;
use ProcessMaker\Managers\SignalManager;
use ProcessMaker\Models\Process;

class SignalHelper
{
    const THROW_TYPES = [
        'endEvent',
        'intermediateThrowEvent',
    ];

    public $allSignals;

    public $globalSignals;

    public function getGlobalSignals() : Collection
    {
        if (!$this->globalSignals) {
            $globalSignalProcess = SignalManager::getGlobalSignalProcess();
            $this->globalSignals = SignalManager::getAllSignals(true, [$globalSignalProcess])
                ->mapWithKeys(fn ($s) => [$s['id'] => $s['name']]);
        }

        return $this->globalSignals;
    }

    public function globalSignalsInProcess(Process $process)
    {
        $globalSignals = $this->getGlobalSignals();
        $signalsInProcess = SignalManager::getAllSignals(true, [$process])
            ->filter(fn ($signalInfo) => $globalSignals->has($signalInfo['id']))
            ->map(function ($signalInfo) {
                return [
                    'id' => $signalInfo['id'],
                    'name' => $signalInfo['name'],
                    'detail' => $signalInfo['detail'],
                ];
            });

        return $signalsInProcess;
    }

    public function processessReferencedByThrowSignals(Process $sourceProcess)
    {
        $id = $sourceProcess->id;
        $signalIdsInProcess = $this->throwSignalsInProcess($sourceProcess);
        $processes = [];

        foreach ($this->getAllSignals() as $signalInfo) {
            if (!in_array($signalInfo['id'], $signalIdsInProcess)) {
                continue;
            }

            foreach ($signalInfo['processes'] as $processInfo) {
                if ($processInfo['id'] === $id) {
                    // Do not include the source process
                    continue;
                }

                foreach ($processInfo['catches'] as $catchInfo) {
                    $processes[] = [
                        Process::findOrFail($processInfo['id']),
                        $signalInfo['id'],
                        $signalInfo['name'],
                    ];
                }
            }
        }

        return $processes;
    }

    public function throwSignalsInProcess(Process $sourceProcess)
    {
        $id = $sourceProcess->id;
        $signalIds = [];
        foreach ($this->getAllSignals() as $signalInfo) {
            foreach ($signalInfo['processes'] as $processInfo) {
                if ($processInfo['id'] === $id) {
                    foreach ($processInfo['catches'] as $catchInfo) {
                        if (in_array($catchInfo['type'], self::THROW_TYPES)) {
                            $signalIds[] = $signalInfo['id'];
                            break;
                        }
                    }
                    break;
                }
            }
        }

        return $signalIds;
    }

    public function getAllSignals()
    {
        if (!$this->allSignals) {
            $this->allSignals = SignalManager::getAllSignals(false);
        }

        return $this->allSignals;
    }
}
