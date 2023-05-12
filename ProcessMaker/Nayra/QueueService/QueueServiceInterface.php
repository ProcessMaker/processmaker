<?php

namespace ProcessMaker\Nayra\QueueService;

interface QueueServiceInterface
{
    /**
     * Connect to the queue service
     *
     * @return void
     */
    public function config(): void;

    /**
     * Send a message to a queue
     *
     * @param string $subject
     * @param string $collaborationId
     * @param mixed $body
     * @return void
     */
    public function sendMessage(string $subject, string $collaborationId, mixed $body): void;

    /**
     * Receive a message from a queue
     *
     * @param string $queueName
     * @return string
     */
    public function receiveMessage(string $queueName): string;
}
