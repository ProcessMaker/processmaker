<?php

namespace ProcessMaker\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Mustache_Engine;
use Mustache_LambdaHelper;
use ProcessMaker\Assets\ScreensInProcess;
use ProcessMaker\Assets\ScreensInScreen;
use ProcessMaker\Assets\ScriptsInProcess;
use ProcessMaker\Assets\ScriptsInScreen;
use ProcessMaker\Bpmn\MustacheOptions;
use ProcessMaker\BpmnEngine;
use ProcessMaker\Contracts\TimerExpressionInterface;
use ProcessMaker\Facades\WorkflowManager as WorkflowManagerFacade;
use ProcessMaker\Factories\SoapClientFactory;
use ProcessMaker\Listeners\BpmnSubscriber;
use ProcessMaker\Listeners\CommentsSubscriber;
use ProcessMaker\Managers\ExportManager;
use ProcessMaker\Managers\TaskSchedulerManager;
use ProcessMaker\Managers\WorkflowManager;
use ProcessMaker\Models\FormalExpression;
use ProcessMaker\Models\SignalEventDefinition;
use ProcessMaker\Nayra\Bpmn\Models\EventDefinitionBus;
use ProcessMaker\Nayra\Contracts\Bpmn\CallActivityInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\EventDefinitionInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\FlowNodeInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\FormalExpressionInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\StartEventInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\ThrowEventInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\TimerEventDefinitionInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\TokenInterface;
use ProcessMaker\Nayra\Contracts\Engine\EngineInterface;
use ProcessMaker\Nayra\Contracts\Storage\BpmnDocumentInterface;
use ProcessMaker\Nayra\MessageBrokers\Service as ServiceFactory;
use ProcessMaker\Nayra\MessageBrokers\ServiceInterface;
use ProcessMaker\Repositories\BpmnDocument;
use ProcessMaker\Repositories\DefinitionsRepository;
use ProcessMaker\WebServices\Contracts\SoapClientInterface;
use ProcessMaker\WebServices\NativeSoapClient;
use ProcessMaker\WebServices\SoapConfigBuilder;
use ProcessMaker\WebServices\SoapRequestBuilder;
use ProcessMaker\WebServices\SoapResponseMapper;
use ProcessMaker\WebServices\SoapServiceCaller;
use ProcessMaker\WebServices\WebServiceRequest;

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
            return WorkflowManager::create();
        });

        $this->app->bind(BpmnEngine::class, function ($app, $params) {
            $definitions = $params['definitions'];
            $globalEvents = $params['globalEvents'] ?? true;
            $repository = $definitions->getFactory();
            $eventBus = app('events');
            //Initialize the BpmnEngine
            $engine = new BpmnEngine($repository, $eventBus);
            $eventDefinitionBus = new EventDefinitionBus;
            $engine->setEventDefinitionBus($eventDefinitionBus);
            // Catch the signal events
            if ($globalEvents) {
                $eventDefinitionBus->attachEvent(
                    SignalEventDefinition::class,
                    function (ThrowEventInterface $source, EventDefinitionInterface $sourceEventDefinition, TokenInterface $token) {
                        WorkflowManagerFacade::throwSignalEventDefinition($sourceEventDefinition, $token);
                    }
                );
            }
            $engine->setJobManager(new TaskSchedulerManager());
            $definitions->setEngine($engine);
            $engine->loadProcessDefinitions($definitions);

            return $engine;
        });

        /**
         * BpmnDocument Process Context
         */
        $this->app->bind(BpmnDocumentInterface::class, function ($app, $params) {
            $repository = new DefinitionsRepository();
            $engine = $params['engine'] ?? new BpmnEngine($repository, app('events'));

            //Initialize BpmnDocument repository (REQUIRES $engine $factory)
            $bpmnRepository = new BpmnDocument($params['process']);
            $processVersion = $params['process_version'] ?? null;
            if ($engine) {
                if (!$engine->getJobManager()) {
                    $engine->setJobManager(new TaskSchedulerManager());
                }
                $bpmnRepository->setEngine($engine);
            }
            if ($processVersion) {
                $bpmnRepository->setProcessVersion($processVersion);
            }
            $bpmnRepository->getFactory();
            $bpmnRepository->setFactory($repository);
            $bpmnRepository->setSkipElementsNotImplemented(true);
            $mapping = $bpmnRepository->getBpmnElementsMapping();

            //Initialize custom properties for ProcessMaker
            $bpmnRepository->setBpmnElementMapping(self::PROCESS_MAKER_NS, '', []);
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
                    ],
                ]
            );

            $bpmnRepository->setBpmnElementMapping(
                BpmnDocument::BPMN_MODEL,
                TimerEventDefinitionInterface::BPMN_PROPERTY_TIME_CYCLE,
                [
                    TimerExpressionInterface::class,
                    [
                        FormalExpressionInterface::BPMN_PROPERTY_BODY => ['1', BpmnDocument::DOM_ELEMENT_BODY],
                    ],
                ]
            );

            $bpmnRepository->setBpmnElementMapping(
                BpmnDocument::BPMN_MODEL,
                TimerEventDefinitionInterface::BPMN_PROPERTY_TIME_DATE,
                [
                    TimerExpressionInterface::class,
                    [
                        FormalExpressionInterface::BPMN_PROPERTY_BODY => ['1', BpmnDocument::DOM_ELEMENT_BODY],
                    ],
                ]
            );
            $bpmnRepository->setBpmnElementMapping(
                BpmnDocument::BPMN_MODEL,
                TimerEventDefinitionInterface::BPMN_PROPERTY_TIME_DURATION,
                [
                    TimerExpressionInterface::class,
                    [
                        FormalExpressionInterface::BPMN_PROPERTY_BODY => ['1', BpmnDocument::DOM_ELEMENT_BODY],
                    ],
                ]
            );

            // Remove reference check for CallActivity::calledElement
            $callActivityMap = $mapping[BpmnDocument::BPMN_MODEL]['callActivity'];
            unset($callActivityMap[1][CallActivityInterface::BPMN_PROPERTY_CALLED_ELEMENT]);
            $bpmnRepository->setBpmnElementMapping(
                BpmnDocument::BPMN_MODEL,
                'callActivity',
                $callActivityMap
            );

            return $bpmnRepository;
        });
        /**
         * Export Manager
         */
        $this->app->singleton(ExportManager::class, function () {
            $instance = new ExportManager;
            $instance->addDependencyManager(ScreensInProcess::class);
            $instance->addDependencyManager(ScreensInScreen::class);
            $instance->addDependencyManager(ScriptsInProcess::class);
            $instance->addDependencyManager(ScriptsInScreen::class);

            return $instance;
        });
        /**
         * Mustache Engine
         */
        $this->app->bind(Mustache_Engine::class, function () {
            $op = new MustacheOptions;

            return new Mustache_Engine([
                'helpers' => $op->helpers,
                'pragmas' => [Mustache_Engine::PRAGMA_FILTERS],
            ]);
        });

        $this->app->bind('workflow.FormalExpression', function ($app) {
            return new FormalExpression();
        });

        $this->app->bind(SoapClientInterface::class, function ($app, $request_config) {
            return new NativeSoapClient($request_config['wsdl'], $request_config['options']);
        });

        $this->app->bind('WebServiceRequest', function ($app, $params) {
            $dataSource = $params['dataSource'];

            return new WebServiceRequest(
                new SoapConfigBuilder(),
                new SoapRequestBuilder(),
                new SoapResponseMapper(),
                new SoapServiceCaller(),
                $dataSource
            );
        });

        // Broker Message Service
        $this->app->bind(ServiceInterface::class, function ($app) {
            return ServiceFactory::create();
        });

        parent::register();
    }
}
