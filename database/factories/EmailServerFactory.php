<?php

use ProcessMaker\Model\EmailServer;
use ProcessMaker\Model\Process;
use Ramsey\Uuid\Uuid;

/**
 * Model factory for a Task
 */

use Faker\Generator as Faker;

$factory->define(EmailServer::class, function (Faker $faker) {

    return [
        'uid' => Uuid::uuid4(),
        'process_id' => function () {
            return factory(Process::class)->create()->id;
        },
        'from_name' => $faker->sentence(5),
        'account' => $faker->freeEmail,
        'password' => $faker->password,
        'from_name' => $faker->freeEmail,
        'mail_to' => $faker->freeEmail,
        'engine' => $faker->randomElement([EmailServer::TYPE_MAIL, EmailServer::TYPE_PHP_MAILER]),
        'smtp_secure' => $faker->randomElement([EmailServer::NO_SECURE, EmailServer::SSL_SECURE, EmailServer::TLS_SECURE]),
    ];
});
