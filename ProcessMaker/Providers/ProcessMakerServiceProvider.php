<?php
namespace ProcessMaker\Providers;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use ProcessMaker\Managers\DatabaseManager;
use ProcessMaker\Managers\ProcessCategoryManager;
use ProcessMaker\Managers\ProcessFileManager;
use ProcessMaker\Managers\ReportTableManager;
use ProcessMaker\Managers\SchemaManager;
use ProcessMaker\Managers\TaskManager;
use ProcessMaker\Model\Group;
use ProcessMaker\Model\User;

/**
 * Provide our ProcessMaker specific services
 * @package ProcessMaker\Providers
 */
class ProcessMakerServiceProvider extends ServiceProvider
{

    /**
     * Bootstrap ProcessMaker services.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();
    }

    /**
     * Register our bindings in the service container
     */
    public function register()
    {
        $this->app->singleton('process_file.manager', function ($app) {
            return new ProcessFileManager();
        });

        $this->app->singleton('process_category.manager', function ($app) {
            return new ProcessCategoryManager();
        });

        $this->app->singleton('database.manager', function ($app) {
            return new DatabaseManager();
        });

        $this->app->singleton('schema.manager', function ($app) {
            return new SchemaManager();
        });

        $this->app->singleton('report_table.manager', function ($app) {
            return new ReportTableManager();
        });

        $this->app->singleton('task.manager', function ($app) {
            return new TaskManager();
        });

        Relation::morphMap([
            User::TYPE => User::class,
            Group::TYPE => Group::class,
        ]);
    }
}
