<?php

namespace ProcessMaker\Managers;

use Illuminate\Support\Facades\Validator;
use ProcessMaker\Exception\ValidationException;
use ProcessMaker\Model\Process;

/**
 * This class provides methods to manage de processes.
 *
 * @package ProcessMaker\Managers
 */
class ProcessManager
{

    /**
     * Stores a new process.
     *
     * @param $data
     *
     * @return Process
     * @throws \Throwable
     */
    public function store($data): Process
    {
        $process = new Process();
        $process->fill($data);
        $process->saveOrFail();

        return $process->refresh();
    }

    /**
     * Update a process.
     *
     * @param Process $process
     * @param array $data
     *
     * @return Process
     * @throws \Throwable
     */
    public function update(Process $process, $data): Process
    {
        $this->validate(
            [
                'process' => $process,
            ],
            [
                'process' => 'process_manager.process_does_not_have_cases'
            ]
        );
        $process->fill($data);
        $process->saveOrFail();
        return $process->refresh();
    }

    /**
     * Remove a process.
     *
     * @param Process $process
     *
     * @return bool|null
     * @throws ValidationException
     */
    public function remove(Process $process): ?bool
    {
        $this->validate(
            [
                'process' => $process,
            ],
            [
                'process' => 'process_manager.process_does_not_have_cases'
            ]
        );
        return $process->delete();
    }

    /**
     * Validate the given data with the given rules.
     *
     * @param array $data
     * @param array $rules
     * @param array $messages
     * @param array $customAttributes
     *
     * @throws ValidationException
     */
    private function validate(
        array $data,
        array $rules,
        array $messages = [],
        array $customAttributes = []
    )
    {
        $validator = Validator::make($data, $rules, $messages, $customAttributes);

        /**
         * Validate that the process does not have cases.
         */
        $validator->addExtension(
            'process_manager.process_does_not_have_cases',
            function ($attribute, Process $process) {
                return $process->cases()->count() === 0;
            }
        );

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }
}
