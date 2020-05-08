<?php

namespace ProcessMaker\Repositories;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use ProcessMaker\Models\ProcessCollaboration;
use ProcessMaker\Models\ProcessRequest as Instance;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Nayra\Contracts\Bpmn\EventInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\ParticipantInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\TokenInterface;
use ProcessMaker\Nayra\Contracts\Engine\ExecutionInstanceInterface;
use ProcessMaker\Nayra\Contracts\Repositories\ExecutionInstanceRepositoryInterface;
use ProcessMaker\Nayra\Contracts\Repositories\StorageInterface;
use ProcessMaker\Nayra\RepositoryTrait;
use Reflection;
use ReflectionClass;

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
            $token->setProperties($tokenInfo);
            $element = $storage->getElementInstanceById($tokenInfo['element_ref']);
            $element->addToken($instance, $token);
        }
        $mytokens2 = [];
        foreach ($instance->getTokens() as $tt) {
            $mytokens2[] = $tt->id;
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
    public function persistInstanceCreated(ExecutionInstanceInterface $instance, EventInterface $event = null, TokenInterface $source = null)
    {
        //Get instance data
        $data = $instance->getDataStore()->getData();
        //Get the process
        $process = $instance->getProcess();
        //Get process definition
        $definition = $process->getOwnerDocument()->getModel();

        //Save the row
        $instance->callable_id = $process->getId();
        $instance->process_id = $definition->getKey();
        $instance->process_version_id = $definition->getLatestVersion()->getKey();
        $instance->user_id = pmUser() ? pmUser()->getKey() : null;
        $instance->name = $definition->name;
        $instance->status = 'ACTIVE';
        $instance->initiated_at = Carbon::now();
        $instance->data = $data;
        if ($source) {
            // copy requester from source request
            $instance->user_id = $source->getInstance()->user_id;
        }
        $instance->saveOrFail();
        $instance->setId($instance->getKey());

        if ($source) {
            $participant = $this->findParticipantFor($instance);
            $sourcePartisipant = $this->findParticipantFor($source->getInstance());
            $this->persistInstanceCollaboration($instance, $participant, $source->getInstance(), $sourcePartisipant);
        }
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
}
