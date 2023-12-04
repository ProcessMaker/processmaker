<?php

namespace ProcessMaker\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\InteractsWithTime;
use Illuminate\Support\Str;
use ProcessMaker\Facades\MessageBrokerService;

class RabbitmqRestartConsumers extends Command
{
    use InteractsWithTime;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rabbitmq:restart-consumers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Restart all RabbitMQ consumers.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (config('app.message_broker_driver') !== 'rabbitmq') {
            return;
        }
        Cache::forever('processmaker-rabbitmq:consumer:restart', $this->currentTime());
        MessageBrokerService::sendMessage('ping', '', '');
    }
}
