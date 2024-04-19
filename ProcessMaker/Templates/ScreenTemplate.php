<?php

namespace ProcessMaker\Templates;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use ProcessMaker\Events\TemplateCreated;
use ProcessMaker\Helpers\ScreenTemplateHelper;
use ProcessMaker\Http\Controllers\Api\ExportController;
use ProcessMaker\ImportExport\Importer;
use ProcessMaker\ImportExport\Options;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\ScreenCategory;
use ProcessMaker\Models\ScreenTemplates;
use ProcessMaker\Models\ScreenType;
use ProcessMaker\Traits\HasControllerAddons;
use ProcessMaker\Traits\HideSystemResources;
use SebastianBergmann\CodeUnit\Exception;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Summary of ScreenTemplate
 */
class ScreenTemplate implements TemplateInterface
{
    use HasControllerAddons;
    use HideSystemResources;

    const PROJECT_ASSET_MODEL_CLASS = 'ProcessMaker\Package\Projects\Models\ProjectAsset';

    /**
     * List process templates
     *
     * @param Request $request The request object.
     *
     * @return array An array containing the list of screen templates.
     */
    public function index(Request $request)
    {
        $orderBy = $this->getRequestSortBy($request, 'name');
        $include = $this->getRequestInclude($request);
        $screenType = (string) $request->input('screen_type');
        $isPublic = (int) $request->input('is_public', false);
        $templates = ScreenTemplates::nonSystem()->with($include)
            ->where('is_public', $isPublic);
        $pmql = $request->input('pmql', '');
        $filter = $request->input('filter');

        if (!empty($screenType)) {
            $templates->where('screen_type', $screenType);
        }

        if (!empty($pmql)) {
            try {
                $templates->pmql($pmql);
            } catch (\ProcessMaker\Query\SyntaxError $e) {
                return response(['error' => 'PMQL error'], 400);
            }
        }

        if (!$isPublic) {
            $templates->where('user_id', Auth::user()->id);
        }

        return $templates
            ->select(
                'screen_templates.id',
                'screen_templates.uuid',
                'screen_templates.unique_template_id',
                'screen_templates.name',
                'screen_templates.description',
                'screen_templates.version',
                'screen_templates.user_id',
                'screen_templates.editing_screen_uuid',
                'screen_templates.screen_category_id',
                'screen_templates.screen_type',
                'screen_templates.is_public',
                'screen_templates.is_default_template',
                'screen_templates.is_system',
                'screen_templates.asset_type',
                'screen_templates.media_collection',
                'screen_templates.screen_custom_css',
                'screen_templates.updated_at',
            )
            ->leftJoin('screen_categories as category', 'screen_templates.screen_category_id', '=', 'category.id')
            ->leftJoin('users as user', 'screen_templates.user_id', '=', 'user.id')
            ->orderBy(...$orderBy)
            ->where(function ($query) use ($filter) {
                $query->where('screen_templates.name', 'like', '%' . $filter . '%')
                    ->orWhere('screen_templates.description', 'like', '%' . $filter . '%')
                    ->orWhere('user.firstname', 'like', '%' . $filter . '%')
                    ->orWhere('user.lastname', 'like', '%' . $filter . '%')
                    ->orWhereIn('screen_templates.id', function ($qry) use ($filter) {
                        $qry->select('assignable_id')
                            ->from('category_assignments')
                            ->leftJoin('screen_categories', function ($join) {
                                $join->on('screen_categories.id', '=', 'category_assignments.category_id');
                                $join->where('category_assignments.category_type', '=', ScreenCategory::class);
                                $join->where('category_assignments.assignable_type', '=', ScreenTemplates::class);
                            })
                            ->where('screen_categories.name', 'like', '%' . $filter . '%');
                    });
            })
            ->paginate($request->input('per_page', 10))
            ->through(function ($template) {
                $template->append(['is_owner']);

                return $template;
            });
    }

