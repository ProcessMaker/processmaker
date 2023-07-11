<?php

namespace ProcessMaker\Nayra\Repositories;

use ProcessMaker\Repositories\ExecutionInstanceRepository;

trait PersistenceRequestTrait
{
    protected ExecutionInstanceRepository $instanceRepository;
    protected Deserializer $deserializer;

    /**
     * Store data related to the event Process Instance Created
     *
     * @param array $transaction
     */
    public function persistInstanceCreated(array $transaction)
    {
        $instance = $this->deserializer->unserializeInstance($transaction['instance']);
        $this->instanceRepository->persistInstanceCreated($instance);
    }

    /**
     * Store data related to the event Process Instance Completed
     *
     * @param array $transaction
     */
    public function persistInstanceCompleted(array $transaction)
    {
        $instance = $this->deserializer->unserializeInstance($transaction['instance']);
        $this->instanceRepository->persistInstanceCompleted($instance);
    }

    /**
     * Store collaboration data
     *
     * @param array $transaction
     */
    public function persistInstanceCollaboration(array $transaction)
    {
        // Return elements from serialized data
        $targetInstance = $this->deserializer->unserializeInstance($transaction['target_instance']);
        $targetParticipant = $this->deserializer->unserializeEntity($transaction['target_participant']);
        $sourceInstance = $this->deserializer->unserializeInstance($transaction['source_instance']);
        $sourceParticipant = $this->deserializer->unserializeEntity($transaction['source_participant']);

        // Persists collaboration between two instances
        $this->instanceRepository->persistInstanceCollaboration($targetInstance, $targetParticipant, $sourceInstance, $sourceParticipant);
    }

    /**
     * Store data related to the event Process Instance Updated
     *
     * @param array $transaction
     */
    public function persistInstanceUpdated(array $transaction)
    {
        $instance = $this->deserializer->unserializeInstance($transaction['instance']);
        $this->instanceRepository->persistInstanceUpdated($instance);
    }
}
