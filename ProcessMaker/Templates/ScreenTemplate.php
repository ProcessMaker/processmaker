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
        $templates = ScreenTemplates::nonSystem()->with($include);
        $pmql = $request->input('pmql', '');
        $filter = $request->input('filter');

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
            })->get();

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
        // Find the required screen model
        $model = (new ExportController)->getModel('screen')->findOrFail($data['asset_id']);

        // Get the screen manifest
        $response = $this->getManifest('screen', $data['asset_id']);

        if (array_key_exists('error', $response)) {
            return response()->json($response, 400);
        }

        $screenType = $model->type;

        // Loop through each asset in the "export" array and set postOptions "mode" accordingly
        $postOptions = [];
        $options = new Options($postOptions);

        // Create an exporter instance
        $exporter = new Exporter();
        $exporter->export($model, ScreenExporter::class, $options);
        $payload = $exporter->payload();

        // Create a new screen template
        $screenTemplate = ScreenTemplates::make($data)->fill([
            'manifest' => json_encode($payload),
            'user_id' => \Auth::user()->id,
            'screen_type' => $screenType,
            'media_collection' => '',
        ]);

        $screenTemplate->saveOrFail();

        // Update the media_collection attribute after saving
        $mediaCollectionName = 'st-' . $screenTemplate->uuid . '-media';
        $screenTemplate->media_collection = $mediaCollectionName;
        $screenTemplate->saveOrFail();

        // Add media to the media_collection after saving
        $thumbnails = json_decode($data['thumbnails'], true);
        foreach ($thumbnails as $thumbnail) {
            $screenTemplate->addMediaFromBase64($thumbnail['url'])->toMediaCollection($mediaCollectionName);
        }
        $screenTemplate->saveOrFail();

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
    // public function create($request) : JsonResponse
    // {
    //     // TODO: Implement creating a screen from a selected screen template
    // }

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

    public function updateTemplateManifest(int $processId, $request)  : JsonResponse
    {
        // TODO: Implement updating a screen template manifest when editing template in screen builder
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

        $template = ScreenTemplates::where(['name' => $name])->where('id', '!=', $templateId)->first();
        if ($template !== null) {
            // If same asset has been Saved as Template previously,
            // offer to choose between “Update Template” and “Save as New Template”
            return ['id' => $template->id, 'name' => $name];
        }

        return null;
    }
}
