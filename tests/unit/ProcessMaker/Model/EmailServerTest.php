<?php

namespace Tests\Unit;

use Faker\Factory as Faker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use ProcessMaker\Model\EmailServer;
use Tests\TestCase;

class EmailServerTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Tests create email server
     */
    public function testCreateEmailServer(): void
    {
        $faker = Faker::create();
        $engine = $faker->randomElement([EmailServer::TYPE_MAIL, EmailServer::TYPE_PHP_MAILER]);
        $secure = $faker->randomElement([EmailServer::NO_SECURE, EmailServer::SSL_SECURE, EmailServer::TLS_SECURE]);

        $server = factory(EmailServer::class)->create([
            'engine' => $engine,
            'smtp_secure' => $secure

        ]);

        $this->assertGreaterThan(0, $server->id);
        $this->assertEquals($engine, $server->engine);
        $this->assertEquals($secure, $server->smtp_secure);

    }

}