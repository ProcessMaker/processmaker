<?php

namespace ProcessMaker\Nayra\QueueService;

use Junges\Kafka\Contracts\Consumer;
use Junges\Kafka\Contracts\KafkaConsumerMessage;
use ProcessMaker\Managers\Nayra\EntityRepositoryFactory;

class KafkaConsumer extends Consumer
{
    const TOPIC_STORE_DATA = 'nayra-store-data';

    const handlers = [
        self::TOPIC_STORE_DATA => 'storeData',
    ];

    public function handle(KafkaConsumerMessage $message): void
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
            EntityRepositoryFactory::createRepository($transaction)
                ->save($transaction);
        }
    }
}
