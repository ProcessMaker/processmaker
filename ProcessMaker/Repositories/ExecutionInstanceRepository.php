<?php

namespace ProcessMaker\Repositories;

use Carbon\Carbon;
use ProcessMaker\Models\ProcessCollaboration;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Nayra\Contracts\Bpmn\ParticipantInterface;
use ProcessMaker\Nayra\Contracts\Engine\ExecutionInstanceInterface;
use ProcessMaker\Nayra\Contracts\Repositories\ExecutionInstanceRepositoryInterface;
use ProcessMaker\Nayra\Contracts\Repositories\StorageInterface;
use ProcessMaker\Nayra\RepositoryTrait;
use ProcessMaker\SanitizeHelper;

/**
 * Execution Instance Repository.
 */
class ExecutionInstanceRepository implements ExecutionInstanceRepositoryInterface
{
    use RepositoryTrait;

    private bool $abortIfInstanceNotFound = true;

    private bool $loadTokens = true;

    /**
     * Set not found flag
     *
     * @param bool $abortIfInstanceNotFound
     */
    public function setAbortIfInstanceNotFound(bool $abortIfInstanceNotFound)
    {
        $this->abortIfInstanceNotFound = $abortIfInstanceNotFound;
    }

    /**
     * Set load tokens flag
     *
     * @param bool $loadTokens
     */
    public function setLoadTokens(bool $loadTokens)
    {
        $this->loadTokens = $loadTokens;
    }

    /**
     * Create an execution instance.
     *
     * @return ExecutionInstanceInterface
     */
    public function createExecutionInstance(): ExecutionInstanceInterface
    {
        $instance = new ProcessRequest();
        $instance->setId(uniqid('request', true));

        return $instance;
    }

