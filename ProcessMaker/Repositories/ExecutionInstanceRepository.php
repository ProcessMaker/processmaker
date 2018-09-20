<?php
namespace ProcessMaker\Repositories;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
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
        return new Instance();
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
        $instance->uuid_text = $instance->getId();
        $instance->callable_uuid = $process->getId();
        $instance->process_uuid = $definition->uuid;
        $instance->user_uuid = Auth::user()->uuid;
        $instance->name = $definition->name;
        $instance->status = 'DRAFT';
        $instance->initiated_at = Carbon::now();
        $instance->data = json_encode($data);
        $instance->save();
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
        $instance->save();
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
        Log::info("persistInstanceCompleted");
        $instance->status = 'COMPLETED';
        $instance->completed_at = Carbon::now();
        $instance->data = json_encode($data);
        $instance->save();
    }
}
