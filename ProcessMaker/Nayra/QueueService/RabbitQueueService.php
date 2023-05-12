<?php

namespace ProcessMaker\Nayra\QueueService;

use ProcessMaker\Listeners\KafkaEventHandler;
use Junges\Kafka\Facades\Kafka;
use Junges\Kafka\Message\Message;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use RdKafka\Consumer;

class RabbitQueueService implements QueueServiceInterface
{
    private $NUMBER_PARTITIONS = 10;
    private $connection;
    private $channel;

    public function __construct()
    {
        $this->NUMBER_PARTITIONS = env('KAFKA_NUMBER_PARTITIONS', 10);
    }

    public function config(): void
    {
    }

    public function sendMessage(string $subject, string $collaborationId, mixed $body): void
    {
        $this->connect();
        $msg = new AMQPMessage(json_encode(['data' => $body]), [
            'collaboration_id' => $collaborationId,
            // 'reply_to' => $responseQueueName,
        ]);
        $queueName = $subject;
        $this->channel->basic_publish($msg, '', $queueName);
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

    private function connect()
    {
        if (!$this->connection) {
            $rabbitmqHost = '192.168.39.78';
            $rabbitmqPort = 30672;
            $rabbitmqUser = 'guest';
            $rabbitmqPassword = 'guest';
            $this->connection = new AMQPStreamConnection($rabbitmqHost, $rabbitmqPort, $rabbitmqUser, $rabbitmqPassword);
            $this->channel = $this->connection->channel();
            $this->channel->queue_declare('start_process', false, true, false, false);
        }
    }
}
