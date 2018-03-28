<?php
use Ramsey\Uuid\Uuid;
use Faker\Generator as Faker;
use Illuminate\Support\Facades\Crypt;

/**
 * Model factory for an external Database
 */
$factory->define(\ProcessMaker\Model\DbSource::class, function (Faker $faker) {

    /**
     * @todo Determine if we need more base columns populated
     */
    return [
        'DBS_UID' => str_replace('-', '', Uuid::uuid4()),
        'PRO_UID' => function () {
            return factory(\ProcessMaker\Model\Process::class)->create()->PRO_UID;
        },
        'DBS_TYPE' => 'mysql',
        'DBS_SERVER' => $faker->localIpv4,
        'DBS_DATABASE_NAME' => $faker->word,
        'DBS_USERNAME' => $faker->userName,
        /**
         * @todo WHY figure out there's a magic value to the encryption here
         */
        'DBS_PASSWORD' => Crypt::encryptString($faker->password),
        'DBS_PORT' => $faker->numberBetween(1000, 9000),
        'DBS_ENCODE' => 'utf8', // @todo Perhaps grab this from our definitions in DbConnections
        'DBS_CONNECTION_TYPE' => 'NORMAL', // @todo Determine what this value means
        'DBS_TNS' => null, // @todo Determine what this value means
        'DBS_DESCRIPTION' => $faker->sentence()
    ];
});
