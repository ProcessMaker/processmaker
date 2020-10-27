<?php

namespace ProcessMaker\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Repositories\DefinitionsRepository;

class CatchSignalEventRequest implements ShouldQueue
{
    use Dispatchable,
        InteractsWithQueue,
        Queueable;

    public $chunck;
    public $signalRef;
    public $payload;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($chunck, $signalRef, $payload)
    {
        $this->chunck = $chunck;
        $this->payload = $payload;
        $this->signalRef = $signalRef;
    }

    public function handle()
    {
        $repository = new DefinitionsRepository;
        $eventDefinition = $repository->createSignalEventDefinition();
        $signal = $repository->createSignal();
        $signal->setId($this->signalRef);
        $eventDefinition->setPayload($signal);
        $eventDefinition->setProperty('signalRef', $this->signalRef);

        foreach ($this->chunck as $requestId) {
            $request = ProcessRequest::find($requestId);
            $definitions = ($request->processVersion ?? $request->process)->getDefinitions(true, null, false);
            $engine = $definitions->getEngine();
            $instance = $engine->loadProcessRequest($request);
            $engine->getEventDefinitionBus()->dispatchEventDefinition(
                null,
                $eventDefinition,
                null
            );

            if ($this->payload) {
                foreach ($this->payload as $key => $value) {
                    $instance->getDataStore()->putData($key, $value);
                }
            }
            $engine->runToNextState();
        }
    }
}
