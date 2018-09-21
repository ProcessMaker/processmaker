<?php
namespace ProcessMaker\Repositories;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use ProcessMaker\Models\ProcessCollaboration;
use ProcessMaker\Models\ProcessRequest as Instance;
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
        $instance->uuid_text = Instance::generateUuid();
        $instance->setId($instance->uuid_text);
        return $instance;
    }

    /**
     * Load an execution instance from a persistent storage.
     *
     * @param string $uuid
     *
     * @return \ProcessMaker\Nayra\Contracts\Engine\ExecutionInstanceInterface
     */
    public function loadExecutionInstanceByUid($uuid, StorageInterface $storage)
    {
        $instance = Instance::withUuid($uuid)->first();
        if (!$instance) {
            abort(404, 'Instance not found');
        }
        $callableId = $instance->callable_uuid;
        $process = $storage->getProcess($callableId);
        $dataStore = $storage->getFactory()->createDataStore();
        $dataStore->setData(json_decode($instance->data, true));
        $instance->setProcess($process);
        $instance->setDataStore($dataStore);
        $process->getTransitions($storage->getFactory());

        //Load tokens:
        foreach ($instance->tokens as $token) {
            $tokenInfo = [
                'id' => $token->uuid_text,
                'status' => $token->status,
                'element_ref' => $token->element_uuid,
            ];
            $token->setProperties($tokenInfo);
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
        $definition = $process->getEngine()->getProcess();

        //Save the row
        $instance->callable_uuid = $process->getId();
        $instance->process_uuid = $definition->uuid;
        $instance->user_uuid = Auth::user()->uuid;
        $instance->name = $definition->name;
        $instance->status = 'DRAFT';
        $instance->initiated_at = Carbon::now();
        $instance->data = json_encode($data);
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
        //Get instance data
        $data = $instance->getDataStore()->getData();
        //Save instance
        $instance->status = 'ACTIVE';
        $instance->data = json_encode($data);
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
        //Get instance data
        $data = $instance->getDataStore()->getData();
        //Save instance
        $instance->status = 'COMPLETED';
        $instance->completed_at = Carbon::now();
        $instance->data = json_encode($data);
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
    public function persistInstanceCollaboration(ExecutionInstanceInterface $instance, $participant, ExecutionInstanceInterface $source, $sourceParticipant)
    {
        if ($source->process_collaboration_uuid === null) {
            $collaboration = new ProcessCollaboration();
            $collaboration->process_uuid = $instance->process->uuid;
            $collaboration->saveOrFail();
            $source->process_collaboration_uuid = $collaboration->uuid;
            $source->saveOrFail();
        }
        $instance->process_collaboration_uuid = $source->process_collaboration_uuid;
        $instance->participant_uuid = $participant ? $participant->getId() : null;
        $instance->saveOrFail();
    }
}
