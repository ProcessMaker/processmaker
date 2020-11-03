<?php

namespace ProcessMaker\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Nayra\Contracts\Bpmn\CatchEventInterface;
use ProcessMaker\Repositories\DefinitionsRepository;

class ThrowMessageEvent extends BpmnAction implements ShouldQueue
{
    use Dispatchable,
        InteractsWithQueue,
        Queueable;

    private const maxJobs = 10;

    public $messageRef;
    public $payload;
    public $instanceId;
    public $elementId;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($instanceId, $elementId, $messageRef, array $payload = [])
    {
        $this->messageRef = $messageRef;
        $this->payload = $payload;
        $this->instanceId = $instanceId;
        $this->elementId = $elementId;
    }

    public function action(ProcessRequest $instance, CatchEventInterface $element)
    {
        $repository = new DefinitionsRepository;
        $eventDefinition = $repository->createMessageEventDefinition();
        $message = $repository->createMessage();
        $message->setId($this->messageRef);
        $eventDefinition->setPayload($message);
        $eventDefinition->setProperty('messageRef', $this->messageRef);

        $this->engine->getEventDefinitionBus()->dispatchEventDefinition(
            null,
            $eventDefinition,
            null
        );
        if ($this->payload) {
            foreach ($this->payload as $key => $value) {
                $instance->getDataStore()->putData($key, $value);
            }
        }
    }
}