    /**
     * Show screen template in screen builder
     *
     * @param mixed $request Request object
     * @return array Returns an array with the screen ID
     */
    public function show($request) : array
    {
        $template = ScreenTemplates::find($request->id);

        $screen = Screen::where('uuid', $template->editing_screen_uuid)->where('is_template', 1)->first();

        if ($screen) {
            return ['id' => $screen->id];
        }

        // Otherwise we need to import the template and create a new screen
        $payload = json_decode($template->manifest, true);
        $postOptions = [];

        foreach ($payload['export'] as $key => $asset) {
            $postOptions[$key] = [
                'mode' => 'copy',
            ];
        }

        $options = new Options($postOptions);
        $importer = new Importer($payload, $options);
        $manifest = $importer->doImport();
        $rootLog = $manifest[$payload['root']]->log;
        $screenId = $rootLog['newId'];

        $newScreen = Screen::find($screenId);
        $newScreen->update([
            'is_template' => 1,
            'title' => $template->name,
            'description' => $template->description,
            'asset_type' => 'SCREEN_TEMPLATE',
        ]);

        ScreenTemplates::where('id', $template->id)->update(['editing_screen_uuid' => $newScreen->uuid]);

        return ['id' => $newScreen->id];
    }

    /**
     * Save new screen template
     * @param mixed $request The HTTP request containing the template data
     * @return JsonResponse The JSON response with the saved template model
     */
    public function save($request) : JsonResponse
    {
        $data = $request->all();

        $screen = Screen::select('custom_css')->where('id', $data['asset_id'])->first();
        $screenCustomCss = $screen['custom_css'];

        // Get the screen manifest
        $manifest = $this->getManifest('screen', $data['asset_id']);
        if (array_key_exists('error', $manifest)) {
            return response()->json($manifest, 400);
        }

        // Create a new screen template
        $screenTemplate = $this->createScreenTemplate($data, $manifest, $screenCustomCss);

        // Save thumbnails
        $this->saveThumbnails($screenTemplate, $data['thumbnails']);

        return response()->json(['model' => $screenTemplate]);
    }

