<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Http\Resources\ApiCollection;
use ProcessMaker\Http\Resources\Script as ScriptResource;
use ProcessMaker\Models\Script;

class ScriptController extends Controller
{
    /**
     * Get a list of scripts in a process.
     *
     * @param Process $process
     *
     * @return ResponseFactory|Response
     */
    public function index(Request $request)
    {
        $query = Script::query();

        $filter = $request->input('filter', '');
        if (!empty($filter)) {
            $filter = '%' . $filter . '%';
            $query->where(function ($query) use ($filter) {
                $query->Where('title', 'like', $filter)
                    ->orWhere('description', 'like', $filter)
                    ->orWhere('language', 'like', $filter);
            });
        }

        $response =
            $query->orderBy(
                $request->input('order_by', 'title'),
                $request->input('order_direction', 'ASC')
            )
            ->paginate($request->input('per_page', 10));

        return new ApiCollection($response);
    }

    /**
     * Previews executing a script, with sample data/config data
     */
    public function preview(Request $request)
    {
        $data = json_decode($request->get('data'), true) ?: [];
        $config = json_decode($request->get('config'), true) ?: [];
        $code = $request->get('code');
        $language = $request->get('language');
        $script = new Script([
            'code' => $code,
            'language' => $language,
        ]);
        return $script->runScript($data, $config);
    }

    /**
     * Get a single script in a process.
     *
     * @param Script $script
     *
     * @return ResponseFactory|Response
     */
    public function show(Script $script)
    {
        return new ScriptResource($script);
    }

    /**
     * Create a new script in a process.
     *
     * @param Request $request
     *
     * @return ResponseFactory|Response
     */
    public function store(Request $request)
    {
        $request->validate(Script::rules());
        $script = new Script();
        $script->fill($request->input());
        $script->saveOrFail();
        return new ScriptResource($script);
    }

    /**
     * Update a script in a process.
     *
     * @param Process $process
     * @param Script $script
     * @param Request $request
     *
     * @return ResponseFactory|Response
     */
    public function update(Script $script, Request $request)
    {
        $request->validate(Script::rules($script));

        $script->fill($request->input());
        $script->saveOrFail();

        return response([], 204);
    }

    /**
     * Delete a script in a process.
     *
     * @param Script $script
     *
     * @return ResponseFactory|Response
     */
    public function destroy(Script $script)
    {
        $script->delete();
        return response([], 204);
    }
}
