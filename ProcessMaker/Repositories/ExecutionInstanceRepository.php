<?php

namespace ProcessMaker\Repositories;

use Carbon\Carbon;
use ProcessMaker\Models\ProcessCollaboration;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequest as Instance;
use ProcessMaker\Nayra\Contracts\Bpmn\ParticipantInterface;
use ProcessMaker\Nayra\Contracts\Engine\ExecutionInstanceInterface;
use ProcessMaker\Nayra\Contracts\Repositories\ExecutionInstanceRepositoryInterface;
use ProcessMaker\Nayra\Contracts\Repositories\StorageInterface;
use ProcessMaker\Nayra\RepositoryTrait;

/**
 * Execution Instance Repository.
 *
 * @package ProcessMaker\Models
 */
class ExecutionInstanceRepository implements ExecutionInstanceRepositoryInterface
{
    use RepositoryTrait;

    /**
     * Create an execution instance.
     *
     * @return ExecutionInstanceInterface
     */
    public function createExecutionInstance()
    {
        $instance = new Instance();
        $instance->setId(uniqid('request', true));
        return $instance;
    }

    /**
     * Load an execution instance from a persistent storage.
     *
     * @param string $instanceId
     *
     * @return \ProcessMaker\Nayra\Contracts\Engine\ExecutionInstanceInterface
     */
    public function loadExecutionInstanceByUid($instanceId, StorageInterface $storage)
    {
        $instance = Instance::find($instanceId);
        if (!$instance) {
            abort(404, 'Instance not found');
        }
        $callableId = $instance->callable_id;
        $process = $storage->getProcess($callableId);
        $dataStore = $storage->getFactory()->createDataStore();
        $dataStore->setData($instance->data);
        $instance->setId($instanceId);
        $instance->setProcess($process);
        $instance->setDataStore($dataStore);
        $process->getTransitions($storage->getFactory());

        //Load tokens:
        foreach ($instance->tokens as $token) {
            $tokenInfo = [
                'id' => $token->getKey(),
                'status' => $token->status,
                'index' => $token->element_index,
                'element_ref' => $token->element_id,
            ];
            $token->setProperties(array_merge($token->token_properties ?: [], $tokenInfo));
            $element = $storage->getElementInstanceById($tokenInfo['element_ref']);
            $element->addToken($instance, $token);
        }
        return $instance;
    }

    /**
     * Create or update an execution instance to a persistent storage.
     *
     * @param \ProcessMaker\Nayra\Contracts\Engine\ExecutionInstanceInterface $instance
     *
     * @return $this
     */
    public function storeExecutionInstance(ExecutionInstanceInterface $instance)
    {
        // TODO: Implement store() method. or Remove from Interface
    }

    /**
     * Persists instance data related to the event Process Instance Created
     *
     * @param ExecutionInstanceInterface $instance
     *
     * @return mixed
     */
    public function persistInstanceCreated(ExecutionInstanceInterface $instance)
    {
        //Get instance data
        $data = $instance->getDataStore()->getData();
        //Get the process
        $process = $instance->getProcess();
        //Get process definition
        $definition = $process->getOwnerDocument()->getModel();

        if ($process->isNonPersistent()) {
            return;
        }

        //Save the row
        $instance->callable_id = $process->getId();
        $instance->process_id = $definition->getKey();
        $instance->process_version_id = $definition->getLatestVersion()->getKey();
        $instance->user_id = pmUser() ? pmUser()->getKey() : null;
        $instance->name = $definition->name;
        $instance->status = 'ACTIVE';
        $instance->initiated_at = Carbon::now();
        $instance->data = $data;
        $instance->saveOrFail();
        $instance->setId($instance->getKey());

        $this->persistCollaboration($instance);
    }

    private function findParticipantFor(ExecutionInstanceInterface $instance)
    {
        $collaboration = $instance->getProcess()->getEngine()->getEventDefinitionBus()->getCollaboration();
        if (!$collaboration) {
            return;
        }
        foreach ($collaboration->getParticipants() as $participant) {
            if ($participant->getProcess()->getId() === $instance->getProcess()->getId()) {
                return $participant;
            }
        }
    }

    /**
     * Persists instance when an error occurs
     *
     * @param ExecutionInstanceInterface $instance
     *
     * @return mixed
     */
    public function persistInstanceError(ExecutionInstanceInterface $instance)
    {
        $process = $instance->getProcess();
        if ($process->isNonPersistent()) {
            return;
        }

        //Save instance
        $instance->status = 'ERROR';
        $instance->mergeLatestStoredData();
        $instance->saveOrFail();
    }

    /**
     * Persists instance data related to the event Process Instance Completed
     *
     * @param ExecutionInstanceInterface $instance
     *
     * @return mixed
     */
    public function persistInstanceUpdated(ExecutionInstanceInterface $instance)
    {
        $process = $instance->getProcess();
        if ($process->isNonPersistent()) {
            return;
        }
        //Save instance
        $instance->status = 'ACTIVE';
        $instance->mergeLatestStoredData();
        $instance->saveOrFail();
    }

    /**
     * Persists instance data related to the event Process Instance Completed
     *
     * @param ExecutionInstanceInterface $instance
     *
     * @return mixed
     */
    public function persistInstanceCompleted(ExecutionInstanceInterface $instance)
    {
        $process = $instance->getProcess();
        if ($process->isNonPersistent()) {
            return;
        }
        //Save instance
        $instance->status = 'COMPLETED';
        $instance->completed_at = Carbon::now();
        $instance->mergeLatestStoredData();
        $instance->saveOrFail();
    }

    /**
     * Persists collaboration between two instances.
     *
     * @param ExecutionInstanceInterface $instance Target instance
     * @param ParticipantInterface $participant Participant related to the target instance
     * @param ExecutionInstanceInterface $source Source instance
     * @param ParticipantInterface $sourceParticipant
     */
    public function persistInstanceCollaboration(ExecutionInstanceInterface $instance, ParticipantInterface $participant = null, ExecutionInstanceInterface $source, ParticipantInterface $sourceParticipant = null)
    {
        $process = $instance->getProcess();
        if ($process->isNonPersistent()) {
            return;
        }
        if ($source->process_collaboration_id === null) {
            $collaboration = new ProcessCollaboration();
            $collaboration->process_id = $instance->process->getKey();
            $collaboration->saveOrFail();
            $source->process_collaboration_id = $collaboration->getKey();
            $source->saveOrFail();
        }
        $instance->process_collaboration_id = $source->process_collaboration_id;
        $instance->participant_id = $participant ? $participant->getId() : null;
        $instance->saveOrFail();
    }

    /**
     * Persist current collaboration
     *
     * @param ProcessRequest $instance
     * @return void
     */
    private function persistCollaboration(ProcessRequest $request)
    {
        $engine = $request->getProcess()->getEngine();
        if (count($engine->getExecutionInstances()) <= 1) {
            return;
        }
        $collaboration = null;
        foreach ($engine->getExecutionInstances() as $instance) {
            if ($instance->collaboration) {
                $collaboration = $instance->collaboration;
                break;
            }
        }
        if (!$collaboration) {
            $collaboration = new ProcessCollaboration();
            $collaboration->process_id = $request->process->getKey();
            $collaboration->saveOrFail();
        }
        $request->process_collaboration_id = $collaboration->id;
        $request->saveOrFail();
    }
}
