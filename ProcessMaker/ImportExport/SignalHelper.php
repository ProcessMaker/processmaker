<?php

namespace ProcessMaker\ImportExport;

use ProcessMaker\Managers\SignalManager;
use Psy\Command\PsyVersionCommand;

class SignalHelper
{
    const THROW_EVENTS = [
        'endEvent',
        'intermediateThrowEvent',
    ];

    const TYPE_THROW = 'throw';

    const TYPE_CATCH = 'catch';

    const TYPE_GLOBAL = 'global';

    const TYPE_PROCESS = 'process';

    public static function processessReferencedBySignals($process)
    {
        $dependents = [];

        self::findGlobalSignals($dependents, $process);

        // May be needed in the future
        // self::findDependentProcessesFromSignals($dependents, $process);

        return $dependents;
    }

    public static function findGlobalSignals(&$dependents, $process)
    {
        $globalSignalProcess = SignalManager::getGlobalSignalProcess();
        $globalSignalIds = SignalManager::getAllSignals(true, [$globalSignalProcess])->map(fn ($s) => $s['id']);

        foreach (SignalManager::getAllSignals(false, [$process]) as $signalData) {
            if ($globalSignalIds->contains($signalData['id'])) {
                $dependents[] = ['type' => self::TYPE_GLOBAL, 'signalData' => $signalData];
            }
        }
    }

    public static function findDependentProcessesFromSignals(&$dependents, $exportingProcess)
    {
        list($throws, $catches) = self::getSignalsByType($exportingProcess);

        // Iterate through all processes and get ones we depend on with throws and catches
        foreach (SignalManager::getAllSignals() as $signalData) {
            $signalId = $signalData['id'];
            foreach ($signalData['processes'] as $processInfo) {
                $processId = $processInfo['id'];

                if ($processId === $exportingProcess->id) {
                    // No need to add signals from the process we're exporting
                    continue;
                }

                foreach ($processInfo['catches'] as $node) {
                    if (
                        (self::type($node) === self::TYPE_THROW && in_array($signalId, $catches)) ||
                        (self::type($node) === self::TYPE_CATCH && in_array($signalId, $throws))
                    ) {
                        $dependents[] = ['type' => self::TYPE_PROCESS, 'signalId' => $signalId, 'processId' => $processId];
                    }
                }
            }
        }
    }

    public static function getSignalsByType($process)
    {
        $processSignalInfo = SignalManager::getAllSignals(false, [$process]);
        $throws = [];
        $catches = [];
        foreach ($processSignalInfo as $signalInfo) {
            foreach ($signalInfo['processes'][0]['catches'] as $node) {
                if (self::type($node) === self::TYPE_THROW) {
                    $throws[] = $signalInfo['id'];
                } else {
                    $catches[] = $signalInfo['id'];
                }
            }
        }

        return [$throws, $catches];
    }

    public static function type($node)
    {
        return in_array($node['type'], self::THROW_EVENTS) ? self::TYPE_THROW : self::TYPE_CATCH;
    }
}
