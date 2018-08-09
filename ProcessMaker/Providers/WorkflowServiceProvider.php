<?php
namespace ProcessMaker\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use ProcessMaker\BpmnEngine;
use ProcessMaker\Listeners\BpmnSubscriber;
use ProcessMaker\Managers\WorkflowManager;
use ProcessMaker\Nayra\Contracts\Storage\BpmnDocumentInterface;
use ProcessMaker\Nayra\Storage\BpmnDocument;
use ProcessMaker\Repositories\DefinitionsRepository;

class WorkflowServiceProvider extends ServiceProvider
{
    /**
     * ProcessMaker BPMN extension definitions.
     */
    const PROCESS_MAKER_NS = 'https://bpm4.processmaker.local/definition/ProcessMaker.xsd';

    /**
     * The subscriber classes to register.
     *
     * @var array
     */
    protected $subscribe = [
        BpmnSubscriber::class,
    ];

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        /**
         * BPMN Workflow Manager
         */
        $this->app->singleton('workflow.manager', function ($app) {
            return new WorkflowManager();
        });
        /** 
         * BpmnDocument Process Context
         */
        $this->app->bind(BpmnDocumentInterface::class, function ($app, $params) {
            $repository = new DefinitionsRepository();
            $eventBus = app('events');

            //Initialize the BpmnEngine
            $engine = new BpmnEngine($repository, $eventBus);

            //Initialize BpmnDocument repository (REQUIRES $engine $factory)
            $bpmnRepository = new BpmnDocument();
            $bpmnRepository->setEngine($engine);
            $bpmnRepository->setFactory($repository);
            $engine->setStorage($bpmnRepository);
            $engine->setProcess($params['process']);

            //Initialize custom properties for ProcessMaker
            $bpmnRepository->setBpmnElementMapping(self::PROCESS_MAKER_NS, '', []);

            return $bpmnRepository;
        });
    }
}
