<?php
namespace ProcessMaker\Providers;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use League\Fractal\TransformerAbstract;
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
use Spatie\Fractalistic\Fractal;

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
        response()->macro('item', function ($item, TransformerAbstract $transformer, $status = 200, array $headers = [], $serializer = '') {
            if (empty($serializer)) {
                $serialize = config('app.serialize_fractal');
                $serializer = new $serialize();
            }

            return response()->json(
                Fractal::create()
                    ->item($item)
                    ->serializeWith($serializer)
                    ->transformWith($transformer)
                    ->toArray(),
                $status,
                $headers
            );
        });

        /**
         * Prepare the response of collection using fractal
         */
        response()->macro('collection', function ($item, TransformerAbstract $transformer, $status = 200, array $headers = [], $serializer = null) {
            if (empty($serializer)) {
                $serialize = config('app.serialize_fractal');
                $serializer = new $serialize();
            }

            return response()->json(
                Fractal::create()
                    ->collection($item)
                    ->transformWith($transformer)
                    ->serializeWith($serializer)
                    ->toArray(),
                $status,
                $headers
            );
        });

        /**
         * Prepare the response of the paginate collection using fractal, for compatibility.
         */
        response()->macro('paged', function ($item, TransformerAbstract $transformer, $status = 200, array $headers = [], $serializer = null, $paginator = null) {
            if (empty($serializer)) {
                $serialize = config('app.serialize_fractal');
                $serializer = new $serialize(true);
            }

            if (empty($paginator)) {
                $paginate = config('app.paginate_fractal');
                /**
                 * @var IlluminatePaginatorAdapter $paginator
                 */
                $paginator = new $paginate($item);
            }

            return response()->json(
                Fractal::create()
                    ->collection($item)
                    ->transformWith($transformer)
                    ->serializeWith($serializer)
                    ->paginateWith($paginator)
                    ->toArray(),
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
