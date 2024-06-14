<?php

namespace Tests\Feature\Console;

use Faker\Factory as Faker;
use Illuminate\Support\Facades\Artisan;
use Tests\Feature\Shared\InstallParameter as Param;
use Tests\TestCase;

class InstallTest extends TestCase
{
    /**
     * Parse the table output of the Artisan command
     *
     * @return array
     */
    private function parseOutput($output)
    {
        $matchCount = preg_match_all('/\|\s+(\w+)\s+\|\s+(\S+)\s+/', $output, $matches);

        $lines = [];

        if ($matchCount) {
            foreach ($matches[0] as $key => $value) {
                $lines[$matches[1][$key]] = $matches[2][$key];
            }
        }

        return $lines;
    }

    /**
     * Generate parameters to feed into the console command
     *
     * @return Illuminate\Support\Collection
     */
    private function generateParameters()
    {
        $faker = Faker::create();

        $params = collect([]);
        $params->push(new Param('--pretend'));
        $params->push(new Param('--no-interaction'));
        $params->push(new Param('--app-debug', 'APP_DEBUG', $faker->boolean()));
        $params->push(new Param('--url', 'APP_URL', "https://{$faker->domainName()}"));
        $params->push(new Param('--username', null, $faker->userName()));
        $params->push(new Param('--password', null, $faker->word()));
        $params->push(new Param('--email', null, $faker->safeEmail()));
        $params->push(new Param('--telescope', 'TELESCOPE_ENABLED', $faker->boolean()));
        $params->push(new Param('--db-host', 'DB_HOST', $faker->ipv4()));
        $params->push(new Param('--db-port', 'DB_PORT', $faker->numberBetween(1, 9999)));
        $params->push(new Param('--db-name', 'DB_DATABASE', $faker->word()));
        $params->push(new Param('--db-username', 'DB_USERNAME', $faker->userName()));
        $params->push(new Param('--db-password', 'DB_PASSWORD', $faker->word()));
        $params->push(new Param('--redis-client', 'REDIS_CLIENT', $faker->randomElement(['phpredis', 'predis'])));
        $params->push(new Param('--redis-prefix', 'REDIS_PREFIX', "{$faker->word()}:"));
        $params->push(new Param('--horizon-prefix', 'HORIZON_PREFIX', "{$faker->word()}:"));
        $params->push(new Param('--broadcast-debug', 'LARAVEL_ECHO_SERVER_DEBUG', $faker->boolean()));
        $params->push(new Param('--broadcast-driver', 'BROADCAST_DRIVER', $faker->randomElement(['pusher', 'redis'])));
        $params->push(new Param('--broadcast-host', 'BROADCASTER_HOST', "https://{$faker->domainName()}"));
        $params->push(new Param('--broadcast-key', 'BROADCASTER_KEY', $faker->md5()));
        $params->push(new Param('--echo-host', 'LARAVEL_ECHO_SERVER_AUTH_HOST', "https://{$faker->domainName()}"));
        $params->push(new Param('--echo-port', 'LARAVEL_ECHO_SERVER_PORT', $faker->numberBetween(1, 9999)));
        $params->push(new Param('--pusher-app-id', 'PUSHER_APP_ID', $faker->word()));
        $params->push(new Param('--pusher-app-key', 'PUSHER_APP_KEY', $faker->md5()));
        $params->push(new Param('--pusher-app-secret', 'PUSHER_APP_SECRET', $faker->sha1()));
        $params->push(new Param('--pusher-cluster', 'PUSHER_CLUSTER', $faker->word()));
        $params->push(new Param('--pusher-tls', 'PUSHER_TLS', $faker->boolean()));

        return $params;
    }

    /**
     * Test to determine if non-interactive install works
     *
     * @return void
     */
    public function testNonInteractiveInstall()
    {
        // Setup our collection of parameters
        $params = $this->generateParameters();

        // Setup arguments in a format acceptable to Artisan::call()
        $arguments = [];
        foreach ($params as $param) {
            $arguments[$param->flag] = $param->value;
        }

        // Make the call
        $returnCode = Artisan::call('processmaker:install', $arguments);

        // Assert the return code is correct
        $this->assertEquals(0, $returnCode);

        // Retrieve the output of the call
        $output = $this->parseOutput(Artisan::output());

        // For each of the params...
        foreach ($params as $param) {
            // If there's a corresponding item in the env file...
            if ($param->env) {
                // Set the output value
                $outputValue = $output[$param->env];

                // Convert the output value if it should be boolean
                if (is_bool($param->value)) {
                    $outputValue = filter_var($outputValue, FILTER_VALIDATE_BOOLEAN);
                }

                // Assert the value equals the output
                $this->assertEquals($param->value, $outputValue);
            }
        }
    }
}