    /**
     * Load an execution instance from a persistent storage.
     *
     * @param string $instanceId
     * @param StorageInterface $storage
     *
     * @return ExecutionInstanceInterface|null
     */
    public function loadExecutionInstanceByUid($instanceId, StorageInterface $storage): ?ExecutionInstanceInterface
    {
        // Get process request
        if (is_numeric($instanceId)) {
            $instance = ProcessRequest::find($instanceId);
        } else {
            $instance = ProcessRequest::where('uuid', $instanceId)->first();
        }

        // Finish if process request not exists
        if (!$instance && $this->abortIfInstanceNotFound) {
            abort(404, 'Instance not found');
        } elseif (!$instance) {
            return null;
        }

        // Get process
        $callableId = $instance->callable_id;
        $process = $storage->getProcess($callableId);

        // Set data store
        $dataStore = $storage->getFactory()->createDataStore();
        $dataStore->setData($instance->data);

        // Set process request properties
        $instance->setId($instance->getKey());
        $instance->setProcess($process);
        $instance->setDataStore($dataStore);

        // Get transitions
        $process->getTransitions($storage->getFactory());

        // Finish if is not necessary load the tokens
        if (!$this->loadTokens) {
            return $instance;
        }

        // Load tokens
        $tokens = $instance->tokens()->where('status', '!=', 'CLOSED')->get();
        foreach ($tokens as $token) {
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
     */
    public function storeExecutionInstance(ExecutionInstanceInterface $instance)
    {
        // TODO: Implement store() method or Remove from Interface
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
        // Get instance data
        $data = $instance->getDataStore()->getData();

        // Get the process
        $process = $instance->getProcess();

        // Get process definition
        $definition = $process->getOwnerDocument()->getModel();
        $version = $process->getOwnerDocument()->getProcessVersion();

        // Do nothing if is not persistent
        if ($process->isNonPersistent()) {
            return;
        }

        // Check if instance is a subprocess
        $parent = $data['_parent'] ?? null;
        if (!empty($parent) && is_numeric($parent['request_id'])) {
            $instance->parent_request_id = $parent['request_id'];
        }

        // Save process request
        $instance->callable_id = $process->getId();
        $instance->collaboration_uuid = $instance->getProperty('collaboration_uuid', null);
        $instance->process_id = $definition->getKey();
        $instance->process_version_id = $version?->getKey();
        if ($instance->collaboration_uuid && !$instance->process_collaboration_id) {
            $collaboration = ProcessCollaboration::firstOrCreate([
                'uuid' => $instance->collaboration_uuid,
                'process_id' => $instance->process_id,
            ]);
            if ($collaboration) {
                $instance->process_collaboration_id = $collaboration->id;
            }
        }
        $instance->user_id = pmUser() ? pmUser()->getKey() : null;
        $instance->name = $definition->name;
        $instance->status = 'ACTIVE';
        $instance->initiated_at = Carbon::now();
        $instance->do_not_sanitize = SanitizeHelper::getDoNotSanitizeFields($definition);
        $instance->data = $data;
        $instance->saveOrFail();

        // Set id
        $instance->setId($instance->getKey());

        // Persists collaboration
        $this->persistCollaboration($instance);
    }

    /**
     * Persists instance when an error occurs
     *
     * @param ExecutionInstanceInterface $instance
     * @return mixed
     */
    public function persistInstanceError(ExecutionInstanceInterface $instance)
    {
        // Get process
        $process = $instance->getProcess();

        // Do nothing if is not persistent
        if ($process->isNonPersistent()) {
            return;
        }

        // Save instance with error
        $instance->status = 'ERROR';
        $instance->mergeLatestStoredData();
        $instance->saveOrFail();
    }

    /**
     * Persists instance data related to the event Process Instance Completed
     *
     * @param ExecutionInstanceInterface $instance
     * @return mixed
     */
    public function persistInstanceUpdated(ExecutionInstanceInterface $instance)
    {
        // Get process
        $process = $instance->getProcess();

        // Do nothing if is not persistent
        if ($process->isNonPersistent()) {
            return;
        }

        // Save updated instance
        if (!$instance->status) {
            $instance->status = 'ACTIVE';
        }
        $instance->mergeLatestStoredData();
        $instance->saveOrFail();
    }

    /**
     * Persists instance data related to the event Process Instance Completed
     *
     * @param ExecutionInstanceInterface $instance
     * @return mixed
     */
    public function persistInstanceCompleted(ExecutionInstanceInterface $instance)
    {
        // Get process
        $process = $instance->getProcess();

        // Do nothing if is not persistent
        if ($process->isNonPersistent()) {
            return;
        }

        // Save completed instance
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
     * @param ParticipantInterface $sourceParticipant Source participant
     */
    public function persistInstanceCollaboration(ExecutionInstanceInterface $instance, ParticipantInterface $participant = null, ExecutionInstanceInterface $source, ParticipantInterface $sourceParticipant = null)
    {
        // Get process
        $process = $instance->getProcess();

        // Do nothing if is not persistent
        if ($process->isNonPersistent()) {
            return;
        }

        // Get collaboration id if not exists
        if ($source->process_collaboration_id === null) {
            $collaboration = new ProcessCollaboration();
            $collaboration->process_id = $instance->process->getKey();
            $collaboration->saveOrFail();
            $source->process_collaboration_id = $collaboration->getKey();
            $source->saveOrFail();
        }

        // Save collaboration
        $instance->process_collaboration_id = $source->process_collaboration_id;
        $instance->participant_id = $participant ? $participant->getId() : null;
        $instance->saveOrFail();
    }

    /**
     * Persist current collaboration.
     *
     * @param ProcessRequest $request
     */
    private function persistCollaboration(ProcessRequest $request)
    {
        // Get valid engine
        $engine = $request->getProcess()->getEngine();
        if ($engine) {
            if (count($engine->getExecutionInstances()) <= 1) {
                return;
            }

            // Get current collaboration
            $collaboration = null;
            foreach ($engine->getExecutionInstances() as $instance) {
                if ($instance->collaboration) {
                    $collaboration = $instance->collaboration;
                    break;
                }
            }

            // If not exists a collaboration, create a new one
            if (!$collaboration) {
                $collaboration = new ProcessCollaboration();
                $collaboration->process_id = $request->process->getKey();
                $collaboration->saveOrFail();
            }

            // Update collaboration id
            $request->process_collaboration_id = $collaboration->id;
            $request->saveOrFail();
        } elseif ($request->collaboration_uuid) {
            // find by uuid or create
            $collaboration = ProcessCollaboration::firstOrCreate([
                'uuid' => $request->collaboration_uuid,
                'process_id' => $request->process_id,
            ]);
            if ($collaboration) {
                $request->process_collaboration_id = $collaboration->id;
                $request->saveOrFail();
            }
        }
    }
}
