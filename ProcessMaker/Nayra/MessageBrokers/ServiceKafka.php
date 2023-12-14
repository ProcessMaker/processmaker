<?php

namespace ProcessMaker\Nayra\MessageBrokers;

use Exception;
use Illuminate\Support\Facades\Log;
use Junges\Kafka\Config\Sasl;
use Junges\Kafka\Contracts\KafkaConsumerMessage;
use Junges\Kafka\Facades\Kafka;
use ProcessMaker\Helpers\DBHelper;
use ProcessMaker\Nayra\Repositories\PersistenceHandler;

class ServiceKafka
{
    const QUEUE_NAME = 'nayra-store';

    const PROCESSES_QUEUE = 'processes';

    /**
     * Connect to the message broker service
     *
     * @return void
     */
    public function connect()
    {
    }

    /*
     * Disconnect from the message broker service
     *
     * @return void
     */
    public function disconnect()
    {
    }

    /**
     * Send a message to a broker service
     *
     * @param string $subject
     * @param string $collaborationId
     * @param mixed $body

     * @return void
     */
    public function sendMessage(string $subject, string $collaborationId, mixed $body)
    {
        $producer = Kafka::publishOn($subject)
        ->withConfigOptions([
            'message.max.bytes' => config('kafka.message_max_bytes', 10000000),
            'compression.codec' => config('kafka.compression_codec', 'gzip'),
        ])
        ->withHeaders(['collaborationId' => $collaborationId])
            ->withBodyKey('body', $body);

        // SASL Configuration
        if ($this->hasSaslConfig()) {
            $producer = $producer->withSasl($this->getSaslConfig());
        }

        $producer->send();
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
    public function worker()
    {
        // Create Kafka consumer
        $heartbeat = config('kafka.heartbeat_interval_ms', 3000);
        $prefix = config('kafka.prefix', '');
        $consumer = Kafka::createConsumer([$prefix . self::QUEUE_NAME])
            ->withOption('fetch.message.max.bytes', config('kafka.message_max_bytes', 10000000))
            ->withOption('compression.codec', config('kafka.compression_codec', 'gzip'))
            ->withOption('heartbeat.interval.ms', $heartbeat)
            ->withOption('session.timeout.ms', $heartbeat * 10);

        // SASL Configuration
        if ($this->hasSaslConfig()) {
            $consumer = $consumer->withSasl($this->getSaslConfig());
        }

        $consumer = $consumer->withHandler(function (KafkaConsumerMessage $message) {
            // Get transactions
            $transactions = $message->getBody();

            // Store transactions
            $this->storeData($transactions);
        })->build();

        // Consume incoming messages
        echo "\033[0;32m" . 'ProcessMaker consumer using kafka.' . "\033[0m" . PHP_EOL;
        $consumer->consume();
    }

    private function hasSaslConfig(): bool
    {
        return config('kafka.sasl_mechanisms') ? true : false;
    }

    private function getSaslConfig(): ?Sasl
    {
        if ($this->hasSaslConfig()) {
            return new Sasl(
                username: config('kafka.sasl_username'),
                password: config('kafka.sasl_password'),
                mechanisms: config('kafka.sasl_mechanisms'),
                securityProtocol: config('kafka.security_protocol'),
            );
        }

        return null;
    }

    /**
     * Store data
     *
     * @param array $transactions
     */
    private function storeData(array $transactions)
    {
        DBHelper::db_health_check();
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
        $prefix = config('kafka.prefix', '');
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
            $this->sendMessage($prefix . self::PROCESSES_QUEUE, '', ['type' => 'about', 'data' => $about]);
        } catch (Exception $e) {
            Log::error('Error sending about message', ['error' => $e->getMessage()]);
        }
    }
}
