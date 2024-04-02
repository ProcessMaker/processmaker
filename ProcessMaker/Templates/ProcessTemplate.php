<?php

namespace ProcessMaker\Templates;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
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
use ProcessMaker\Models\WizardTemplate;
use ProcessMaker\Traits\HasControllerAddons;
use SebastianBergmann\CodeUnit\Exception;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Summary of ProcessTemplate
 */
class ProcessTemplate implements TemplateInterface
{
    use HasControllerAddons;

    const PROJECT_ASSET_MODEL_CLASS = 'ProcessMaker\Package\Projects\Models\ProjectAsset';

    /**
     * List process templates
     *
     * @param Request $request The request object.
     *
     * @return array An array containing the list of process templates.
     */
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
     * Show process template in modeler
     *
     * @param mixed $request Request object
     * @return array Returns an array with the process ID
     */
    public function show($request) : array
    {
        $template = ProcessTemplates::find($request->id);
        $process = Process::where('uuid', $template->editing_process_uuid)->where('is_template', 1)->first();

        // If a process exists with the template name return that process
        if ($process) {
            return ['id' => $process->id];
        }
        // Otherwise we need to import the template and create a new process
        $payload = json_decode($template->manifest, true);

        $export = array_filter($payload['export'], function ($asset) {
            return $asset['type'] !== 'CommentConfiguration';
        });

        // Loop through each asset in the "export" array and set postOptions "mode" accordingly
        $postOptions = [];
        foreach ($export as $key => $asset) {
            $mode = 'copy';
            $saveMode = 'saveAllAssets';
            if (array_key_exists('saveAssetsMode', $asset) && $asset['saveAssetsMode'] === 'saveModelOnly') {
                $saveMode = 'saveModelOnly';
            }
            if ($payload['root'] != $key && $saveMode === 'saveModelOnly' && substr($asset['type'], -8) === 'Category') {
                $mode = 'discard'; // set mode to 'discard' for category assets that are not the root
            }
            $postOptions[$key] = [
                'mode' => $mode,
                'isTemplate' => true,
                'saveAssetsMode' => $saveMode,
            ];

            if ($payload['root'] === $key) {
                // Set name, description, and user_id attributes for root asset
                $payload['export'][$key]['attributes'] = [
                    'name' => $template->name,
                    'description' => $template->description,
                    'is_template' => true,
                    'bpmn' => $asset['attributes']['bpmn'],
                    'user_id' => $template->user_id ?? \Auth::user()->getKey(),
                ];

                // Also set the name, description, and is_template directly on the asset
                $payload['export'][$key]['name'] = $template->name;
                $payload['export'][$key]['description'] = $template->description;
            }

            if (in_array($asset['type'], ['Process', 'Screen', 'Scripts', 'Collections', 'DataConnector'])) {
                $payload['export'][$key]['attributes']['is_template'] = true; // set attributes for all assets
                $payload['export'][$key]['is_template'] = true; // set attributes for all assets
            }
        }

        $options = new Options($postOptions);
        $importer = new Importer($payload, $options);

        $manifest = $importer->doImport();
        $rootLog = $manifest[$payload['root']]->log;
        $processId = $rootLog['newId'];

        $processUuid = Process::select('uuid')->where('id', $processId)->first()->uuid;
        ProcessTemplates::where('id', $template->id)->update(['editing_process_uuid' => $processUuid]);

        // Return an array with the process ID
        return ['id' => $processId];
    }

