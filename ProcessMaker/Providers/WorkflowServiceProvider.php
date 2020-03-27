<?php

namespace ProcessMaker\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use ProcessMaker\Assets\ScreensInProcess;
use ProcessMaker\Assets\ScreensInScreen;
use ProcessMaker\Assets\ScriptsInProcess;
use ProcessMaker\Assets\ScriptsInScreen;
use ProcessMaker\BpmnEngine;
use ProcessMaker\Contracts\TimerExpressionInterface;
use ProcessMaker\Facades\WorkflowManager as WorkflowManagerFacade;
use ProcessMaker\Listeners\BpmnSubscriber;
use ProcessMaker\Listeners\CommentsSubscriber;
use ProcessMaker\Managers\ExportManager;
use ProcessMaker\Managers\TaskSchedulerManager;
use ProcessMaker\Managers\WorkflowManager;
use ProcessMaker\Nayra\Bpmn\Models\EventDefinitionBus;
use ProcessMaker\Nayra\Bpmn\Models\SignalEventDefinition;
use ProcessMaker\Nayra\Contracts\Bpmn\EventDefinitionInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\FlowNodeInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\FormalExpressionInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\StartEventInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\ThrowEventInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\TimerEventDefinitionInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\TokenInterface;
use ProcessMaker\Nayra\Contracts\Storage\BpmnDocumentInterface;
use ProcessMaker\Repositories\BpmnDocument;
use ProcessMaker\Repositories\DefinitionsRepository;

class WorkflowServiceProvider extends ServiceProvider
{
    /**
     * ProcessMaker BPMN extension definitions.
     */
    const PROCESS_MAKER_NS = 'http://processmaker.com/BPMN/2.0/Schema.xsd';

