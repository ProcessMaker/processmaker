<?php

namespace ProcessMaker\Contracts;

use ProcessMaker\Nayra\Contracts\Bpmn\TokenInterface;
use ProcessMaker\Nayra\Contracts\Engine\ExecutionInstanceInterface;

interface CaseRepositoryInterface
{
    /**
     * Store a new case started.
     *
     * @param ExecutionInstanceInterface $instance
     * @return void
     */
    public function create(ExecutionInstanceInterface $instance): void;
    /**
     * Update the case started.
     *
     * @param ExecutionInstanceInterface $instance
     * @param TokenInterface $token
     * @return void
     */
    public function update(ExecutionInstanceInterface $instance, TokenInterface $token): void;
    /**
     * Update the status of a case started.
     *
     * @param ExecutionInstanceInterface $instance
     * @return void
     */
    public function updateStatus(ExecutionInstanceInterface $instance): void;
}
