<?php
use Ramsey\Uuid\Uuid;
use Faker\Generator as Faker;
use ProcessMaker\Model\Process;
use Illuminate\Support\Facades\Crypt;

/**
 * Model factory for an external Database
 */
$factory->define(\ProcessMaker\Model\DbSource::class, function (Faker $faker) {

    /**
     * @todo Determine if we need more base columns populated
     */
    return [
        'process_id' => function () {
            return factory(Process::class)->create()->id;
        },
        'type' => 'mysql',
        'server' => $faker->localIpv4,
        'database_name' => $faker->word,
        'username' => $faker->userName,
        'password' => Crypt::encryptString($faker->password),
        'port' => $faker->numberBetween(1000, 9000),
        'encode' => 'utf8', // @todo Perhaps grab this from our definitions in DbConnections
        'connection_type' => 'NORMAL', // @todo Determine what this value means
        'description' => $faker->sentence()
    ];
});
