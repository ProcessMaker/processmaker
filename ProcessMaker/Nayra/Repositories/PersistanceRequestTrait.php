<?php

namespace ProcessMaker\Nayra\Repositories;

use ProcessMaker\Repositories\ExecutionInstanceRepository;
use ProcessMaker\Repositories\TokenRepository;

trait PersistanceRequestTrait
{
    protected Deserializer $deserializer;

    protected ExecutionInstanceRepository $instanceRepository;

    protected TokenRepository $tokenRepository;

    public function persistInstanceCreated(array $transaction)
    {
        $instance = $this->deserializer->unserializeInstance($transaction['instance']);
        $this->instanceRepository->persistInstanceCreated($instance);
    }

    public function persistInstanceCompleted(array $transaction)
    {
        $instance = $this->deserializer->unserializeInstance($transaction['instance']);
        $this->instanceRepository->persistInstanceCompleted($instance);
    }

    public function persistInstanceCollaboration(array $transaction)
    {
        $targetInstance = $this->deserializer->unserializeInstance($transaction['target_instance']);
        $targetParticipant = $this->deserializer->unserializeEntity($transaction['target_participant']);
        $sourceInstance = $this->deserializer->unserializeInstance($transaction['source_instance']);
        $sourceParticipant = $this->deserializer->unserializeEntity($transaction['source_participant']);
        $this->instanceRepository->persistInstanceCollaboration($targetInstance, $targetParticipant, $sourceInstance, $sourceParticipant);
    }
}
