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
     * Provides a list of processes.
     *
     * @param string $filter
     * @param int $start
     * @param int $limit
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function index($filter, $start, $limit)
    {
        $this->validate(
            [
                'start' => $start,
                'limit' => $limit,
            ],
            [
                'start' => 'nullable|numeric|min:0',
                'limit' => 'nullable|numeric|min:0',
            ]
        );
        $query = Process::select();
        $filter === null ?: $query->where('name', 'like', "%$filter%");
        $start === null ? : $query->offset($start);
        $limit === null ? : $query->limit($limit);
        return $query->get();
    }

    /**
     * Get the process definition.
     *
     * @param \ProcessMaker\Model\Process $process
     */
    public function getDefinition(Process $process)
    {
        $fractal = new Manager();
        $serializer = new ProcessMakerSerializer();
        $fractal->setSerializer($serializer);
        return $fractal->createData(new Item($process, new ProcessTransformer));
    }

    /**
     * Stores a new process.
     *
     * @param array $data
     *
     * @return \ProcessMaker\Model\Process
     */
    public function store($data)
    {
        //@todo Implement the management (create) for the rest of models.
        return Process::create([
            'name' => $data['prj_name'],
            'description' => $data['prj_description'],
            'category' => $data['prj_category'],
            'type' => $data['prj_type'],
            'created_at' => $data['prj_create_date'],
            'updated_at' => $data['prj_update_date'],
        ]);
    }

    /**
     * Update a process.
     *
     * @param \ProcessMaker\Model\Process $process
     * @param array $data
     *
     * @return \ProcessMaker\Model\Process
     */
    public function update(Process $process, $data)
    {
        //@todo Implement the management (update) for the rest of models.
        $process->update([
            'PRO_NAME' => $data['prj_name'],
            'PRO_DESCRIPTION' => $data['prj_description'],
            'PRO_CATEGORY' => $data['prj_category'],
            'PRO_TYPE' => $data['prj_type'],
            'PRO_CREATE_DATE' => $data['prj_create_date'],
            'PRO_UPDATE_DATE' => $data['prj_update_date'],

        ]);
        $processCategory->save();
        return $process;
    }

    /**
     * Remove a process.
     *
     * @param \ProcessMaker\Model\Process $process
     *
     * @return bool
     */
    public function remove(Process $process)
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
    ) {
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
