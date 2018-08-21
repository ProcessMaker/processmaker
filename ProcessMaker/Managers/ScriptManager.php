<?php

namespace ProcessMaker\Managers;

use Illuminate\Pagination\LengthAwarePaginator;
use ProcessMaker\Model\Process;
use ProcessMaker\Model\Script;

class ScriptManager
{
    /**
     * Get a list of All scripts in a process.
     *
     * @param Process $process
     * @param array $options
     *
     * @return LengthAwarePaginator
     */
    public function index(Process $process, array $options): LengthAwarePaginator
    {
        $start = $options['current_page'];
        $query = Script::where('process_id', $process->id);
        $filter = $options['filter'];
        if (!empty($filter)) {
            $filter = '%' . $filter . '%';
            $query->where(function ($query) use ($filter) {
                $query->Where('title', 'like', $filter)
                    ->orWhere('description', 'like', $filter)
                    ->orWhere('language', 'like', $filter);
            });
        }
        return $query->orderBy($options['sort_by'], $options['sort_order'])
            ->paginate($options['per_page'])
            ->appends($options);
    }

    /**
     * Create a new script in a process.
     *
     * @param Process $process
     * @param array $data
     *
     * @return Script
     * @throws \Throwable
     */
    public function save(Process $process, $data): Script
    {
        $data['process_id'] = $process->id;

        $script = new Script();
        $script->fill($data);
        $script->saveOrFail();

        return $script;
    }

    /**
     * Update script in a process.
     *
     * @param Process $process
     * @param Script $script
     * @param array $data
     *
     * @return Script
     * @throws \Throwable
     */
    public function update(Process $process, Script $script, $data): Script
    {
        $data['process_id'] = $process->id;
        $script->fill($data);
        $script->saveOrFail();
        return $script;
    }

    /**
     * Remove script in a process.
     *
     * @param Script $script
     *
     * @return bool|null
     * @throws \Exception
     */
    public function remove(Script $script): ?bool
    {
        return $script->delete();
    }

}
