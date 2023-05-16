<?php

namespace ProcessMaker\Events;

use ProcessMaker\Contracts\SecurityLogEventInterface;
use ProcessMaker\Models\Process;

class ProcessCreated implements SecurityLogEventInterface
{
    private Process $process;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Process $process)
    {
        $this->process = $process;
    }

    public function getData(): array
    {
        return [
            'Name'              => $this->process->getAttribute('name'),
            'Description'       => $this->process->getAttribute('description'),
            'Category'          => $this->process->category ? $this->process->category->name : null,
            'Process Manager'   => $this->process->user ? [
                'label' => $this->process->user->getAttribute('fullname'),
                'link' => route('users.edit', $this->process->user),
            ] : null,
        ];
    }

    public function getEventName(): string
    {
        return 'Process Created';
    }
}
