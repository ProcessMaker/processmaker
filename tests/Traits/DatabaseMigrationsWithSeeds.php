<?php

namespace Tests\Traits;

use Illuminate\Contracts\Console\Kernel;

trait DatabaseMigrationsWithSeeds
{
    /**
     * Define hooks to migrate the database before and after each test.
     * Note, this also seeds the database after performing a fresh migration
     *
     * @return void
     */
    public function runDatabaseMigrations()
    {
        dd('RUNNING MIGRATE FRESH WITH THE SEED');
        $this->artisan('migrate:fresh --seed');

        $this->app[Kernel::class]->setArtisan(null);

        $this->beforeApplicationDestroyed(function () {
            $this->artisan('migrate:rollback');

            RefreshDatabaseState::$migrated = false;
        });
    }
}