    /**
     * Create a screen from the selected screen template
     *
     * @param mixed $request The HTTP request data
     * @return JsonResponse The JSON response containing the new screen ID
     *
     * @throws ModelNotFoundException if the screen template is not found
     */
    public function create(Request $request): JsonResponse
    {
        $request->validate([
            'templateId' => 'required|integer',
        ]);

        // Check for existing assets
        $existingAssets = $request->existingAssets;
        $requestData = $existingAssets ? $request->toArray()['request'] : $request;

        // The created screen should be based on the selected screen template,
        // regardless of the default template configuration.

        $defaultTemplateId = $requestData['defaultTemplateId'] ?? null;
        if ($defaultTemplateId) {
            $this->updateDefaultTemplate(
                $defaultTemplateId,
                $requestData['type'],
                $requestData['is_public']
            );
        }

        $newScreenId = $this->importScreen($requestData, $existingAssets);

        try {
            $screen = Screen::findOrFail($newScreenId);

            $screen->title = $requestData['title'];
            $screen->description = $requestData['description'];
            $screen->save();

            $this->syncProjectAssets($requestData, $screen->id);

            return response()->json(['id' => $newScreenId, 'title' => $screen->title]);
        } catch (Exception $e) {
            return response([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get the default template for the given screen type
     *
     * @param string $screenType The type of the screen (DISPLAY, FORM, CONVERSATIONAL, EMAIL)
     * @return ScreenTemplates|null The default screen template or null if not found
     */
    public function getDefaultTemplate(string $screenType, int $isPublic): ?ScreenTemplates
    {
        return ScreenTemplates::query()
            ->where([
                'screen_type' => $screenType,
                'is_public' => $isPublic,
                'is_default_template' => 1,
            ])
            ->first();
    }

    /**
     * Update the default template for the given screen type
     *
     * @param int $defaultTemplateId The ID of the new default template
     * @param string $screenType The type of screen (FORM, DISPLAY, EMAIL, CONVERSATIONAL)
     * @param int $isPublic The visibility of the template (0 = private, 1 = public)
     */
    public function updateDefaultTemplate(int $defaultTemplateId, string $screenType, int $isPublic): void
    {
        ScreenTemplates::query()
            ->where([
                'screen_type' => $screenType,
                'is_public' => $isPublic,
            ])
            ->update([
                'is_default_template' => 0,
            ]);

        ScreenTemplates::where('id', $defaultTemplateId)
            ->update([
                'is_default_template' => 1,
            ]);
    }

    /**
     *  Publish a Screen Template to display in the Shared Templates tab
     * @param mixed $request
     * @return JsonResponse
     */
    public function publishTemplate($request) : JsonResponse
    {
        $id = (int) $request->id;
        $template = ScreenTemplates::where('id', $id)->firstOrFail();
        $template->is_default_template = false;
        $template->is_public = true;
        $template->saveOrFail();

        return response()->json();
    }

    /**
     * Update the template.
     */
    public function updateTemplate(Request $request): JsonResponse
    {
        $templateId = $request->has('existingAssetId') ? $request->existingAssetId : $request->id;

        try {
            $request->validate([
                'is_public' => 'sometimes|boolean',
            ]);

            $template = ScreenTemplates::findOrFail($templateId);
            $template->update($request->except('media_collection'));

            $response = response()->json();
        } catch (ModelNotFoundException $e) {
            $response = response()->json(['message' => 'Template not found.'], 404);
        } catch (Exception $e) {
            $response = response()->json(['message' => $e->getMessage()], 500);
        }

        return $response;
    }

    /**
     *  Update process template configurations
     * @param Request
     * @return JsonResponse
     */
    public function updateTemplateConfigs($request) : JsonResponse
    {
        try {
            $id = (int) $request->id;
            $template = ScreenTemplates::where('id', $id)->firstOrFail();
            $this->syncTemplateMedia($template, $request->template_media);

            if ($request->is_public !== $template->is_public) {
                $template->is_default_template = false;
            }

            $template->fill($request->except('id'));
            $template->user_id = Auth::user()->id;

            $template->saveOrFail();

            return response()->json();
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Update the manifest of a screen template.
     *
     * @param  int  $screenId  The ID of the screen
     * @param  Illuminate\Http\Request  $request  The HTTP request containing the updated template data
     * @return JsonResponse  The JSON response indicating the success of the update
     */
    public function updateTemplateManifest(int $screenId, $request)  : JsonResponse
    {
        $data = $request->all();

        // Get the screen manifest
        $manifest = $this->getManifest('screen', $screenId);
        if (array_key_exists('error', $manifest)) {
            return response()->json($manifest, 400);
        }

        // Update the screen template manifest
        $this->updateScreenTemplateData($data, $manifest);

        // Save screen template thumbnails
        $this->saveThumbnails($data['name'], $data['thumbnails']);

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
        $template = ScreenTemplates::select([
            'id',
            'uuid',
            'media_collection',
            'name',
            'description',
            'screen_category_id',
            'version',
            'screen_type',
            'is_public',
        ])->where('id', $id)->firstOrFail();

        $categories = ScreenCategory::orderBy('name')
            ->where('status', 'ACTIVE')
            ->pluck('name', 'id')
            ->toArray();
        $addons = $this->getPluginAddons('edit', compact(['template']));
        $route = ['label' => 'Screens', 'action' => 'screens'];

        $screenTypes = ScreenType::all()->pluck('name')->toArray();

        return ['screen', $template, $addons, $categories, $route, $screenTypes];
    }

    /**
     *  Delete screen template
     * @param mixed $request
     * @return bool
     */
    public function destroy(int $id) : bool
    {
        return ScreenTemplates::find($id)->delete();
    }

    /**
     *  Import screen template
     * @param Request
     * @return JsonResponse
     */
    public function importTemplate($request) : JsonResponse
    {
        try {
            $jsonData = $request->file('file')->get();

            $payload = json_decode($jsonData, true);

            $this->preparePayloadForImport($payload);

            $importOptions = $this->configureImportOptions($payload);

            $this->performImport($payload, $importOptions);

            // Dispatch event for template creation
            TemplateCreated::dispatch($payload);

            return response()->json([], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Delete screen template media
     * @param mixed $request
     * @return bool
     */
    public function deleteMediaImages(Request $request)
    {
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

        $template = ScreenTemplates::where(['name' => $name])->where('id', '!=', $templateId)->first();
        if ($template !== null) {
            // If same asset has been Saved as Template previously,
            // offer to choose between “Update Template” and “Save as New Template”
            return ['id' => $template->id, 'name' => $name];
        }

        return null;
    }

    /**
     * Get process template manifest.
     *
     * @param string $type
     * @param Request $request
     * @return array
     */
    public function getManifest(string $type, int $id) : array
    {
        $response = (new ExportController)->manifest($type, $id);

        return json_decode($response->getContent(), true);
    }

    /**
     * Get included relationships.
     *
     * @param Request $request
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
     * @return array
     */
    protected function getRequestInclude(Request $request) : array
    {
        $include = $request->input('include');

        return $include ? explode(',', $include) : [];
    }

    /**
     * Create a new screen template.
     *
     * @param  array  $data  The data for creating the screen template
     * @param  array  $payload  The payload for the screen template
     * @return \App\Models\ScreenTemplates  The created screen template
     */
    protected function createScreenTemplate(array $data, array $payload, $customCss) : ScreenTemplates
    {
        $screenTemplate = ScreenTemplates::make($data)->fill([
            'manifest' => json_encode($payload),
            'user_id' => auth()->id(),
            'screen_type' => $data['screenType'],
            'screen_custom_css' => $customCss,
            'media_collection' => '',
            'is_public' => filter_var($data['is_public'], FILTER_VALIDATE_BOOLEAN) === true ? 1 : 0,
        ]);
        $screenTemplate->saveOrFail();
        $screenTemplate->media_collection = 'st-' . $screenTemplate->uuid . '-media';
        $screenTemplate->saveOrFail();

        return $screenTemplate;
    }

    private function updateScreenTemplateData(array $data, array $payload)
    {
        ScreenTemplates::where('name', $data['name'])->update([
            'description' => $data['description'],
            'is_public' => $data['make_public'] ? 1 : 0,
            'screen_category_id' => $data['screen_category_id'],
            'manifest' => json_encode($payload),
            'user_id' => auth()->id(),
        ]);
    }

    protected function saveThumbnails($screenTemplate, string $thumbnails)
    {
        $screenTemplate = $this->resolveScreenTemplate($screenTemplate);

        $thumbnails = json_decode($thumbnails, true);

        foreach ($thumbnails as $thumbnail) {
            $screenTemplate
                ->addMediaFromBase64($thumbnail['url'])
                ->toMediaCollection($screenTemplate->media_collection);
        }

        $screenTemplate->saveOrFail();
    }

    /**
     * Resolve the screen template from the given input.
     *
     * @param  mixed  $screenTemplate  The screen template name or model
     * @return \App\Models\ScreenTemplates  The resolved screen template model
     */
    protected function resolveScreenTemplate($screenTemplate): ScreenTemplates
    {
        if ($screenTemplate instanceof ScreenTemplates) {
            return $screenTemplate;
        }

        return ScreenTemplates::where('name', $screenTemplate)->firstOrFail();
    }

    /**
     * Imports a screen using the provided data and existing assets.
     *
     * @param array $data The data for the screen import.
     * @param array $existingAssets The existing assets to be considered during the import.
     */
    protected function importScreen($data, $existingAssets)
    {
        $templateId = (int) $data['templateId'];
        $template = ScreenTemplates::where('id', $templateId)->firstOrFail();
        $template->fill($data->except('id'));
        $template->name = $data['title'];

        $payload = json_decode($template->manifest, true);

        $payload['title'] = $data['title'];
        $payload['description'] = $data['description'];

        $postOptions = [];

        foreach ($payload['export'] as $key => $asset) {
            // Exclude the import of screen categories if the category already exists in the database
            if ($asset['model'] === 'ProcessMaker\Models\ScreenCategory') {
                $screenCategory = ScreenCategory::where('uuid', $key)->first();
                if ($screenCategory !== null) {
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
                // Set title and description for the new screen
                $payload['export'][$key]['attributes']['name'] = $data['title'];
                $payload['export'][$key]['attributes']['description'] = $data['description'];
                $payload['export'][$key]['attributes']['screen_category_id'] = $data['screen_category_id'];

                $payload['export'][$key]['name'] = $data['title'];
                $payload['export'][$key]['description'] = $data['description'];
                $payload['export'][$key]['screen_category_id'] = $data['screen_category_id'];
            }
        }

        $options = new Options($postOptions);
        $importer = new Importer($payload, $options);
        $existingAssetsInDatabase = null;
        $importingFromTemplate = true;
        $manifest = $importer->doImport($existingAssetsInDatabase, $importingFromTemplate);
        $rootLog = $manifest[$payload['root']]->log;
        $newScreenId = $rootLog['newId'];

        $this->handleTemplateOptions($data, $newScreenId);

        return $rootLog['newId'];
    }

    public function handleTemplateOptions($data, $screenId)
    {
        // Define available options and their corresponding components
        $availableOptions = ScreenComponents::getComponents();

        $templateOptions = json_decode($data['templateOptions'], true);
        $newScreen = Screen::findOrFail($screenId);

        if (is_array($templateOptions)) {
            // Iterate through available options to handle each one
            foreach ($availableOptions as $option => $components) {
                // Check if the current options is in the template options
                if (!in_array($option, $templateOptions)) {
                    // Remove the option configs/components from the new screen config
                    switch($option) {
                        case 'CSS':
                            $newScreen->custom_css = null;
                            break;
                        case 'Fields':
                        case 'Layout':
                            $newConfig = ScreenTemplateHelper::removeScreenComponents($newScreen->config, $components);
                            $newScreen->config = $newConfig;
                            break;
                        default:
                            break;
                    }
                }
            }
        }
        $newScreen->save();
    }

    /**
     * Synchronizes project assets with the given data and new screen ID.
     */
    public function syncProjectAssets($data, int $newScreenId): void
    {
        if (class_exists(self::PROJECT_ASSET_MODEL_CLASS) && !empty($data['projects'])) {
            $manifest = $this->getManifest('screen', $newScreenId);

            foreach (explode(',', $data['projects']) as $project) {
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
    }

    private function syncTemplateMedia($template, $media)
    {
        // Get the UUIDs of updated media
        $updatedTemplateMediaUuids = $this->getMediaUuids($media);

        // Delete media that is missing from the request media
        $this->deleteMissingMedia($template, $updatedTemplateMediaUuids);

        // Get the UUIDs of existing media associated with the template
        $existingMediaUuids = $this->getMediaUuids($template->template_media);

        // Add new media that is not already associated with the template
        $this->addNewMedia($template, $media, $existingMediaUuids);
    }

    private function getMediaUuids($media)
    {
        return array_map(function ($obj) {
            return $obj['uuid'];
        }, $media);
    }

    private function deleteMissingMedia($template, $updatedTemplateMediaUuids)
    {
        $result = array_filter($template->template_media, function ($obj) use ($updatedTemplateMediaUuids) {
            return !in_array($obj['uuid'], $updatedTemplateMediaUuids);
        });

        foreach ($result as $media) {
            Media::where('uuid', $media['uuid'])->delete();
        }
    }

    private function addNewMedia($template, $media, $existingMediaUuids)
    {
        $result = array_filter($media, function ($obj) use ($existingMediaUuids) {
            return !in_array($obj['uuid'], $existingMediaUuids);
        });

        foreach ($result as $media) {
            $template->addMediaFromBase64($media['url'])->toMediaCollection($template->media_collection);
        }
    }

    /**
     * Prepare payload for import.
     *
     * @param  array  $payload
     * @return void
     */
    private function preparePayloadForImport(array &$payload): void
    {
        foreach ($payload['export'] as &$asset) {
            // Modify asset attributes as needed
            $asset['attributes']['editing_screen_uuid'] = null;
        }
    }

    /**
     * Configure import options.
     *
     * @param  array  $payload
     * @return \Importer\Options
     */
    private function configureImportOptions(array $payload): Options
    {
        $postOptions = [];

        foreach ($payload['export'] as $key => $asset) {
            // Set import mode for each asset
            $postOptions[$key] = ['mode' => 'copy'];
        }

        return new Options($postOptions);
    }

    /**
     * Perform the import operation.
     *
     * @param  array  $payload
     * @param  \Importer\Options  $options
     * @return void
     */
    private function performImport(array $payload, Options $options): void
    {
        $importer = new Importer($payload, $options);
        $importer->doImport();
    }
}
