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
use ProcessMaker\ImportExport\Options;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessTemplates;
use ProcessMaker\Models\Template;

class TemplateController extends Controller
{
    protected array $types = [
        'process' => [Process::class, ProcessTemplate::class, ProcessCategory::class, 'process_category_id', 'process_templates'],
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
     * @param \Illuminate\Http\Request $request
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
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(string $type, Request $request)
    {
        $existingTemplate = $this->template->checkForExistingTemplates($type, $request);

        if (!is_null($existingTemplate)) {
            return response()->json([
                'name' => ['The template name must be unique.'],
                'id' => $existingTemplate['id'],
                'templateName' => $existingTemplate['name'],
            ], 409);
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
        $proTemplates = ProcessTemplates::select()->find($request->id);
        $changes = $request->all();
        $original = array_intersect_key($proTemplates->getOriginal(), $changes);
        $response = $this->template->updateTemplateConfigs($type, $request);
        //Call event to log Template Config changes
        TemplateUpdated::dispatch($changes, $original, false, $proTemplates);

        return $response;
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
            $request->validate(Template::rules($request->id, $this->types[$type][4]));
            $postOptions = $this->checkIfAssetsExist($request);
            if (!empty($postOptions)) {
                $response = [
                    'id' => $request->id,
                    'request' => $request->toArray(),
                    'existingAssets' => $postOptions,
                ];
            } else {
                $response = $this->template->create($type, $request);
            }
        } elseif ($type === 'update-assets') {
            $request['request'] = json_decode($request['request'], true);
            $request['existingAssets'] = json_decode($request['existingAssets'], true);
            $request->validate([
                'id' => 'required|numeric',
                'request' => 'required|array',
                'existingAssets' => 'required|array',
            ]);
            $response = $this->template->create($type, $request);
        }

        // Register the Event
        if (empty($postOptions) && isset($response->getData()->processId)) {
            $process = Process::find($response->getData()->processId);
            ProcessCreated::dispatch($process, ProcessCreated::TEMPLATE_CREATION);
        }

        return $response;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \ProcessMaker\Models\Template  $template
     * @return \Illuminate\Http\Response
     */
    public function delete(string $type, Request $request)
    {
        $template = ProcessTemplates::find($request->id);
        $response = $this->template->deleteTemplate($type, $request);
        //Call event to Store Template Deleted on LOG
        TemplateDeleted::dispatch($template);

        return $response;
    }

    public function preimportValidation(string $type, Request $request)
    {
        $content = $request->file('file')->get();
        $payload = json_decode($content);

        if (!$result = $this->validateImportedFile($content, $request)) {
            return response(
                ['message' => __('The selected file is invalid or not supported for the Templates importer. Please verify that this file is a Template.')],
                422
            );
        }

        return $result;
    }

    private function validateImportedFile($content, $request)
    {
        $decoded = substr($content, 0, 1) === '{' ? json_decode($content) : (($content = base64_decode($content)) && substr($content, 0, 1) === '{' ? json_decode($content) : null);
        $isDecoded = $decoded && is_object($decoded);
        $hasType = $isDecoded && isset($decoded->type) && is_string($decoded->type);
        $validType = $hasType && $decoded->type === 'process_templates_package';

        if ($validType) {
            return (new ImportController())->preview($request, $decoded->version);
        }
    }

    private function checkIfAssetsExist($request)
    {
        $template = ProcessTemplates::findOrFail($request->id);
        $payload = json_decode($template->manifest, true);

        // Get assets form the template
        $existingOptions = [];

        foreach ($payload['export'] as $key => $asset) {
            if (Str::contains($asset['name'], 'Screen Interstitial')
                || Str::contains($asset['model'], 'CommentConfiguration')
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
}
