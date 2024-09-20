<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use ProcessMaker\Events\ProcessCreated;
use ProcessMaker\Events\TemplateDeleted;
use ProcessMaker\Events\TemplatePublished;
use ProcessMaker\Events\TemplateUpdated;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Http\Resources\TemplateCollection;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessCategory;
use ProcessMaker\Models\ProcessTemplates;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\ScreenCategory;
use ProcessMaker\Models\ScreenTemplates;
use ProcessMaker\Models\Template;
use ProcessMaker\Traits\ProjectAssetTrait;

class TemplateController extends Controller
{
    use ProjectAssetTrait;

    protected array $types = [
        'process' => [
            Process::class,
            ProcessTemplates::class,
            ProcessCategory::class,
            'process_category_id',
            'process_templates',
            'process_templates_package',
        ],
        'screen' => [
            Screen::class,
            ScreenTemplates::class,
            ScreenCategory::class,
            'screen_category_id',
            'screen_templates',
            'screen_templates_package',
        ],
    ];

    private $template;

    public function __construct(Template $template)
    {
        $this->template = $template;
    }

    /**
     * Get list Process Templates
     *
     * @param string $type
     * @param Request $request
     * @return TemplateCollection
     */
    public function index(string $type, Request $request)
    {
        $templates = $this->template->index($type, $request);

        if ($request->input('per_page') === '0') {
            return $templates;
        }

        return new TemplateCollection($templates);
    }

    public function show(string $type, Request $request)
    {
        return $this->template->show($type, $request);
    }

    /**
     * Store a newly created template
     *
     * @param string $type
     * @param  Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(string $type, Request $request)
    {
        $existingTemplate = $this->checkForExistingTemplates($type, $request);
        if (!empty($existingTemplate)) {
            return $existingTemplate;
        }
        $request->validate(Template::rules($request->id, $this->types[$type][4]));
        $storeTemplate = $this->template->store($type, $request);
        TemplatePublished::dispatch($request->all());

        return $storeTemplate;
    }

    /**
     * Update the template manifest
     *
     * @param  string  $type
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function updateTemplateManifest(string $type, int $processId, Request $request)
    {
        return $this->template->updateTemplateManifest($type, $processId, $request);
    }

    /**
     * Update stored template with new.
     *
     * @param  string  $type
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function updateTemplate(string $type, Request $request)
    {
        $existingTemplate = $this->checkForExistingTemplates($type, $request);
        if (!empty($existingTemplate)) {
            return $existingTemplate;
        }

        $request->validate(Template::rules($request->id, $this->types[$type][4]));

        return $this->template->updateTemplate($type, $request);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  string  $type
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function updateTemplateConfigs(string $type, Request $request)
    {
        $request->validate(Template::rules($request->id, $this->types[$type][4]));
        if ($type === 'process') {
            $template = ProcessTemplates::select()->find($request->id);
            $changes = $request->all();
            $original = array_intersect_key($template->getOriginal(), $changes);
            // Call event to log Template Config changes
            TemplateUpdated::dispatch($changes, $original, false, $template);
        } elseif ($type === 'screen') {
            if (!$request->media_collection) {
                $existingTemplate = $this->checkForExistingTemplates($type, $request);
            }

            if (!empty($existingTemplate)) {
                return $existingTemplate;
            }
        }

        return $this->template->updateTemplateConfigs($type, $request);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  string  $type
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function create(string $type, Request $request)
    {
        if ($type === 'process') {
            return $this->createProcess($request);
        } elseif ($type === 'screen') {
            return $this->createScreen($request);
        } elseif ($type === 'update-assets') {
            return $this->updateAssets($request);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  Template  $template
     * @return \Illuminate\Http\Response
     */
    public function delete(string $type, Request $request)
    {
        if ($type === 'process') {
            $template = ProcessTemplates::find($request->id);
            // Call event to Store Template Deleted on LOG
            TemplateDeleted::dispatch($template);
        }

        return $this->template->deleteTemplate($type, $request);
    }

    /**
     * Import template
     *
     * @param  Template  $template
     * @return \Illuminate\Http\Response
     */
    public function import(string $type, Request $request)
    {
        $response = $this->preimportValidation($type, $request);

        if ($response->getStatusCode() === 422) {
            return $response;
        }

        return $this->template->importTemplate($type, $request);
    }

    public function preimportValidation(string $type, Request $request)
    {
        $content = $request->file('file')->get();

        if (!$result = $this->validateImportedFile($content, $request, $type)) {
            return response(
                ['message' => __('The selected file is invalid or not supported for the ' . ucfirst($type) .
                     ' Templates importer. Please verify that this file is a ' . ucfirst($type) . ' Template.'),
                ],
                422
            );
        }

        return $result;
    }

