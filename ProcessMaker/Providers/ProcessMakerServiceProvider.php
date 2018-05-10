<?php

namespace ProcessMaker\Providers;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use ProcessMaker\Managers\DatabaseManager;
use ProcessMaker\Managers\ProcessCategoryManager;
use ProcessMaker\Managers\ProcessFileManager;
use ProcessMaker\Managers\ProcessManager;
use ProcessMaker\Managers\ReportTableManager;
use ProcessMaker\Managers\SchemaManager;
use ProcessMaker\Managers\TaskManager;
use ProcessMaker\Model\Activity;
use ProcessMaker\Model\Artifact;
use ProcessMaker\Model\Diagram;
use ProcessMaker\Model\Event;
use ProcessMaker\Model\Gateway;
use ProcessMaker\Model\Group;
use ProcessMaker\Model\Lane;
use ProcessMaker\Model\Laneset;
use ProcessMaker\Model\Participant;
use ProcessMaker\Model\Pool;
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

        $this->app->singleton('process.manager', function ($app) {
            return new ProcessManager();
        });
        /**
         * Mapping of shape and elements used in BPMN_BOUND.BOU_ELEMENT_TYPE
         *
         */
        Relation::morphMap([
            Activity::TYPE    => Activity::class,
            Artifact::TYPE    => Artifact::class,
            'bpmnData'        => Activity::class,
            Diagram::TYPE     => Diagram::class,
            Event::TYPE       => Event::class,
            Gateway::TYPE     => Gateway::class,
            Lane::TYPE        => Lane::class,
            Laneset::TYPE     => Laneset::class,
            'bpmnParticipant' => Participant::class,
            'bpmnPool'        => Pool::class,
            User::TYPE        => User::class,
            Group::TYPE       => Group::class,

        ]);

        $this->app->singleton('report_table.manager', function ($app) {
            return new ReportTableManager();
        });

        $this->app->singleton('task.manager', function ($app) {
            return new TaskManager();
        });

    }
}