    /**
     * Save new process template
     * @param mixed $request The HTTP request containing the template data
     * @return JsonResponse The JSON response with the saved template model
     */
    public function save($request) : JsonResponse
    {
        $data = $request->all();

        // Find the required model
        $model = (new ExportController)->getModel('process')->findOrFail($data['asset_id']);

        // Get the process manifest
        $response = $this->getManifest('process', $data['asset_id']);
        if (array_key_exists('error', $response)) {
            return response()->json($response, 400);
        }

        // Array of post options
        $uuid = $model->uuid;

        // Loop through each asset in the "export" array and set postOptions "mode" accordingly
        $postOptions = [];
        foreach ($response['export'] as $key => $asset) {
            $mode = $data['saveAssetsMode'] === 'saveAllAssets' ? 'copy' : 'discard';
            if ($key === $uuid) {
                $mode = 'copy';
            }
            $postOptions[$key] = [
                'mode' => $mode,
                'isTemplate' => true,
                'saveAssetsMode' => $data['saveAssetsMode'],
            ];
        }
        $options = new Options($postOptions);

        // Create an exporter instance
        $exporter = new Exporter();
        $exporter->export($model, ProcessExporter::class, $options);
        $payload = $exporter->payload();

        // Create a new process template
        $processTemplate = ProcessTemplates::make($data)->fill([
            'manifest' => json_encode($payload),
            'svg' => Arr::get($payload, "export.{$payload['root']}.attributes.svg"),
            'process_id' => $data['asset_id'],
            'user_id' => \Auth::user()->id,
        ]);

        $processTemplate->saveOrFail();

        return response()->json(['model' => $processTemplate]);
    }

    /**
     * Create a process from the selected process template
     *
     * @param mixed $request The HTTP request data
     * @return JsonResponse The JSON response containing the new process ID
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException if the process template is not found
     */
    public function create($request) : JsonResponse
    {
        $templateId = (int) $request->id;
        $template = ProcessTemplates::where('id', $templateId)->firstOrFail();
        $template->fill($request->except('id'));

        $payload = json_decode($template->manifest, true);
        // Check for existing assets
        $existingAssets = $request->existingAssets;
        $requestData = $existingAssets ? $request->toArray()['request'] : $request;

        $payload['name'] = $requestData['name'];
        $payload['description'] = $requestData['description'];

        $postOptions = [];
        foreach ($payload['export'] as $key => $asset) {
            // Exclude the import of comment configurations to account for the unavailability
            // of the comment configuration table in the database.
            if ($asset['model'] === 'ProcessMaker\Package\PackageComments\Models\CommentConfiguration') {
                unset($payload['export'][$key]);
                continue;
            }

            // Exclude the import of process categories if the category already exists in the database
            if ($asset['model'] === 'ProcessMaker\Models\ProcessCategory') {
                $processCategory = ProcessCategory::where('uuid', $key)->first();
                if ($processCategory !== null) {
                    unset($payload['export'][$key]);
                    continue;
                }
            }

            $postOptions[$key] = [
                'mode' => 'copy',
                'isTemplate' => false,
                'saveAssetsMode' => 'saveAllAssets',
            ];

            if ($existingAssets) {
                foreach ($existingAssets as $item) {
                    $uuid = $item['uuid'];
                    if (isset($postOptions[$uuid])) {
                        $postOptions[$uuid]['mode'] = $item['mode'];
                    }
                }
            }

            if ($payload['root'] === $key) {
                // Set name and description for the new process
                $payload['export'][$key]['attributes']['name'] = $requestData['name'];
                $payload['export'][$key]['attributes']['description'] = $requestData['description'];
                $payload['export'][$key]['attributes']['process_category_id'] = $requestData['process_category_id'];
                // Store the wizard template uuid on the process to rerun the helper process
                if (isset($requestData['wizardTemplateUuid'])) {
                    $properties = json_decode($payload['export'][$key]['attributes']['properties'], true);
                    $properties['wizardTemplateUuid'] = $requestData['wizardTemplateUuid'];
                    $payload['export'][$key]['attributes']['properties'] = json_encode($properties);
                }
                // Store the helper process request id that initiated the process creation
                if (isset($requestData['helperProcessRequestId'])) {
                    $properties = json_decode($payload['export'][$key]['attributes']['properties'], true);
                    $properties['helperProcessRequestId'] = $requestData['helperProcessRequestId'];
                    $payload['export'][$key]['attributes']['properties'] = json_encode($properties);
                }

                $payload['export'][$key]['name'] = $requestData['name'];
                $payload['export'][$key]['description'] = $requestData['description'];
                $payload['export'][$key]['process_category_id'] = $requestData['process_category_id'];

                if (!isset($existingAssets)) {
                    $payload['export'][$key]['process_manager_id'] = $requestData['manager_id'];
                }
            }
            if (in_array($asset['type'], ['Process', 'Screen', 'Scripts', 'Collections', 'DataConnector'])) {
                $payload['export'][$key]['attributes']['is_template'] = false;
                $payload['export'][$key]['is_template'] = false;
            }
        }
        $options = new Options($postOptions);

        $importer = new Importer($payload, $options);
        $existingAssetsInDatabase = null;
        $importingFromTemplate = true;
        $manifest = $importer->doImport($existingAssetsInDatabase, $importingFromTemplate);
        $rootLog = $manifest[$payload['root']]->log;
        $processId = $rootLog['newId'];

        $process = Process::findOrFail($processId);

        $this->syncLaunchpadAssets($request, $process);

        if (class_exists(self::PROJECT_ASSET_MODEL_CLASS) && !empty($requestData['projects'])) {
            $manifest = $this->getManifest('process', $processId);

            foreach (explode(',', $requestData['projects']) as $project) {
                foreach ($manifest['export'] as $asset) {
                    $model = $asset['model']::find($asset['attributes']['id']);
                    $projectAsset = new (self::PROJECT_ASSET_MODEL_CLASS);
                    $projectAsset->create([
                        'project_id' => $project,
                        'asset_id' => $model->id,
                        'asset_type' => get_class($model),
                    ]);
                }
            }
        }

        return response()->json(['processId' => $processId, 'processName' => $process->name]);
    }

