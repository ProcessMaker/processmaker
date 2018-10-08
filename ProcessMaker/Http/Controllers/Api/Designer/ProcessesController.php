<?php

namespace ProcessMaker\Http\Controllers\Api\Designer;

use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use ProcessMaker\Facades\ProcessManager;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Model\Process;
use ProcessMaker\Model\ProcessCategory;
use ProcessMaker\Model\User;
use ProcessMaker\Transformers\ProcessTransformer;
use Ramsey\Uuid\Uuid;

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
            'current_page' => $request->input('page', 1),
            'per_page' => $request->input('per_page', 10),
            'sort_by' => $request->input('order_by', 'name'),
            'sort_order' => $request->input('order_direction', 'ASC'),
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
        $data = $this->verifyCategory($request->json()->all());
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
        $data = $this->verifyCategory($request->json()->all());

        ProcessManager::update($process, $data);
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

    /**
     * Load information category if exists field category_uid
     *
     * @param $data
     * @return array
     */
    private function verifyCategory($data)
    {
        if (array_key_exists('category_uid', $data)) {
            $data['process_category_id'] = null;
            $category = ProcessCategory::where('uid', $data['category_uid'])->first();
            if (!empty($category)) {
                $data['process_category_id'] = $category->id;
            }
        }
        return $data;
    }

    /**
     * Create Process with diagram bpmn by default
     *
     * @param Request $request
     *
     * @return ResponseFactory|Response
     * @throws \Exception}
     */
    public function createProcessTemplate(Request $request)
    {
        $data = $this->verifyCategory($request->json()->all());

        $data['user_id'] = Auth::id();
        //Load process by default with template bpmn only start element
        $template = file_get_contents(database_path('processes') . '/templates/OnlyStartElement.bpmn');
        $templateIds = ['DefinitionsId', 'ProcessId', 'ProcessName', 'BPMNShapeStartEventId', 'StartEventId', 'BPMNDiagramId', 'BPMNPlaneId'];
        $values = [Uuid::uuid4(), Uuid::uuid4(), $data['name'], Uuid::uuid4(), Uuid::uuid4(), Uuid::uuid4(), Uuid::uuid4()];

        $data['bpmn'] = str_replace($templateIds, $values, $template);
        $response = ProcessManager::store($data);
        return fractal($response, new ProcessTransformer())->respond(201);
    }
}
