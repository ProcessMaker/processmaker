<?php

namespace ProcessMaker\Http\Controllers\Api\Designer;

use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use ProcessMaker\Facades\ProcessManager;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Model\Process;
use ProcessMaker\Model\ProcessCategory;
use ProcessMaker\Model\User;
use ProcessMaker\Transformers\ProcessTransformer;

/**
 * Implements endpoints to manage the processes.
 *
 */
class ProcessesController extends Controller
{

    /**
     * List of processes. What is returned is ordered by category and id (uncategorized shows last)
     *
     * @param Request $request
     *
     * @return ResponseFactory|Response
     */
    public function index(Request $request)
    {
        $options = [
            'filter' => $request->input('filter', ''),
            'current_page' => $request->input('current_page', 1),
            'per_page' => $request->input('per_page', 10),
            'sort_by' => $request->input('sort_by', 'name'),
            'sort_order' => $request->input('sort_order', 'ASC'),
        ];

        $sortingField = [
            'status' => 'status',
            'user' => 'user_id',
            'category' => 'process_category_id',
            'due_date' => 'updated_at',
            'name' => 'name',
            'description' => 'description'
        ];

        $query = Process::with(['category', 'user']);


        if (!empty($options['filter'])) {
            // We want to search off of name and description and category name
            // Cannot join on table because of Eloquent's lack of specific table column names in generated SQL
            // See: https://github.com/laravel/ideas/issues/347
            $filter = '%' . $options['filter'] . '%';
            $category = new ProcessCategory();
            $user = new User();
            $query->where(function ($query) use ($filter, $category, $user) {
                $query->Where('name', 'like', $filter)
                    ->orWhere('description', 'like', $filter)
                    ->orWhere('status', 'like', $filter)
                    ->orWhere(function ($q) use ($filter, $category) {
                        $q->whereHas('category', function ($query) use ($filter, $category) {
                            $query->where($category->getTable() . '.name', 'like', $filter);
                        });
                    })
                    ->orWhere(function ($q) use ($filter, $user) {
                        $q->whereHas('user', function ($query) use ($filter, $user) {
                            $query->where($user->getTable() . '.firstname', 'like', $filter)
                                ->where($user->getTable() . '.lastname', 'like', $filter);
                        });
                    });
            });
        }

        $sort = 'name';
        if (isset($sortingField[$options['sort_by']])) {
            $sort = $sortingField[$options['sort_by']];
        }

        $query->orderBy($sort, $options['sort_order']);

        $processes = $query->paginate($options['per_page'])
            ->appends($options);

        return fractal($processes, new ProcessTransformer())->respond();
    }

    /**
     * Stores a new process.
     *
     * @param Request $request
     *
     * @return array
     */
    public function store(Request $request)
    {
        $data = $request->json()->all();
        $response = ProcessManager::store($data);
        return response($this->format($response), 201);
    }

    /**
     * Update a process.
     *
     * @param Request $request
     * @param \ProcessMaker\Model\Process $process
     *
     * @return array
     */
    public function update(Request $request, Process $process)
    {
        $data = $request->json()->all();
        $response = ProcessManager::update($process, $data);
        return response('', 204);
    }

    /**
     * Remove a process.
     *
     * @param \ProcessMaker\Model\Process $process
     *
     * @return array
     */
    public function remove(Process $process)
    {
        ProcessManager::remove($process);
        return response('', 204);
    }

    /**
     * Retrieve and show a single process
     *
     * @param \ProcessMaker\Model\Process $process
     *
     * @return ResponseFactory|Response
     */
    public function show(Process $process)
    {
        $process->category = $process->category()->first();
        $process->user = $process->user()->first();
        return fractal($process, new ProcessTransformer())->respond(200);
    }
}