    /**
     * Set a template as a Public Template
     *
     * @param  Template  $template
     * @return \Illuminate\Http\Response
     */
    public function publishTemplate(string $type, Request $request)
    {
        return $this->template->publishTemplate($type, $request);
    }

    /**
     * Delete media from the template
     *
     * @param  Template  $template
     * @return \Illuminate\Http\Response
     */
    public function deleteMediaImages(string $type, Request $request)
    {
        return $this->template->deleteMediaImages($type, $request);
    }

    public function applyTemplate(string $type, Request $request)
    {
        return $this->template->applyTemplate($type, $request);
    }

    private function validateImportedFile($content, $request, $type)
    {
        $decoded = null;
        if (substr($content, 0, 1) === '{') {
            $decoded = json_decode($content);
        } else {
            $decodedContent = base64_decode($content);
            if ($decodedContent && substr($decodedContent, 0, 1) === '{') {
                $decoded = json_decode($decodedContent);
            }
        }

        if (!$decoded || !is_object($decoded) || !isset($decoded->type) || !is_string($decoded->type)) {
            return null; // Invalid JSON format or Missing or invalid type property
        }

        // Validate the type
        $validTypes = ['process_templates_package', 'screen_templates_package'];
        if (!in_array($decoded->type, $validTypes) || $decoded->type !== $this->types[$type][5]) {
            return null; // Invalid package type
        }

        // If the type is valid, proceed with the preview
        return (new ImportController())->preview($request, $decoded->version);
    }

    private function checkIfAssetsExist($request)
    {
        $template = ProcessTemplates::findOrFail($request->id);
        $payload = json_decode($template->manifest, true);

        // Get assets form the template
        $existingOptions = [];

        foreach ($payload['export'] as $key => $asset) {
            if (Str::contains($asset['model'], 'CommentConfiguration')
            ) {
                unset($payload['export'][$key]);
                continue;
            }

            if (!$asset['model']::where('uuid', $key)->exists()
                || $payload['root'] === $asset['attributes']['uuid']
                || Str::contains($asset['type'], 'Category')
            ) {
                continue;
            }

            $item = [
                'type' => ($asset['type'] === 'Process') ? 'SubProcess' : $asset['type'],
                'uuid' => $key,
                'model' => $asset['model'],
                'name' => $asset['name'],
                'mode' => 'copy',
            ];

            $existingOptions[] = $item;
        }

        return $existingOptions;
    }

    protected function createProcess(Request $request)
    {
        $request->validate(Template::rules($request->id, $this->types['process'][4]));
        $postOptions = $this->checkIfAssetsExist($request);

        if (!empty($postOptions)) {
            $response = [
                'id' => $request->id,
                'request' => $request->toArray(),
                'existingAssets' => $postOptions,
            ];
        } else {
            $response = $this->template->create('process', $request);
        }

        if ($request->has('projects')) {
            $projectIds = explode(',', $request->input('projects'));
            $this->updateProjectUpdatedAt($projectIds);
        }
        $this->dispatchProcessCreatedEvent($postOptions, $response);

        return $response;
    }

    protected function createScreen(Request $request)
    {
        $request['templateId'] = $request->templateId ?? $request->defaultTemplateId;
        $request->validate(Screen::rules($request->id));
        $response = $this->template->create('screen', $request);

        if ($request->has('projects')) {
            $projectIds = explode(',', $request->input('projects'));
            $this->updateProjectUpdatedAt($projectIds);
        }

        return $response;
    }

    protected function updateAssets(Request $request)
    {
        $request['request'] = json_decode($request['request'], true);
        $request['existingAssets'] = json_decode($request['existingAssets'], true);
        $request->validate([
            'id' => 'required|numeric',
            'request' => 'required|array',
            'existingAssets' => 'required|array',
        ]);

        return $this->template->create('update-assets', $request);
    }

    protected function dispatchProcessCreatedEvent($postOptions, $response)
    {
        if (empty($postOptions) && isset($response->getData()->processId)) {
            $process = Process::find($response->getData()->processId);
            ProcessCreated::dispatch($process, ProcessCreated::TEMPLATE_CREATION);
        }
    }

    protected function checkForExistingTemplates(string $type, Request $request)
    {
        $existingTemplate = $this->template->checkForExistingTemplates($type, $request);

        if (!is_null($existingTemplate)) {
            return response()->json([
                'name' => ['The template name must be unique.'],
                'id' => $existingTemplate['id'],
                'templateName' => $existingTemplate['name'],
                'owner_id' => $existingTemplate['owner_id'],
            ], 409);
        }
    }
}
