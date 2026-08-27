<?php

namespace ProcessMaker\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use ProcessMaker\Facades\Metrics;
use ProcessMaker\Managers\DataManager;
use ProcessMaker\Models\Process as Definitions;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Nayra\Contracts\Bpmn\ActivityInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\CallActivityInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\TokenInterface;
use ProcessMaker\Nayra\Contracts\Engine\ExecutionInstanceInterface;

class CompleteActivity extends BpmnAction implements ShouldQueue
{
    public $definitionsId;

    public $instanceId;

    public $tokenId;

    public $data;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Definitions $definitions, ExecutionInstanceInterface $instance, TokenInterface $token, array $data)
    {
        $this->definitionsId = $definitions->getKey();
        $this->instanceId = $instance->getKey();
        $this->tokenId = $token->getKey();
        $this->data = $data;
    }

    /**
     * Execute the job with performance optimizations.
     *
     * @return void
     */
    public function action(ProcessRequestToken $token, ActivityInterface $element, array $data)
    {
        try {
            //@todo requires a class to manage the data access and control the updates
            if (!($element instanceof CallActivityInterface)) {
                // Use optimized data update job for better performance
                OptimizedDataUpdate::dispatch($token->getKey(), $data)
                    ->onQueue('bpmn-data');
            }
            
            $this->engine->runToNextState();
            $element->complete($token);

            // Queue metrics collection to avoid blocking the main process
            $this->queueMetricsCollection($element);
            
        } catch (\Exception $e) {
            \Log::error('CompleteActivity failed', [
                'token_id' => $token->getKey(),
                'element_id' => $element->getId(),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Queue metrics collection for non-blocking performance
     */
    private function queueMetricsCollection(ActivityInterface $element): void
    {
        dispatch(function () use ($element) {
            try {
                Metrics::counterInc(
                    'activity_completed_total',
                    'Total number of activities completed',
                    [
                        'activity_id' => $element->getId(),
                        'activity_name' => $element->getName(),
                        'process_id' => $this->definitionsId,
                        'request_id' => $this->instanceId,
                    ]
                );
            } catch (\Exception $e) {
                \Log::warning('Metrics collection failed', [
                    'element_id' => $element->getId(),
                    'error' => $e->getMessage()
                ]);
            }
        })->onQueue('metrics');
    }
}
