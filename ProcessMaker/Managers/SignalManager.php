<?php

namespace ProcessMaker\Managers;

use DOMXPath;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\SignalData;
use ProcessMaker\Repositories\BpmnDocument;

/**
 * Represents a business signal definition.
 *
 * @property string $id
 * @property string $name
 * @property string $detail
 * @property string $type
 * @property array $processes
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $created_at
 *
 * @OA\Schema(
 *   schema="signalsEditable",
 *   @OA\Property(property="id", type="string", format="id"),
 *   @OA\Property(property="name", type="string"),
 *   @OA\Property(property="detail", type="string"),
 * ),
 * @OA\Schema(
 *   schema="signals",
 *   allOf={
 *     @OA\Schema(ref="#/components/schemas/signalsEditable"),
 *     @OA\Schema(
 *       @OA\Property(property="type", type="string"),
 *       @OA\Property(property="processes", type="array", @OA\Items(
 *          type="object",
 *          @OA\Property(property="id", type="integer", format="id"),
 *          @OA\Property(property="is_system", type="boolean"),
 *          @OA\Property(property="name", type="string"),
 *          @OA\Property(property="catches", type="array", @OA\Items(
 *            type="object",
 *            @OA\Property(property="id", type="integer", format="id"),
 *            @OA\Property(property="name", type="string"),
 *            @OA\Property(property="type", type="string"),
 *          )),
 *       )),
 *     ),
 *   },
 * )
 *
 */
class SignalManager
{
    const PROCESS_NAME = 'global_signals';

    /**
     * Return the list of all signals in the system
     *
     * @param false $includeSystemProcesses, true if the signals that will be included can pertain to system processes
     * @param null $processList, collection of processes that will be used to extract the list of signals.
     * @return mixed
     */
    public static function getAllSignals($includeSystemProcesses = false, $processList = null)
    {
        $signals = collect();

        $processes = $processList ? $processList : Process::all();

        foreach ($processes as $process) {
            $nodes = $process
                        ->getDomDocument()
                        ->getElementsByTagNameNS(BpmnDocument::BPMN_MODEL, 'signal');

            foreach ($nodes as $node) {
                $signals->push([
                    'id' => $node->getAttribute('id'),
                    'name' => $node->getAttribute('name'),
                    'detail' => $node->getAttribute('detail'),
                    'process' => (!$process->category->is_system || $includeSystemProcesses)
                                    ? [
                                        'id' => $process->id,
                                        'name' => $process->name,
                                        'is_system' => $process->category->is_system,
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
                    'detail' => $signal['detail'],
                    'processes' => $signal['process'] ? [$signal['process']] : [],
                ]);
            }
            return $carry;

        }, collect());

        return $result->values();
    }

    public static function addSignal(SignalData $signal)
    {
        $signalProcess = SignalManager::getGlobalSignalProcess();
        $definitions = $signalProcess->getDefinitions();
        $newNode = $definitions->createElementNS(BpmnDocument::BPMN_MODEL, "bpmn:signal");
        $newNode->setAttribute('id', $signal->getId());
        $newNode->setAttribute('name', $signal->getName());
        $newNode->setAttribute('detail', $signal->getDetail());
        $definitions->firstChild->appendChild($newNode);
        $signalProcess->bpmn = $definitions->saveXML();
        $signalProcess->save();
    }


    public static function replaceSignal(SignalData $newSignal, SignalData $oldSignal)
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
            $newNode->setAttribute('detail', $newSignal->getDetail());

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
     * @param SignalData $signal
     */
    public static function removeSignal(SignalData $signal)
    {
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
     * @return SignalData | null
     */
    public static function findSignal($signalId)
    {
        $assocSignal =  SignalManager::getAllSignals()
                            ->firstWhere('id', $signalId);
        return $assocSignal ? self::associativeToSignal($assocSignal) : null;
    }

    /**
     * @param $signalId
     * @param $includeSystemProcesses
     *
     * @return array
     */
    public static function getSignalProcesses($signalId, $includeSystemProcesses = false)
    {
        $assocSignal =  SignalManager::getAllSignals($includeSystemProcesses)
                            ->firstWhere('id', $signalId);

        return $assocSignal && $assocSignal['processes'] ? $assocSignal['processes'] : [];
    }

    /**
     * @param SignalData $newSignal
     * @param SignalData | null $oldSignal In case of an insert, this variable is null
     *
     * @return array
     */
    public static function validateSignal(SignalData $newSignal, ?SignalData $oldSignal)
    {
        $result = [];

        if ( !preg_match('/^[a-zA-Z_][\w.-]*$/', $newSignal->getId()) ) {
            self::addError($result, 'id','The signal ID should be an alphanumeric string');
        }

        $signalIdExists = self::getAllSignals()
                        ->contains(function ($sig) use($newSignal, $oldSignal){
                            return $sig['id'] === $newSignal->getId()
                                && (empty($oldSignal) ? true : $sig['id'] !== $oldSignal->getId());
                        });

        if ($signalIdExists) {
            self::addError($result, 'id','The signal ID already exists');
        }

        if (strlen(trim($newSignal->getId())) === 0) {
            self::addError($result, 'id','The signal ID is required');
        }

        if (strlen(trim($newSignal->getName())) === 0) {
            self::addError($result, 'name','The signal name is required');
        }

        $signalNameExists = self::getAllSignals()
            ->contains(function ($sig) use($newSignal, $oldSignal){
                return $sig['name'] === $newSignal->getId()
                    && (empty($oldSignal) ? true : $sig['name'] !== $oldSignal->getName());
            });

        if ($signalNameExists) {
            self::addError($result, 'name','The signal name already exists');
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
                    'type' => $node->parentNode->localName,
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
     * @return SignalData
     */
    public static function associativeToSignal(array $signal): SignalData
    {
        return new SignalData(
            $signal['id'],
            $signal['name'],
            $signal['detail']
        );
    }

    /**
     * @param array $errors
     * @param string $field
     * @param string $message
     */
    private static function addError(array &$errors, string $field, string $message)
    {
        if (!array_key_exists($field, $errors)) {
            $errors[$field] = [];
        }

        array_push($errors[$field], $message);
    }

    public static function permissions($user) {
        return [
            'create-signals' => $user->can('create-signals'),
            'view-signals' => $user->can('view-signals'),
            'edit-signals' => $user->can('edit-signals'),
            'delete-signals' => $user->can('delete-signals'),
        ];
    }
}
