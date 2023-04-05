<?php

namespace ProcessMaker\Templates;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\View\View;
use ProcessMaker\Http\Controllers\Api\ExportController;
use ProcessMaker\ImportExport\Exporter;
use ProcessMaker\ImportExport\Exporters\ProcessExporter;
use ProcessMaker\ImportExport\Importer;
use ProcessMaker\ImportExport\Options;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessCategory;
use ProcessMaker\Models\ProcessTemplates;
use ProcessMaker\Models\Template;
use ProcessMaker\Traits\HasControllerAddons;
use SebastianBergmann\CodeUnit\Exception;

/**
 * Summary of ProcessTemplate
 */
class ProcessTemplate implements TemplateInterface
{
    use HasControllerAddons;

    public function index(Request $request)
    {
        $orderBy = $this->getRequestSortBy($request, 'name');
        $include = $this->getRequestInclude($request);
        $templates = ProcessTemplates::nonSystem()->with($include);

        $filter = $request->input('filter');

        $templates = $templates->select('process_templates.*')
            ->leftJoin('process_categories as category', 'process_templates.process_category_id', '=', 'category.id')
            ->leftJoin('users as user', 'process_templates.user_id', '=', 'user.id')
            ->orderBy(...$orderBy)
            ->where(function ($query) use ($filter) {
                $query->where('process_templates.name', 'like', '%' . $filter . '%')
                    ->orWhere('process_templates.description', 'like', '%' . $filter . '%')
                    ->orWhere('user.firstname', 'like', '%' . $filter . '%')
                    ->orWhere('user.lastname', 'like', '%' . $filter . '%')
                    ->orWhereIn('process_templates.id', function ($qry) use ($filter) {
                        $qry->select('assignable_id')
                            ->from('category_assignments')
                            ->leftJoin('process_categories', function ($join) {
                                $join->on('process_categories.id', '=', 'category_assignments.category_id');
                                $join->where('category_assignments.category_type', '=', ProcessCategory::class);
                                $join->where('category_assignments.assignable_type', '=', ProcessTemplates::class);
                            })
                            ->where('process_categories.name', 'like', '%' . $filter . '%');
                    });
            })->get();

        return $templates;
    }

    public function show($request)
    {
        $templateId = (int) $request->id;
        $template = ProcessTemplates::where('id', $templateId)->firstOrFail();
        $payload = json_decode($template->manifest, true);
        foreach ($payload['export'] as $key => $asset) {
            if ($payload['root'] === $key) {
                // Set name and description for the new process
                $payload['export'][$key]['attributes']['name'] = $template->name;
                $payload['export'][$key]['attributes']['description'] = $template->description;
                $payload['export'][$key]['attributes']['process_category_id'] = 1;

                $payload['export'][$key]['name'] = $template->name;
                $payload['export'][$key]['description'] = $template->description;
                $payload['export'][$key]['process_category_id'] = 1;
                $payload['export'][$key]['process_manager_id'] = $template->manager_id;
            }
        }
        $options = new Options([]);
        $importer = new Importer($payload, $options);
        $manifest = $importer->doImport();
        $rootLog = $manifest[$payload['root']]->log;
        $processId = $rootLog['newId'];

        return ['id' => $processId, 'mode' => 'review'];
    }

    /**
     * Summary of save
     * @param mixed $request
     * @return JsonResponse
     */
    public function save($request) : JsonResponse
    {
        $data = $request->all();

        // Find the required model
        $model = (new ExportController)->getModel('process')->findOrFail($data['asset_id']);

        // Get the process manifest
        $manifest = $this->getManifest('process', $data['asset_id']);

        // Array of post options
        $postOptions = array_map(fn ($value) => ['mode' => $data['mode']], $manifest['export']);
        $options = new Options($postOptions);

        // Create an exporter instance
        $exporter = new Exporter();
        $exporter->export($model, ProcessExporter::class, $options);
        $payload = $exporter->payload();

        // Extract svg from payload
        $svg = Arr::get($payload, 'export.' . $payload['root'] . '.attributes.svg');

        // Create a new process template
        $processTemplate = ProcessTemplates::make($data);

        // Fill the manifest and svg attributes
        $processTemplate->fill($data);
        $processTemplate->manifest = json_encode($payload);
        $processTemplate->svg = $svg;
        $processTemplate->process_id = $data['asset_id'];
        $processTemplate->user_id = \Auth::user()->id;

        $processTemplate->saveOrFail();

        // Return response
        return response()->json(['model' => $processTemplate]);
    }

