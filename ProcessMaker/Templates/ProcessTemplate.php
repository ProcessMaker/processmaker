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

    /**
     * Summary of save
     * @param mixed $request
     * @return JsonResponse
     */
    public function save($request) : JsonResponse
    {
        $processId = $request->id;
        $name = $request->name;
        $description = $request->description;
        $userId = $request->user_id;
        $category = $request->process_category_id;
        $mode = $request->mode;

        $model = (new ExportController)->getModel('process')->findOrFail($processId);
        $result = (object) $this->getManifest('process', $processId);
        //dd('RESULT', $result->export);
        $postOptions = [];
        foreach ($result->export as $key => $asset) {
            $postOptions[$key] = [
                'mode' => $mode,
            ];
        }

        $options = new Options($postOptions);

        $exporter = new Exporter();
        $exporter->export($model, ProcessExporter::class, $options);
        $payload = $exporter->payload();

        $svg = Arr::get($payload, 'export.' . $payload['root'] . '.attributes.svg');
        $template = ProcessTemplates::firstOrCreate([
            'name' => $name,
            'description' => $description,
            'user_id' => $userId,
            'manifest' => json_encode($payload),
            'svg' => $svg,
            'process_id' => $processId,
            'process_category_id' => $category,
        ]);

        return response()->json(['model' => $template]);
    }

    public function create($request) : JsonResponse
    {
        $templateId = $request->id;
        $template = ProcessTemplates::where('id', $templateId)->firstOrFail();

        $payload = json_decode($template->manifest, true);

        $postOptions = [];
        foreach ($payload['export']as $key => $asset) {
            $postOptions[$key] = [
                'mode' => $asset['mode'],
            ];
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

    public function update($request) : JsonResponse
    {
        $id = (int) $request->id;
        $template = ProcessTemplates::where('id', $id)->firstOrFail();
        if (!isset($request->process_id)) {
            // This is an update from the template configs page
            $template->fill($request->except('id'));
        } else {
            // This is an update from a the process designer
            // Get process manifest
            $processId = $request->process_id;
            $mode = $request->mode;

            $manifest = $this->getManifest('process', $processId);
            $rootUuid = $manifest->getData()->root;
            $export = $manifest->getData()->export;
            $svg = $export->$rootUuid->attributes->svg;

            // Discard ALL assets/dependents
            if ($mode === 'discard') {
                $manifest = json_decode(json_encode($manifest), true);
                $rootExport = Arr::first($manifest['original']['export'], function ($value, $key) use ($rootUuid) {
                    return $key === $rootUuid;
                });
                data_set($rootExport, 'dependents.*.discard', true);
                data_set($manifest, 'original.export', $rootExport);
            }

            $template->fill($request->except('id'));
            $template->svg = $svg;
            $template->manifest = json_encode($manifest);
        }

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

    public function configure(int $id) : array
    {
        $template = (object) [];

        $template = ProcessTemplates::where('id', $id)->firstOrFail();
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
     * Get the where array to filter the resources.
     *
     * @param Request $request
     * @param array $searchableColumns
     *
     * @return array
     */
    protected function getRequestFilterBy(Request $request, array $searchableColumns)
    {
        $where = [];
        $filter = $request->input('filter');
        if ($filter) {
            foreach ($searchableColumns as $column) {
                // for other columns, it can match a substring
                $sub_search = '%';
                if (array_search('status', explode('.', $column), true) !== false) {
                    // filtering by status must match the entire string
                    $sub_search = '';
                }
                $where[] = [$column, 'like', $sub_search . $filter . $sub_search, 'or'];
            }
        }

        return $where;
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
            return [$template->id, $name];
        }
    }
}
