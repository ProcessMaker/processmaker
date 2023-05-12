<?php

namespace ProcessMaker\Nayra\QueueService;

use ProcessMaker\Listeners\KafkaEventHandler;
use Junges\Kafka\Facades\Kafka;
use Junges\Kafka\Message\Message;
use RdKafka\Consumer;

class KafkaQueueService implements QueueServiceInterface
{
    private $NUMBER_PARTITIONS = 10;

    public function __construct()
    {
        $this->NUMBER_PARTITIONS = env('KAFKA_NUMBER_PARTITIONS', 10);
    }

    public function config(): void
    {
        app()->bind(
            Consumer::class,
            function ($app, $param) {
                $consumer = Kafka::createConsumer(['nayra-store']);
                $consumer->subscribe([KafkaEventHandler::TOPIC_STORE_DATA]);
                $consumer->withHandler(new KafkaEventHandler);

                return $consumer;
            }
        );
    }

    public function sendMessage(string $topic, string $collaborationId, mixed $body): void
    {
        // $partition = abs(crc32($collaborationId) % self::NUMBER_PARTITIONS);
        $partition = $collaborationId % $this->NUMBER_PARTITIONS;
        $topic = $topic;
        $message = Message::create($topic, $partition)
            ->withHeader('collaborationId', $collaborationId)
            ->withBody($body);
        Kafka::publishOn($topic)->withMessage($message)->send();
    }

    public function receiveMessage(string $queueName): string
    {
        /*$this->consumer->subscribe([$queueName]);
        $message = $this->consumer->consume(120 * 1000);

        if ($message->err === RD_KAFKA_RESP_ERR_NO_ERROR) {
            return $message->payload;
        }*/

        return '';
    }
}
