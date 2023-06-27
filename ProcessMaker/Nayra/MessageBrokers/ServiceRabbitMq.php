<?php

namespace ProcessMaker\Nayra\MessageBrokers;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use ProcessMaker\Nayra\Repositories\PersistenceHandler;

class ServiceRabbitMq
{
    const QUEUE_NAME_CONSUME = 'nayra-store';

    const QUEUE_NAME_PUBLISH = 'requests';

    private $connection;

    private $channel;

    /**
     * Connect to the message broker service
     *
     * @return void
     */
    public function connect(): void
    {
        // Get configuration
        $rabbitMqHost = config('rabbitmq.host');
        $rabbitMqPort = config('rabbitmq.port');
        $rabbitMqLogin = config('rabbitmq.login');
        $rabbitMqPassword = config('rabbitmq.password');

        // Create connection
        $this->connection = new AMQPStreamConnection($rabbitMqHost, $rabbitMqPort, $rabbitMqLogin, $rabbitMqPassword);
        $this->channel = $this->connection->channel();

        // Set channel config
        $this->channel->queue_declare(self::QUEUE_NAME_PUBLISH, false, true, false, false);
        $this->channel->queue_declare(self::QUEUE_NAME_CONSUME, false, true, false, false);
    }

    /*
     * Disconnect from the message broker service
     *
     * @return void
     */
    public function disconnect(): void
    {
        // Close connections
        $this->channel->close();
        $this->connection->close();
    }

    /**
     * Send a message to a broker service
     *
     * @param string $subject
     * @param string $collaborationId
     * @param mixed $body
     * @return void
     */
    public function sendMessage(string $subject, string $collaborationId, mixed $body): void
    {
        // Connect to RabbitMQ
        $this->connect();

        // Prepare the message to send
        $message = new AMQPMessage(json_encode(['data' => $body, 'collaboration_id' => $collaborationId]));

        // Publish the message
        $this->channel->basic_publish($message, '', self::QUEUE_NAME_PUBLISH);

        // Close connection to RabbitMQ
        $this->disconnect();
    }

    /**
     * Receive a message from a broker service
     *
     * @param string $queueName
     * @return string
     */
    public function receiveMessage(string $queueName): string
    {
        return '';
    }

    /**
     * Run worker
     */
    public function worker(): void
    {
        // Connect to service
        $this->connect();

        // Set callback to process the transactions
        $callback = function ($message) {
            // Parse transactions
            $transactions = json_decode($message->body, true);

            // Store transactions
            $this->storeData($transactions);

            // Acknowledge message.
            $message->ack();
        };

        // Consume messages from the queue
        $this->channel->basic_qos(null, 1, null);
        $this->channel->basic_consume(self::QUEUE_NAME_CONSUME, '', false, false, false, false, $callback);

        // Wait for incoming messages
        while ($this->channel->is_consuming()) {
            $this->channel->wait();
        }

        // Disconnect from service
        $this->disconnect();
    }

    /**
     * Store data
     *
     * @param array $transactions
     */
    private function storeData(array $transactions): void
    {
        $handler = new PersistenceHandler();
        foreach ($transactions as $transaction) {
            $handler->save($transaction);
        }
    }
}
