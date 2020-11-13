<?php

namespace ProcessMaker\Managers;

use DOMXPath;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\Screen;
use Illuminate\Database\Eloquent\Model;
use ProcessMaker\Nayra\Bpmn\Models\Signal;
use ProcessMaker\Repositories\BpmnDocument;

class SignalManager
{
    const PROCESS_NAME = 'global_signals';

    public static function getAllSignals($returnFullList = false)
    {
        $signals = collect();
        foreach (Process::all() as $process) {
            $nodes = $process
                        ->getDomDocument()
                        ->getElementsByTagNameNS(BpmnDocument::BPMN_MODEL, 'signal');

            foreach ($nodes as $node) {
                $signals->push([
                    'id' => $node->getAttribute('id'),
                    'name' => $node->getAttribute('name'),
                    'process' => (!$process->category->is_system || $returnFullList)
                                    ? [
                                        'id' => $process->id,
                                        'name' => $process->name,
                                        'catches' => self::getSignalCatchEvents($node->getAttribute('id'), $process->getDomDocument())->toArray()
                                    ]
                                    : null
                ]);
            }
        }


        $result = $signals->reduce(function ($carry, $signal) {
            $foundSignal = $carry->firstWhere('id', $signal['id']);
            if ($foundSignal) {
                if ($signal['process'] && !in_array($signal['process'], $foundSignal['processes'])) {
                    $foundSignal['processes'][] = $signal['process'];
                    $carry = $carry->merge([$foundSignal['id'] => $foundSignal]);
                }
            } else {
                $carry->put($signal['id'], [
                    'id' => $signal['id'],
                    'name' => $signal['name'],
                    'processes' => $signal['process'] ? [$signal['process']] : [],
                ]);
            }
            return $carry;

        }, collect());

        return $result->values();
    }

    public static function addSignal(Signal $signal)
    {
        $signalProcess = SignalManager::getGlobalSignalProcess();
        $definitions = $signalProcess->getDefinitions();
        $newNode = $definitions->createElementNS(BpmnDocument::BPMN_MODEL, "bpmn:signal");
        $newNode->setAttribute('id', $signal->getId());
        $newNode->setAttribute('name', $signal->getName());
        $definitions->firstChild->appendChild($newNode);
        $signalProcess->bpmn = $definitions->saveXML();
        $signalProcess->save();
    }


    public static function replaceSignal(Signal $newSignal, Signal $oldSignal)
    {
        $signal = self::getAllSignals(true)->firstWhere('id', $oldSignal->getId());
        foreach ($signal['processes'] as $processData) {
            $process = Process::find($processData['id']);
            if (empty($process)) {
                return;
            }
            $definitions = $process->getDefinitions();
            $newNode = $definitions->createElementNS(BpmnDocument::BPMN_MODEL, "bpmn:signal");
            $newNode->setAttribute('id', $newSignal->getId());
            $newNode->setAttribute('name', $newSignal->getName());

            $domDefinitions = new DOMXPath($definitions);
            if ($domDefinitions->query("//*[@id='" . $oldSignal->getId() . "']")->count() > 0 ) {
                $oldNode = $domDefinitions->query("//*[@id='" . $oldSignal->getId() . "']")->item(0);
                $definitions->firstChild->replaceChild($newNode, $oldNode);
            }


            $nodes = collect($definitions->getElementsByTagNameNS(BpmnDocument::BPMN_MODEL, 'signalEventDefinition'));
            foreach ($nodes as $node) {
                if ($node->getAttribute('signalRef') === $oldSignal->getId()) {
                    $node->setAttribute('signalRef', $newSignal->getId());
                }
            }

            $process->bpmn = $definitions->saveXML();
            $process->save();
        }
    }

    /**
     * @param Signal $signal
     */
    public static function removeSignal(Signal $signal)
    {
        // TODO validate that the signal is not referenced in any catch event

        $signalAsArray = self::getAllSignals(true)->firstWhere('id', $signal->getId());
        foreach ($signalAsArray['processes'] as $processData) {
            $process = Process::find($processData['id']);
            if (empty($process)) {
                return;
            }
            $definitions = $process->getDefinitions();
            $domDefinitions = new DOMXPath($definitions);
            if ($domDefinitions->query("//*[@id='" . $signal->getId() . "']")->count() > 0 ) {
                $node = $domDefinitions->query("//*[@id='" . $signal->getId() . "']")->item(0);
                $definitions->firstChild->removeChild($node);
                $process->bpmn = $definitions->saveXML();
                $process->save();
            }
        }
    }

    /**
     * @return Process
     */
    public static function getGlobalSignalProcess()
    {
        $list = Process::where('name', '' . static::PROCESS_NAME)->get();
        if ($list->count() === 0) {
            throw new \Exception("Global store of signals not found");
        }

        return $list->first();
    }

    /**
     * @param $signalId
     *
     * @return Signal | null
     */
    public static function findSignal($signalId)
    {
        $assocSignal =  SignalManager::getAllSignals()
                            ->firstWhere('id', $signalId);
        return $assocSignal ? self::associativeToSignal($assocSignal) : null;
    }

    /**
     * @param Signal $newSignal
     * @param Signal | null $oldSignal In case of an insert, this variable is null
     *
     * @return array
     */
    public static function validateSignal(Signal $newSignal, ?Signal $oldSignal)
    {
        $result = [];

        if ( !preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $newSignal->getId()) ) {
            $result[] = 'The signal ID should be an alphanumeric string';
        }

        $signalIdExists = self::getAllSignals()
                        ->contains(function ($sig) use($newSignal, $oldSignal){
                            return $sig['id'] === $newSignal->getId()
                                && (empty($oldSignal) ? true : $sig['id'] !== $oldSignal->getId());
                        });

        if ($signalIdExists) {
            $result[] = 'The signal ID already exists';
        }

        $signalNameExists = self::getAllSignals()
            ->contains(function ($sig) use($newSignal, $oldSignal){
                return $sig['name'] === $newSignal->getId()
                    && (empty($oldSignal) ? true : $sig['name'] !== $oldSignal->getName());
            });

        if ($signalNameExists) {
            $result[] = 'The signal name already exists';
        }

        return $result;
    }

    public static function getSignalCatchEvents($signalId, BpmnDocument $document)
    {
        $nodes = collect($document->getElementsByTagNameNS(BpmnDocument::BPMN_MODEL, 'signalEventDefinition'));
        return $nodes->reduce(function ($carry, $node) use($signalId) {
            if ($node->getAttribute('signalRef') === $signalId) {
                $carry->push ([
                    'id' => $node->parentNode->getAttribute('id'),
                    'name' => $node->parentNode->getAttribute('name'),
                    'type' => $node->parentNode->localName
                ]);
            }
            return $carry;
        }, collect());
    }

    /**
     * Converts an associative array to a signal object
     *
     * @param array $signal
     *
     * @return Signal
     */
    public static function associativeToSignal(array $signal): Signal
    {
        $result = new Signal();
        $result->setId($signal['id']);
        $result->setName($signal['name']);
        return $result;
    }
}
