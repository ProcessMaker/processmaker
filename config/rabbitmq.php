<?php

$_port = env('RABBITMQ_PORT', 5672);
if (preg_match('/\d+$/', $_port, $_match)) {
    $_port = intval($_match[0]) ?: 5672;
}

return [
    /*
     * Set to "horizon" if you wish to use Laravel Horizon.
     */
    'worker' => env('RABBITMQ_WORKER', 'default'),

    'dsn' => env('RABBITMQ_DSN', null),

    /*
     * Could be one a class that implements \Interop\Amqp\AmqpConnectionFactory for example:
     *  - \EnqueueAmqpExt\AmqpConnectionFactory if you install enqueue/amqp-ext
     *  - \EnqueueAmqpLib\AmqpConnectionFactory if you install enqueue/amqp-lib
     *  - \EnqueueAmqpBunny\AmqpConnectionFactory if you install enqueue/amqp-bunny
     */

    'factory_class' => Enqueue\AmqpLib\AmqpConnectionFactory::class,

    'host' => env('RABBITMQ_HOST', '127.0.0.1'),
    'port' => $_port,

    'vhost' => env('RABBITMQ_VHOST', '/'),
    'login' => env('RABBITMQ_LOGIN', 'guest'),
    'password' => env('RABBITMQ_PASSWORD', 'guest'),

    'queue' => env('RABBITMQ_QUEUE', 'default'),

    'options' => [

        'exchange' => [

            'name' => env('RABBITMQ_EXCHANGE_NAME'),

            /*
             * Determine if exchange should be created if it does not exist.
             */

            'declare' => env('RABBITMQ_EXCHANGE_DECLARE', true),

            /*
             * Read more about possible values at https://www.rabbitmq.com/tutorials/amqp-concepts.html
             */

            'type' => env('RABBITMQ_EXCHANGE_TYPE', \PhpAmqpLib\Exchange\AMQPExchangeType::DIRECT),
            'passive' => env('RABBITMQ_EXCHANGE_PASSIVE', false),
            'durable' => env('RABBITMQ_EXCHANGE_DURABLE', true),
            'auto_delete' => env('RABBITMQ_EXCHANGE_AUTODELETE', false),
            'arguments' => env('RABBITMQ_EXCHANGE_ARGUMENTS'),
        ],

        'queue' => [

            /*
             * Determine if queue should be created if it does not exist.
             */

            'declare' => env('RABBITMQ_QUEUE_DECLARE', true),

            /*
             * Determine if queue should be binded to the exchange created.
             */

            'bind' => env('RABBITMQ_QUEUE_DECLARE_BIND', true),

            /*
             * Read more about possible values at https://www.rabbitmq.com/tutorials/amqp-concepts.html
             */

            'passive' => env('RABBITMQ_QUEUE_PASSIVE', false),
            'durable' => env('RABBITMQ_QUEUE_DURABLE', true),
            'exclusive' => env('RABBITMQ_QUEUE_EXCLUSIVE', false),
            'auto_delete' => env('RABBITMQ_QUEUE_AUTODELETE', false),
            'arguments' => env('RABBITMQ_QUEUE_ARGUMENTS'),
        ],
    ],

    /*
     * Determine the number of seconds to sleep if there's an error communicating with rabbitmq
     * If set to false, it'll throw an exception rather than doing the sleep for X seconds.
     */

    'sleep_on_error' => env('RABBITMQ_ERROR_SLEEP', 5),

    /*
     * Optional SSL params if an SSL connection is used
     * Using an SSL connection will also require to configure your RabbitMQ to enable SSL. More details can be founds here: https://www.rabbitmq.com/ssl.html
     */

    'ssl_params' => [
        'ssl_on' => env('RABBITMQ_SSL', false),
        'cafile' => env('RABBITMQ_SSL_CAFILE', null),
        'local_cert' => env('RABBITMQ_SSL_LOCALCERT', null),
        'local_key' => env('RABBITMQ_SSL_LOCALKEY', null),
        'verify_peer' => env('RABBITMQ_SSL_VERIFY_PEER', true),
        'passphrase' => env('RABBITMQ_SSL_PASSPHRASE', null),
    ],
];