    /**
     * The subscriber classes to register.
     *
     * @var array
     */
    protected $subscribe = [
        BpmnSubscriber::class,
        CommentsSubscriber::class,
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
            $engine = empty($params['engine']) ? new BpmnEngine($repository, $eventBus) : $params['engine'];
            $eventDefinitionBus = new EventDefinitionBus;
            $engine->setEventDefinitionBus($eventDefinitionBus);

            // Catch the signal events
            if ($params['globalEvents']) {
                $eventDefinitionBus->attachEvent(
                    SignalEventDefinition::class,
                    function (ThrowEventInterface $source, EventDefinitionInterface $sourceEventDefinition, TokenInterface $token) {
                        WorkflowManagerFacade::catchSignalEvent($source, $sourceEventDefinition, $token);
                    }
                );
            }

            $engine->setJobManager(new TaskSchedulerManager());

            //Initialize BpmnDocument repository (REQUIRES $engine $factory)
            $bpmnRepository = new BpmnDocument($params['process']);
            $bpmnRepository->setEngine($engine);
            $bpmnRepository->setFactory($repository);
            $bpmnRepository->setSkipElementsNotImplemented(true);
            $mapping = $bpmnRepository->getBpmnElementsMapping();

            //Initialize custom properties for ProcessMaker
            $bpmnRepository->setBpmnElementMapping(self::PROCESS_MAKER_NS, '', []);
            $bpmnRepository->setBpmnElementMapping(BpmnDocument::BPMN_MODEL, 'userTask', $mapping[BpmnDocument::BPMN_MODEL]['task']);
            $bpmnRepository->setBpmnElementMapping(BpmnDocument::BPMN_MODEL, 'manualTask', $mapping[BpmnDocument::BPMN_MODEL]['task']);
            $bpmnRepository->setBpmnElementMapping(BpmnDocument::BPMN_MODEL, 'association', BpmnDocument::SKIP_ELEMENT);
            $bpmnRepository->setBpmnElementMapping(BpmnDocument::BPMN_MODEL, 'textAnnotation', BpmnDocument::SKIP_ELEMENT);

            $bpmnRepository->setBpmnElementMapping(
                BpmnDocument::BPMN_MODEL,
                'startEvent',
                [
                    StartEventInterface::class,
                    [
                        FlowNodeInterface::BPMN_PROPERTY_INCOMING => ['n', [BpmnDocument::BPMN_MODEL, FlowNodeInterface::BPMN_PROPERTY_INCOMING]],
                        FlowNodeInterface::BPMN_PROPERTY_OUTGOING => ['n', [BpmnDocument::BPMN_MODEL, FlowNodeInterface::BPMN_PROPERTY_OUTGOING]],
                        StartEventInterface::BPMN_PROPERTY_EVENT_DEFINITIONS => ['n', EventDefinitionInterface::class],
                    ]
                ]
            );

            $bpmnRepository->setBpmnElementMapping(
                BpmnDocument::BPMN_MODEL,
                'timerEventDefinition',
                [TimerEventDefinitionInterface::class,
                    [
                        TimerEventDefinitionInterface::BPMN_PROPERTY_TIME_DATE => ['1', [BpmnDocument::BPMN_MODEL, TimerEventDefinitionInterface::BPMN_PROPERTY_TIME_DATE]],
                        TimerEventDefinitionInterface::BPMN_PROPERTY_TIME_CYCLE => ['1', [BpmnDocument::BPMN_MODEL, TimerEventDefinitionInterface::BPMN_PROPERTY_TIME_CYCLE]],
                        TimerEventDefinitionInterface::BPMN_PROPERTY_TIME_DURATION => ['1', [BpmnDocument::BPMN_MODEL, TimerEventDefinitionInterface::BPMN_PROPERTY_TIME_DURATION]],
                    ]
                ]
            );

            $bpmnRepository->setBpmnElementMapping(
                BpmnDocument::BPMN_MODEL,
                TimerEventDefinitionInterface::BPMN_PROPERTY_TIME_CYCLE,
                [
                    TimerExpressionInterface::class,
                    [
                        FormalExpressionInterface::BPMN_PROPERTY_BODY => ['1', BpmnDocument::DOM_ELEMENT_BODY],
                    ]
                ]
            );

            $bpmnRepository->setBpmnElementMapping(
                BpmnDocument::BPMN_MODEL,
                TimerEventDefinitionInterface::BPMN_PROPERTY_TIME_DATE,
                [
                    TimerExpressionInterface::class,
                    [
                        FormalExpressionInterface::BPMN_PROPERTY_BODY => ['1', BpmnDocument::DOM_ELEMENT_BODY],
                    ]
                ]
            );
            $bpmnRepository->setBpmnElementMapping(
                BpmnDocument::BPMN_MODEL,
                TimerEventDefinitionInterface::BPMN_PROPERTY_TIME_DURATION,
                [
                    TimerExpressionInterface::class,
                    [
                        FormalExpressionInterface::BPMN_PROPERTY_BODY => ['1', BpmnDocument::DOM_ELEMENT_BODY],
                    ]
                ]
            );

            // Override the CallActivity Definition
            $bpmnRepository->setBpmnElementMapping(
                BpmnDocument::BPMN_MODEL,
                'callActivity',
                [
                    CallActivityInterface::class,
                    [
                        FlowNodeInterface::BPMN_PROPERTY_INCOMING => ['n', [BpmnDocument::BPMN_MODEL, FlowNodeInterface::BPMN_PROPERTY_INCOMING]],
                        FlowNodeInterface::BPMN_PROPERTY_OUTGOING => ['n', [BpmnDocument::BPMN_MODEL, FlowNodeInterface::BPMN_PROPERTY_OUTGOING]],
                    ]
                ]
            );
            return $bpmnRepository;
        });
        /**
         * Export Manager
         */
        $this->app->singleton(ExportManager::class, function () {
            $instance = new ExportManager;
            $instance->addDependencieManager(ScreensInProcess::class);
            $instance->addDependencieManager(ScreensInScreen::class);
            $instance->addDependencieManager(ScriptsInProcess::class);
            $instance->addDependencieManager(ScriptsInScreen::class);
            return $instance;
        });
    }
}
