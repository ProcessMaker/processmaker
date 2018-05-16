<?php

namespace ProcessMaker\Providers;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use League\Fractal\Manager;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;
use League\Fractal\Serializer\SerializerAbstract;
use League\Fractal\TransformerAbstract;
use ProcessMaker\Managers\DatabaseManager;
use ProcessMaker\Managers\DynaformManager;
use ProcessMaker\Managers\ProcessCategoryManager;
use ProcessMaker\Managers\ProcessFileManager;
use ProcessMaker\Managers\ProcessManager;
use ProcessMaker\Managers\ReportTableManager;
use ProcessMaker\Managers\SchemaManager;
use ProcessMaker\Managers\TriggerManager;
use ProcessMaker\Model\Activity;
use ProcessMaker\Model\Artifact;
use ProcessMaker\Model\Diagram;
use ProcessMaker\Model\Event;
use ProcessMaker\Model\Gateway;
use ProcessMaker\Model\Lane;
use ProcessMaker\Model\Laneset;
use ProcessMaker\Model\Participant;
use ProcessMaker\Model\Pool;

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

        /**
         * Prepare the response of an item using fractal
         */
        response()->macro('item', function ($item, TransformerAbstract $transformer, $status = 200, array $headers = [], SerializerAbstract $serializer = null) {
            /**
             * @var Manager $fractal
             */
            $fractal = new Manager();
            if (!$serializer) {
                $serialize = config('app.serialize_fractal');
                $serializer = new $serialize();
            }
            $resource = new Item($item, $transformer);
            $fractal->setSerializer($serializer);

            return response()->json(
                $fractal->createData($resource)->toArray(),
                $status,
                $headers
            );
        });

        /**
         * Prepare the response of the paginate collection using fractal, for compatibility.
         */
        response()->macro('collection', function ($item, TransformerAbstract $transformer, $status = 200, array $headers = [], SerializerAbstract $serializer = null, IlluminatePaginatorAdapter $paginator = null) {
            /**
             * @var Manager $fractal
             */
            $fractal = new Manager();
            if (!$serializer) {
                $serialize = config('app.serialize_fractal');
                $serializer = new $serialize(true);
            }
            $fractal->setSerializer($serializer);
            $resource = new Collection($item, $transformer);

            if (!$paginator) {
                $paginate = config('app.paginate_fractal');
                $paginator = new $paginate($item);
            }
            $resource->setPaginator($paginator);

            return response()->json(
                $fractal->createData($resource)->toArray(),
                $status,
                $headers
            );
        });
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
        $this->app->singleton('report_table.manager', function ($app) {
            return new ReportTableManager();
        });

        $this->app->singleton('dynaform.manager', function ($app) {
            return new DynaformManager();
        });

       $this->app->singleton('trigger.manager', function ($app) {
            return new TriggerManager();
        });

    }
}
