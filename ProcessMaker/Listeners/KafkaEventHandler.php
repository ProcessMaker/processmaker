<?php

namespace ProcessMaker\Listeners;

use Junges\Kafka\Contracts\KafkaConsumerMessage;
use ProcessMaker\Managers\Nayra\EntityRepositoryFactory;

class KafkaEventHandler
{
    const TOPIC_STORE_DATA = 'nayra-store-data';

    const handlers = [
        self::TOPIC_STORE_DATA => 'storeData',
    ];

    /**
     * Handle a Kafka message.
     *
     * @param  KafkaConsumerMessage  $message
     * @return void
     */
    public function __invoke(KafkaConsumerMessage $message)
    {
        $data = json_decode($message->getBody(), true);
        $topic = $message->getTopicName();
        $partition = $message->getPartition();
        if (isset(self::handlers[$topic])) {
            call_user_func([$this, self::handlers[$topic]], $data, $partition);
        }
    }

    private function storeData(array $transactions, int $partition)
    {
        foreach ($transactions as $transaction) {
            EntityRepositoryFactory::createRepository($transaction['entity'])
                ->save($transaction);
        }
    }
}