    /**
     *  Update process template bpmn.
     * @param mixed $request
     * @return JsonResponse
     */
    public function updateTemplate($request) : JsonResponse
    {
        $id = (int) $request->id;
        $template = ProcessTemplates::where('id', $id)->firstOrFail();

        $manifest = $this->getManifest('process', $request->process_id);
        $rootUuid = Arr::get($manifest, 'root');
        $export = Arr::get($manifest, 'export');
        $svg = Arr::get($export, $rootUuid . '.attributes.svg', null);

        $template->fill($request->all());
        $template->svg = $svg;
        $template->manifest = json_encode($manifest);

        try {
            $template->saveOrFail();

            return response()->json();
        } catch (Exception $e) {
            return response(
                ['message' => $e->getMessage(),
                    'errors' => ['bpmn' => $e->getMessage()], ],
                422
            );
        }
    }

    /**
     *  Update process template configurations
     * @param Request
     * @return JsonResponse
     */
    public function updateTemplateConfigs($request) : JsonResponse
    {
        $id = (int) $request->id;
        $template = ProcessTemplates::where('id', $id)->firstOrFail();
        $oldTemplateName = $template->name;
        $template->fill($request->except('id'));
        $template->user_id = Auth::user()->id;
        $process = Process::where('name', $oldTemplateName)->where('is_template', 1)->first();
        if ($process) {
            $process->fill($request->except('id'));
        }

        try {
            $template->saveOrFail();

            return response()->json();
        } catch (\Exception $e) {
            return response([
                'message' => $e->getMessage(),
                'errors' => [
                    'bpmn' => $e->getMessage(),
                ],
            ], 422);
        }
    }

    public function updateTemplateManifest(int $processId, $request)  : JsonResponse
    {
        $data = $request->all();

        $model = (new ExportController)->getModel('process')->findOrFail($processId);
        $model->fill($data);
        $model->saveOrFail();

        $manifest = $this->getManifest('process', $processId);

        $postOptions = [];
        foreach ($manifest['export'] as $key => $asset) {
            $postOptions[$key] = [
                'mode' => 'update',
                'isTemplate' => true,
                'saveAssetsMode' => 'saveAllAssets',
            ];
        }
        $options = new Options($postOptions);

        // Create an exporter instance
        $exporter = new Exporter();
        $exporter->export($model, ProcessExporter::class, $options);
        $payload = $exporter->payload();

        // Extract svg from payload
        $svg = Arr::get($payload, 'export.' . $payload['root'] . '.attributes.svg');

        // Update the process template manifest, svg, and user_id
        $processTemplate = ProcessTemplates::where('name', $data['name'])->update([
            'manifest' => json_encode($payload),
            'svg' => $svg,
            'user_id' => \Auth::user()->id,
        ]);

        return response()->json();
    }

