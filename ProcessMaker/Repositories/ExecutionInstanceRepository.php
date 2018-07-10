<?php
namespace ProcessMaker\Repositories;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use ProcessMaker\Model\Application as Instance;
use ProcessMaker\Model\Delegation as Token;
use ProcessMaker\Nayra\Contracts\Engine\ExecutionInstanceInterface;
use ProcessMaker\Nayra\Contracts\Repositories\ExecutionInstanceRepositoryInterface;
use ProcessMaker\Nayra\Contracts\Repositories\StorageInterface;
use ProcessMaker\Nayra\RepositoryTrait;
use Illuminate\Support\Facades\Log;

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
     * @param string $uid
     *
     * @return \ProcessMaker\Nayra\Contracts\Engine\ExecutionInstanceInterface
     */
    public function loadExecutionInstanceByUid($uid, StorageInterface $storage)
    {
        $instance = Instance::where('uid', $uid)->first();
        if (!$instance) {
            abort(404, 'Instance not found');
        }
        $callableId = $instance->callable;
        $process = $storage->getProcess($callableId);
        $dataStore = $storage->getFactory()->createDataStore();
        $dataStore->setData(json_decode($instance->APP_DATA, true));
        $instance->setProcess($process);
        $instance->setDataStore($dataStore);
        $process->getTransitions($storage->getFactory());

        //Load tokens:
        foreach ($instance->delegations as $token) {
            $tokenInfo = [
                'id' => $token->uid,
                'status' => $token->thread_status,
                'element_ref' => $token->element_ref,
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

        //Save the row
        $instance->uid = $instance->getId();
        $instance->callable = $process->getId();
        $instance->process_id = $process->getEngine()->getProcess()->id;
        $instance->creator_user_id = 1;
        $instance->APP_TITLE = '';
        $instance->APP_STATUS = Instance::STATUS_DRAFT;
        $instance->APP_INIT_DATE = Carbon::now();
        $instance->APP_DATA = json_encode($data);
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
        $instance->APP_STATUS = Instance::STATUS_TO_DO;
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
        Log::info("persistInstanceCompleted");
        $instance->APP_STATUS = Instance::STATUS_COMPLETED;
        $instance->APP_FINISH_DATE = Carbon::now();
        $instance->save();
    }
}
