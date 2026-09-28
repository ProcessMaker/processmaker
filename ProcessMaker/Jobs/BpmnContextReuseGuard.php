<?php

namespace ProcessMaker\Jobs;

use ProcessMaker\BpmnEngine;
use ProcessMaker\Models\ProcessRequest;

class BpmnContextReuseGuard
{
    /**
     * Return null when reuse is safe, otherwise return the fallback reason.
     */
    public function fallbackReason(
        ?ProcessRequest $loadedInstance,
        ProcessRequest $persistedInstance,
        ?int $loadedRevision,
        array $persistedActiveTokenIds
    ): ?string {
        if (!$loadedInstance) {
            return 'context_not_loaded';
        }

        if (!$this->isLinearExecution($loadedInstance)) {
            return 'execution_not_linear';
        }

        if ((int) $persistedInstance->execution_revision !== (int) $loadedRevision) {
            return 'execution_revision_changed';
        }

        $loadedTokenIds = collect($loadedInstance->getTokens())
            ->filter(fn ($token) => !in_array($token->getStatus(), BpmnEngine::INACTIVE_TOKEN_STATUSES, true))
            ->map(fn ($token) => (string) $token->getId())
            ->values()
            ->all();

        if (count($persistedActiveTokenIds) !== 1) {
            return 'persisted_execution_not_linear';
        }

        if ((string) $persistedActiveTokenIds[0] !== $loadedTokenIds[0]) {
            return 'active_token_changed';
        }

        return null;
    }

    /**
     * A collaboration, multi-instance token, or multiple active tokens must
     * use a freshly hydrated engine.
     */
    private function isLinearExecution(ProcessRequest $instance): bool
    {
        if ($instance->getRawOriginal('process_collaboration_id')) {
            return false;
        }

        $tokens = collect($instance->getTokens())
            ->filter(fn ($token) => !in_array($token->getStatus(), BpmnEngine::INACTIVE_TOKEN_STATUSES, true))
            ->values();

        return $tokens->count() === 1 && !$tokens->first()->isMultiInstance();
    }
}
