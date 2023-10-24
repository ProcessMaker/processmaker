<?php

namespace ProcessMaker\Nayra\MessageBrokers;

use Exception;
use Illuminate\Support\Facades\Log;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use ProcessMaker\Helpers\DBHelper;
use ProcessMaker\Nayra\Repositories\PersistenceHandler;

class ServiceRabbitMq
{
    const QUEUE_NAME_CONSUME = 'nayra-store';

    const QUEUE_NAME_PUBLISH = 'requests';

    const PROCESSES_QUEUE = 'processes';

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
        $rabbitmqKeepalive = true;
        $rabbitmqHeartbeat = config('rabbitmq.heartbeat', 60);

        // Create connection
        $this->connection = new AMQPStreamConnection($rabbitMqHost, $rabbitMqPort, $rabbitMqLogin, $rabbitMqPassword, '/', false, 'AMQPLAIN', null, 'en_US', 3, 3, null, $rabbitmqKeepalive, $rabbitmqHeartbeat);
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
        if (!($this->connection instanceof AMQPStreamConnection) || !$this->connection->isConnected()) {
            $this->connect();
        }

        // Prepare the message to send
        if ($subject === 'scripts') {
            $payload = json_encode($body);
        } else {
            $payload = json_encode(['data' => $body, 'collaboration_id' => $collaborationId]);
        }
        $message = new AMQPMessage($payload);

        // Publish the message
        $this->channel->basic_publish($message, '', $subject);
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
        echo "\033[0;32m" . 'ProcessMaker consumer using rabbitmq.' . "\033[0m" . PHP_EOL;
        $this->connect();

        // Set callback to process the transactions
        $callback = function ($message) {
            DBHelper::db_health_check();

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
        if ($this->connection->isConnected()) {
            $this->disconnect();
        }
    }

    /**
     * Store data
     *
     * @param array $transactions
     */
    private function storeData(array $transactions): void
    {
        $handler = new PersistenceHandler();
        if (isset($transactions['type'])) {
            // Single transaction like about message
            $transactions = [$transactions];
        }
        foreach ($transactions as $transaction) {
            $handler->save($transaction);
        }
    }

    public function sendAboutMessage()
    {
        // Get about information from composer.json
        $composerJsonPath = base_path('composer.json');
        $composerJson = json_decode(file_get_contents($composerJsonPath), true);
        $about = [
            'name' => $composerJson['name'],
            'version' => $composerJson['version'],
            'description' => $composerJson['description'],
        ];
        // Send about message
        try {
            $this->sendMessage(self::PROCESSES_QUEUE, '', ['type' => 'about', 'data' => $about]);
        } catch (Exception $e) {
            Log::error('Error sending about message', ['error' => $e->getMessage()]);
        }
    }
}
