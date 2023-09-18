<?php

namespace ProcessMaker\Nayra\MessageBrokers;

interface ServiceInterface
{
    /**
     * Connect to the message broker service
     *
     * @return void
     */
    public function connect(): void;

    /*
     * Disconnect from the message broker service
     *
     * @return void
     */
    public function disconnect(): void;

    /**
     * Send a message to a broker service
     *
     * @param string $subject
     * @param string $collaborationId
     * @param mixed $body
     * @return void
     */
    public function sendMessage(string $subject, string $collaborationId, mixed $body): void;

    /**
     * Receive a message from a broker service
     *
     * @param string $queueName
     * @return string
     */
    public function receiveMessage(string $queueName): string;

    /**
     * Run worker
     */
    public function worker(): void;
}
