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
use ProcessMaker\ImportExport\Exporters\ScreenExporter;
use ProcessMaker\ImportExport\Importer;
use ProcessMaker\ImportExport\Options;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\ScreenCategory;
use ProcessMaker\Models\ScreenTemplates;
use ProcessMaker\Models\Template;
use ProcessMaker\Templates\ScreenComponents;
use ProcessMaker\Traits\HasControllerAddons;
use ProcessMaker\Traits\HideSystemResources;
use SebastianBergmann\CodeUnit\Exception;

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

        $templates = $templates->select('screen_templates.*')
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
            })->paginate($request->input('per_page', 10));

        return $templates;
    }

    /**
     * Show screen template in screen builder
     *
     * @param mixed $request Request object
     * @return array Returns an array with the screen ID
     */
    // public function show($request) : array
    // {
    //     // TODO: Implement showing selected screen template in screen builder
    // }

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
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException if the screen template is not found
     */
    public function create($request) : JsonResponse
    {
        // Check for existing assets
        $existingAssets = $request->existingAssets;
        $requestData = $existingAssets ? $request->toArray()['request'] : $request;

        $newScreenId = $this->importScreen($requestData, $existingAssets);

        try {
            $screen = Screen::findOrFail($newScreenId);

            $screen->title = $requestData['title'];
            $screen->description = $requestData['description'];
            $screen->save();

            $this->syncProjectAssets($requestData);

            return response()->json(['id' => $newScreenId, 'title' => $screen->title]);
        } catch (Exception $e) {
            return response([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     *  Publish a Screen Template to display in the Public Templates tab
     * @param mixed $request
     * @return JsonResponse
     */
    public function publishTemplate($request) : JsonResponse
    {
        $id = (int) $request->id;
        $template = ScreenTemplates::where('id', $id)->firstOrFail();
        $template->is_public = true;
        $template->saveOrFail();

        return response()->json();
    }

    /**
     *  Update process template configurations
     * @param Request
     * @return JsonResponse
     */
    public function updateTemplateConfigs($request) : JsonResponse
    {
        $id = (int) $request->id;
        $template = ScreenTemplates::where('id', $id)->firstOrFail();
        $template->fill($request->except('id'));
        $template->user_id = Auth::user()->id;

        try {
            $template->saveOrFail();

            return response()->json();
        } catch (\Exception $e) {
            return response([
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
        // TODO: Implement showing selected screen template configurations
    }

    /**
     *  Delete process template
     * @param mixed $request
     * @return bool
     */
    public function destroy(int $id) : bool
    {
        return ScreenTemplates::where('id', $id)->delete();
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

        // Get template options from the request data
        $templateOptions = json_decode($data['templateOptions'], true);

        // Retrieve the screen model
        $newScreen = Screen::findOrFail($screenId);
        $config = $newScreen->config;

        if (is_array($templateOptions)) {
            // Iterate through available options to handle each one
            foreach ($availableOptions as $option => $components) {
                // Check if the current options is in the template options
                if (!in_array($option, $templateOptions)) {
                    // Handle the option accordingly
                    switch($option) {
                        case 'CSS':
                            $newScreen->custom_css = null;
                            break;
                        case 'Fields':
                            // Remove Fields from the screen config
                            $newConfig = $this->removeScreenComponents($newScreen->config, $components);
                            $newScreen->config = $newConfig;
                            break;
                        case 'Layout':
                            $newConfig = $this->removeScreenComponents($newScreen->config, $components);
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

    public function syncProjectAssets($data)
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

    public function removeScreenComponents($config, $components)
    {
        // Iterate through each screen page
        $updatedConfig = [];
        foreach ($config as $pageIndex => $page) {
            $filteredPageItems = [];
            // Iterate through each page item
            if (isset($page['items'])) {
                foreach ($page['items'] as $index => $item) {
                    if (isset($item['component'])) {
                        if ($item['component'] === 'FormMultiColumn' && !$this->checkNestedComponents($item, $components)) {
                            // Its not listed we need to store it but we need to check for existing items in the the multicolumn
                            foreach ($item['items'] as $colIndex => $column) {
                                for ($i = count($column) - 1; $i >= 0; $i--) {
                                    $columnItem = $column[$i];
                                    if ($this->checkNestedComponents($columnItem, $components)) {
                                        if ($columnItem['component'] === 'FormMultiColumn') {
                                            // Recursively check for nested multi columns
                                            $this->filterNestedMultiColumns($columnItem, $components, false);
                                            $column[$i] = $columnItem;
                                        } else {
                                            // Remove the component if its not listed
                                            array_splice($column, $i, 1);
                                        }
                                    } else {
                                        if ($columnItem['component'] === 'FormMultiColumn') {
                                            // Recursively check for nested multi columns
                                            $this->filterNestedMultiColumns($columnItem, $components, false);
                                            $column[$i] = $columnItem;
                                        }
                                    }
                                }

                                $item['items'][$colIndex] = $column;
                            }
                            $filteredPageItems[] = $item;
                        } elseif ($item['component'] === 'FormMultiColumn' && $this->checkNestedComponents($item, $components)) {
                            $filteredMultiColumnItems = [];
                            // Its listed we need to remove it but we need to check for existing items in the the multicolumn
                            foreach ($item['items'] as $colIndex => $column) {
                                for ($i = count($column) - 1; $i >= 0; $i--) {
                                    $columnItem = $column[$i];
                                    if ($this->checkNestedComponents($columnItem, $components)) {
                                        if ($columnItem['component'] === 'FormMultiColumn') {
                                            // Recursively check for nested multi columns
                                            $this->filterNestedMultiColumns($columnItem, $components, true);
                                            foreach ($columnItem as $item) {
                                                $filteredMultiColumnItems[] = $item;
                                            }
                                            $columnItem = null;
                                        } else {
                                            // Remove the component if its not listed
                                            array_splice($column, $i, 1);
                                        }
                                    }

                                    if (!is_null($columnItem)) {
                                        $filteredMultiColumnItems[] = $columnItem;
                                    }
                                }
                                // Remove the multicolumn
                                unset($item['items'][$i]);
                            }

                            // Remove the multicolumn
                            unset($page['items'][$index]);
                            $filteredPageItems = $filteredMultiColumnItems;
                        } elseif (!$this->checkNestedComponents($item, $components)) {
                            $filteredPageItems[] = $item;
                        }
                    }
                }
            }
            $page['items'] = $filteredPageItems;
            $updatedConfig[] = $page;
        }

        return $updatedConfig;
    }

    public function checkNestedComponents($item, $components)
    {
        if (in_array($item['component'], ['BFormComponent', 'BWrapperComponent'])) {
            // Check if the bootstrapComponent is in the specified list for BFormComponent or BWrapperComponent
            if (isset($item['config']['bootstrapComponent'])
                && in_array($item['config']['bootstrapComponent'],
                    $components[$item['component']]['bootstrapComponent'])
            ) {
                return true;
            }
        } else {
            // Check if the component is directly in the specified list
            if (in_array($item['component'], $components)) {
                return true;
            }
        }

        return false;
    }

    private function filterNestedMultiColumns(&$item, $components, $removeMultiColumn)
    {
        if ($removeMultiColumn) {
            $filteredColumnItems = [];
        } else {
            $filteredColumnItems = $item;
        }

        foreach ($item['items'] as $colIndex => $column) {
            for ($i = count($column) - 1; $i >= 0; $i--) {
                $columnItem = $column[$i];
                if ($this->checkNestedComponents($columnItem, $components)) {
                    if ($columnItem['component'] === 'FormMultiColumn') {
                        // Recursively check nested multi-columns
                        $this->filterNestedMultiColumns($columnItem, $components, $removeMultiColumn);
                        $filteredColumnItems[] = $columnItem;
                    } else {
                        // Remove the component if it's not listed
                        array_splice($column, $i, 1);
                        $filteredColumnItems['items'][$colIndex] = $column;
                    }
                } elseif (!$this->checkNestedComponents($columnItem, $components)) {
                    $filteredColumnItems[] = $columnItem;
                }
            }
        }
        $item = $filteredColumnItems;
    }
}
