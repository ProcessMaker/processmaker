<?php

namespace ProcessMaker\Managers;

use Illuminate\Pagination\LengthAwarePaginator;
use ProcessMaker\Models\Script;
use Illuminate\Support\Facades\Request;

class ScriptManager
{
    /**
     * Get a list of All scripts in a process.
     *
     * @param array $options
     *
     * @return LengthAwarePaginator
     */
    public function index(array $options): LengthAwarePaginator
    {
        $start = $options['current_page'];
        $query = Script::query();
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
     * @param array $data
     *
     * @return Script
     * @throws \Throwable
     */
    public function save($data): Script
    {
        $script = new Script();
        $script->fill($data);
        $script->saveOrFail();

        return $script;
    }

    /**
     * Update script in a process.
     *
     * @param Script $script
     * @param array $data
     *
     * @return Script
     * @throws \Throwable
     */
    public function update(Script $script, $data): Script
    {
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
