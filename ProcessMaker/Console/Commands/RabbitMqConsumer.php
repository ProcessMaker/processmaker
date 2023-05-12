<?php

namespace ProcessMaker\Console\Commands;

use Illuminate\Console\Command;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use ProcessMaker\Managers\Nayra\EntityRepositoryFactory;

class RabbitMqConsumer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rabbitmq:consumer';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $rabbitmqHost = '192.168.39.78';
        $rabbitmqPort = 30672;
        $rabbitmqUser = 'guest';
        $rabbitmqPassword = 'guest';
        $connection = new AMQPStreamConnection($rabbitmqHost, $rabbitmqPort, $rabbitmqUser, $rabbitmqPassword);
        $channel = $connection->channel();
        $queueName = 'nayra-store';
        $channel->queue_declare($queueName, false, true, false, false);
        $callback = function ($msg) use ($channel) {
            error_log('message ' . $msg->body);
            $transactions = json_decode($msg->body, true);
            $this->storeData($transactions, 0);
            $msg->ack();
        };

        // Consume messages from the queue
        $channel->basic_qos(null, 1, null);
        $channel->basic_consume($queueName, '', false, false, false, false, $callback);

        // Wait for incoming messages
        while ($channel->is_consuming()) {
            $channel->wait();
        }

        $channel->close();
        $connection->close();

        return 0;
    }

    private function storeData(array $transactions, int $partition)
    {
        foreach ($transactions as $transaction) {
            EntityRepositoryFactory::createRepository($transaction['entity'])
                ->save($transaction);
        }
    }
}
