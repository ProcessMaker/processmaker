<?php

namespace Tests\Feature\OnSiteStorage;

use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Tests\CreatesApplication;

class OnSiteStorageMigrationsTest extends TestCase
{
    use CreatesApplication;

    /**
     * Verifies that migrations create the core tables that can be moved to and external database
     */
    public function testMigrations()
    {
        // Drop the tables created in the migrations
        Schema::connection('data')->dropIfExists('comments');
        Schema::connection('data')->dropIfExists('process_requests');

        // Run the migrations that create the tables
        Artisan::call('migrate:refresh',
                    array('--path' => 'database/migrations/2019_01_14_201209_create_comments_table.php',
                            '--force' => true));

        Artisan::call('migrate:refresh',
            array('--path' => 'database/migrations/2018_09_07_174154_create_process_requests_table.php',
                '--force' => true));

        // Assert that the migrations created the tables
        $this->assertEquals(True, Schema::connection('data')->hasTable('comments'));
        $this->assertEquals(True, Schema::connection('data')->hasTable('process_requests'));
    }
}
