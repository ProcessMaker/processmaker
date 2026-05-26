<?php

namespace ProcessMaker\Support;

use Illuminate\Support\Facades\Log;

/**
 * Debug-only step timing helper (active when APP_DEBUG=true).
 *
 * Use it temporarily to profile hot paths; remove calls once bottlenecks are identified.
 *
 * Example 1 — sequential steps in a controller or job:
 *
 *     $timer = ExecutionTimer::start('TaskController::update', ['task_id' => $task->id]);
 *
 *     $this->authorize('update', $task);
 *     $timer->step('authorize');
 *
 *     WorkflowManager::completeTask($process, $instance, $task, $data);
 *     $timer->step('completeTask');
 *
 *     return new Resource($task->refresh());
 *
 * Example 2 — loop iterations with explicit phase start (mark + stepPhase):
 *
 *     $timer = ExecutionTimer::start('BpmnEngine::runToNextState', ['engine_uid' => $this->uid]);
 *
 *     while ($step) {
 *         $iterStart = $timer->mark();
 *         $step = $this->step();
 *         $timer->stepPhase('step', $iterStart, ['iteration' => $iteration, 'step_result' => $step]);
 *         $iteration++;
 *     }
 *
 * Logs are written to the default log channel as `[channel] timing` with step_ms / phase_ms and total_ms.
 * When APP_DEBUG is false, all methods are no-ops (zero overhead).
 */
class ExecutionTimer
{
    private float $start;

    private float $lastStep;

    private function __construct(
        private readonly string $channel,
        private readonly array $context,
        private readonly bool $enabled
    ) {
        $this->start = microtime(true);
        $this->lastStep = $this->start;
    }

    public static function start(string $channel, array $context = []): self
    {
        return new self($channel, $context, (bool) config('app.debug'));
    }

    public static function disabled(string $channel = '', array $context = []): self
    {
        return new self($channel, $context, false);
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Mark the start of a sub-phase (returns microtime for stepPhase).
     */
    public function mark(): float
    {
        return microtime(true);
    }

    /**
     * Log elapsed time since the previous step.
     */
    public function step(string $step, array $extra = []): self
    {
        if (!$this->enabled) {
            return $this;
        }

        $now = microtime(true);
        Log::debug("[{$this->channel}] timing", array_merge($this->context, $extra, [
            'step' => $step,
            'step_ms' => round(($now - $this->lastStep) * 1000, 2),
            'total_ms' => round(($now - $this->start) * 1000, 2),
        ]));
        $this->lastStep = $now;

        return $this;
    }

    /**
     * Log elapsed time since an explicit phase start (useful for loop iterations).
     */
    public function stepPhase(string $step, float $phaseStart, array $extra = []): self
    {
        if (!$this->enabled) {
            return $this;
        }

        $now = microtime(true);
        Log::debug("[{$this->channel}] timing", array_merge($this->context, $extra, [
            'step' => $step,
            'phase_ms' => round(($now - $phaseStart) * 1000, 2),
            'total_ms' => round(($now - $this->start) * 1000, 2),
        ]));
        $this->lastStep = $now;

        return $this;
    }
}