    public function create($request) : JsonResponse
    {
        $templateId = $request->id;
        $template = ProcessTemplates::where('id', $templateId)->firstOrFail();
        $template->fill($request->except('id'));

        $payload = json_decode($template->manifest, true);
        $payload['name'] = $request['name'];
        $payload['description'] = $request['description'];

        $postOptions = [];
        foreach ($payload['export'] as $key => $asset) {
            $postOptions[$key] = [
                'mode' => $asset['mode'],
            ];
            if ($payload['root'] === $key) {
                // Set name and description for the new process
                $payload['export'][$key]['attributes']['name'] = $request['name'];
                $payload['export'][$key]['attributes']['description'] = $request['description'];
                $payload['export'][$key]['attributes']['process_category_id'] = $request['process_category_id'];

                $payload['export'][$key]['name'] = $request['name'];
                $payload['export'][$key]['description'] = $request['description'];
                $payload['export'][$key]['process_category_id'] = $request['process_category_id'];
                $payload['export'][$key]['process_manager_id'] = $request['manager_id'];
            }
        }

        $options = new Options($postOptions);
        $importer = new Importer($payload, $options);
        $manifest = $importer->doImport();
        $rootLog = $manifest[$payload['root']]->log;
        $processId = $rootLog['newId'];

        return response()->json(['processId' => $processId]);
    }

    public function view() : bool
    {
        dd('PROCESS TEMPLATE VIEW');
    }

    public function edit($template) : JsonResponse
    {
        dd('PROCESS TEMPLATE EDIT');
    }

    public function updateTemplate($request) : JsonResponse
    {
        $id = (int) $request->id;
        $template = ProcessTemplates::where('id', $id)->firstOrFail();
        // Get process manifest
        $processId = $request->process_id;
        $mode = $request->mode;

        $rootUuid = null;
        $export = null;
        $manifest = $this->getManifest('process', $processId);
        if (is_array($manifest)) {
            $rootUuid = Arr::get($manifest, 'root');
            $export = Arr::get($manifest, 'export');
        } else {
            $rootUuid = $manifest->getData()->root;
            $export = $manifest->getData()->export;
        }

        $svg = Arr::get($export, $rootUuid . '.attributes.svg');

        $template->fill($request->except('id'));
        $template->svg = $svg;
        $template->manifest = json_encode($manifest);

        // Catch errors to send more specific status
        try {
            $template->saveOrFail();
        } catch (Exception $e) {
            return response(
                ['message' => $e->getMessage(),
                    'errors' => ['bpmn' => $e->getMessage()], ],
                422
            );
        }

        return response()->json();
    }

    public function updateTemplateConfigs($request) : JsonResponse
    {
        $id = (int) $request->id;
        $template = ProcessTemplates::where('id', $id)->firstOrFail();
        $template->fill($request->except('id'));

        // Catch errors to send more specific status
        try {
            $template->saveOrFail();
        } catch (\Exception $e) {
            return response([
                'message' => $e->getMessage(),
                'errors' => [
                    'bpmn' => $e->getMessage(),
                ],
            ], 422);
        }

        return response()->json();
    }

    public function configure(int $id) : array
    {
        $template = (object) [];

        $query = ProcessTemplates::select(['name', 'description', 'process_category_id'])->where('id', $id)->firstOrFail();

        $template->id = $id;
        $template->name = $query->name;
        $template->description = $query->description;
        $template->process_category_id = $query['process_category_id'];
        $categories = ProcessCategory::orderBy('name')
            ->where('status', 'ACTIVE')
            ->get()
            ->pluck('name', 'id')
            ->toArray();
        $addons = $this->getPluginAddons('edit', compact(['template']));

        return [$template, $addons, $categories];
    }

    public function destroy(int $id) : bool
    {
        $response = ProcessTemplates::where('id', $id)->delete();

        return $response;
    }

    /**
     * Get process manifest.
     *
     * @param string $type
     *
     * @param Request $request
     *
     * @return JSON
     */
    public function getManifest(string $type, int $id) : array
    {
        $response = (new ExportController)->manifest($type, $id);
        $content = json_decode($response->getContent(), true);

        return $content;
    }

    /**
     * Get included relationships.
     *
     * @param Request $request
     *
     * @return array
     */
    protected function getRequestSortBy(Request $request, $default)
    {
        $column = $request->input('order_by', $default);
        $direction = $request->input('order_direction', 'asc');

        return [$column, $direction];
    }

    /**
     * Get included relationships.
     *
     * @param Request $request
     *
     * @return array
     */
    protected function getRequestInclude(Request $request)
    {
        $include = $request->input('include');

        return $include ? explode(',', $include) : [];
    }

    public function existingTemplate($request)
    {
        $templateId = $request->id;
        $name = $request->name;

        $template = ProcessTemplates::where(['name' => $name])->where('id', '!=', $templateId)->first();
        if ($template !== null) {
            // If same asset has been Saved as Template previously, offer to choose between “Update Template” and “Save as New Template”
            return ['id' => $template->id, 'name' => $name];
        }

        return null;
    }
}