    /**
     * Displays Template Configurations
     *
     * @param int $id ID of the process template
     * @return array An array containing the template object, addons, and categories
     * @throws Illuminate\Database\Eloquent\ModelNotFoundException If no template is found with the given ID
     */
    public function configure(int $id) : array
    {
        $template = (object) [];

        $query = ProcessTemplates::select(['name', 'description', 'process_category_id', 'version'])->where('id', $id)->firstOrFail();

        $template->id = $id;
        $template->name = $query->name;
        $template->description = $query->description;
        $template->process_category_id = $query['process_category_id'];
        $template->version = $query->version;
        $template->is_public = $query->is_public;
        $categories = ProcessCategory::orderBy('name')
            ->where('status', 'ACTIVE')
            ->get()
            ->pluck('name', 'id')
            ->toArray();
        $addons = $this->getPluginAddons('edit', compact(['template']));
        $route = ['label' => 'Screens', 'action' => 'screens'];

        return ['process', $template, $addons, $categories, $route, null];
    }

    /**
     *  Delete process template
     * @param mixed $request
     * @return bool
     */
    public function destroy(int $id) : bool
    {
        $response = ProcessTemplates::where('id', $id)->delete();

        return $response;
    }

    /**
     * Get process template manifest.
     *
     * @param string $type
     *
     * @param Request $request
     *
     * @return array
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
    protected function getRequestSortBy(Request $request, $default) : array
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
    protected function getRequestInclude(Request $request) : array
    {
        $include = $request->input('include');

        return $include ? explode(',', $include) : [];
    }

    /**
     * Check if an existing process template with the same name exists.
     * If exists, return an array with the existing template ID and name.
     * Otherwise, return null.
     * @param Request $request
     *
     * @return array|null Array containing the existing template ID and name or null if no existing template found
     */
    public function existingTemplate($request) : ?array
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

    /**
     * Syncs launchpad assets from a guided template to the imported process.
     *
     * @param  Illuminate\Http\Request  $request
     * @param  App\Models\Process  $process
     * @return void
     */
    protected function syncLaunchpadAssets($request, $process)
    {
        if (empty($request->wizardTemplateUuid)) {
            return;
        }

        // Add media collection for the imported process
        $processMediaCollectionName = $process->uuid . '_images_carousel';
        $process->addMediaCollection($processMediaCollectionName);

        // Retrieve the guided template by UUID
        $guidedTemplateUuid = $request->input('wizardTemplateUuid');
        $template = WizardTemplate::where('uuid', $guidedTemplateUuid)->first();

        // Get launchpad slides media from the guided template
        $templateLaunchpadSlides = $template->getMedia($template->media_collection, function (Media $media) {
            return $media->custom_properties['media_type'] === 'launchpadSlides';
        });

        // Iterate over each launchpad slide and add to the imported process media collection
        foreach ($templateLaunchpadSlides as $slide) {
            // Extract order index from file name
            $orderIndex = $this->extractOrderIndexFromFileName($slide->getPath());

            // Add media to the imported process collection
            $media = $process->addMedia($slide->getPath())->preservingOriginal()->toMediaCollection($processMediaCollectionName);

            // Set order column if available
            if (!is_null($orderIndex)) {
                $media->order_column = $orderIndex;
                $media->save();
            }
        }
    }

    /**
     * Extracts order index from the file name.
     *
     * @param string $fileName
     * @return int|null
     */
    protected function extractOrderIndexFromFileName($fileName)
    {
        preg_match('/\d+/', basename($fileName), $matches);
        if (!empty($matches)) {
            return intval($matches[0]) - 1;
        }

        return null;
    }
}
