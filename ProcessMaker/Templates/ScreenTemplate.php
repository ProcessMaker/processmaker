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
use SebastianBergmann\CodeUnit\Exception;

/**
 * Summary of ScreenTemplate
 */
class ScreenTemplate implements TemplateInterface
{
    use HasControllerAddons;

    const PROJECT_ASSET_MODEL_CLASS = 'ProcessMaker\Package\Projects\Models\ProjectAsset';

    /**
     * List process templates
     *
     * @param Request $request The request object.
     *
     * @return array An array containing the list of screen templates.
     */
    // public function index(Request $request)
    // {
    //     // TODO: Implement screen templates listing
    // }

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

        $uuid = $model->uuid;
        $screenType = $model->type;

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
        $exporter->export($model, ScreenExporter::class, $options);
        $payload = $exporter->payload();

        // Create a new process template
        $screenTemplate = ScreenTemplates::make($data)->fill([
            'manifest' => json_encode($payload),
            'user_id' => \Auth::user()->id,
            'screen_type' => $screenType,
        ]);

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
     *  Update process template bpmn.
     * @param mixed $request
     * @return JsonResponse
     */
    //  // TODO: May not need this function for with screen templates
    // public function updateTemplate($request) : JsonResponse
    // {
    //     $id = (int) $request->id;
    //     $template = ProcessTemplates::where('id', $id)->firstOrFail();

    //     $manifest = $this->getManifest('process', $request->process_id);
    //     $rootUuid = Arr::get($manifest, 'root');
    //     $export = Arr::get($manifest, 'export');
    //     $svg = Arr::get($export, $rootUuid . '.attributes.svg', null);

    //     $template->fill($request->all());
    //     $template->svg = $svg;
    //     $template->manifest = json_encode($manifest);

    //     try {
    //         $template->saveOrFail();

    //         return response()->json();
    //     } catch (Exception $e) {
    //         return response(
    //             ['message' => $e->getMessage(),
    //                 'errors' => ['bpmn' => $e->getMessage()], ],
    //             422
    //         );
    //     }
    // }

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
