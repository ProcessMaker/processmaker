<?php

namespace ProcessMaker\Nayra\MessageBrokers;

use Junges\Kafka\Contracts\KafkaConsumerMessage;
use Junges\Kafka\Facades\Kafka;
use ProcessMaker\Nayra\Repositories\PersistenceHandler;

class ServiceKafka
{
    const QUEUE_NAME = 'nayra-store';

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
            ->withHeaders(['collaborationId' => $collaborationId])
            ->withBodyKey('body', $body);
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
        $consumer = Kafka::createConsumer([self::QUEUE_NAME])->withHandler(function (KafkaConsumerMessage $message) {
            // Get transactions
            $transactions = $message->getBody();

            // Store transactions
            $this->storeData($transactions);
        })->build();

        // Consume incoming messages
        $consumer->consume();
    }

    /**
     * Store data
     *
     * @param array $transactions
     */
    private function storeData(array $transactions)
    {
        $handler = new PersistenceHandler();
        foreach ($transactions as $transaction) {
            $handler->save($transaction);
        }
    }
}
