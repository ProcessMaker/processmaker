<?php
namespace ProcessMaker\Providers;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use League\Fractal\Manager;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use League\Fractal\Pagination\PaginatorInterface;
use League\Fractal\Resource\Collection;
use League\Fractal\TransformerAbstract;
use League\Fractal\Resource\Item;
use ProcessMaker\Managers\DatabaseManager;
use ProcessMaker\Managers\ProcessCategoryManager;
use ProcessMaker\Managers\ProcessFileManager;
use ProcessMaker\Managers\ProcessManager;
use ProcessMaker\Managers\ReportTableManager;
use ProcessMaker\Managers\SchemaManager;
use ProcessMaker\Model\Activity;
use ProcessMaker\Model\Artifact;
use ProcessMaker\Model\Diagram;
use ProcessMaker\Model\Event;
use ProcessMaker\Model\Gateway;
use ProcessMaker\Model\Lane;
use ProcessMaker\Model\Laneset;
use ProcessMaker\Model\Participant;
use ProcessMaker\Model\Pool;
use ProcessMaker\Transformers\ProcessMakerSerializer;

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

        $fractal = new Manager();
        $fractal->setSerializer(new ProcessMakerSerializer());

        response()->macro('item', function ($item, TransformerAbstract $transformer, $status = 200, array $headers = []) use ($fractal) {
            $resource = new Item($item, $transformer);

            return response()->json(
                $fractal->createData($resource)->toArray(),
                $status,
                $headers
            );
        });

        response()->macro('collection', function ($item, TransformerAbstract $transformer, $status = 200, array $headers = []) use ($fractal) {
            $resource = new Collection($item, $transformer);

            return response()->json(
                $fractal->createData($resource)->toArray(),
                $status,
                $headers
            );
        });

        response()->macro('paged', function ($item, TransformerAbstract $transformer, $status = 200, array $headers = []) use ($fractal) {
            $fractal->setSerializer(new ProcessMakerSerializer(true));

            $resource = new Collection($item, $transformer);

            $resource->setPaginator(new IlluminatePaginatorAdapter($item));

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
        ]);

        $this->app->singleton('report_table.manager', function ($app) {
            return new ReportTableManager();
        });
    }
}
