<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Http\Resources\ApiResource;
use ProcessMaker\Jobs\CompileSass;
use ProcessMaker\Models\Setting;

class CssOverride extends Controller
{

    /**
     * Create a new Settings css-override
     *
     * @param Request $request
     *
     * @return ApiResource
     *
     * @OA\Post(
     *     path="/css_settings",
     *     summary="Save a new settings css override",
     *     operationId="createSettingsCss",
     *     tags={"SettingsCss"},
     *     @OA\RequestBody(
     *       required=true,
     *       @OA\JsonContent(ref="#/components/schemas/settingsEditable")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="success",
     *         @OA\JsonContent(ref="#/components/schemas/settings")
     *     ),
     * )
     * @throws \Throwable
     *
     */
    public function store(Request $request)
    {
        $request->request->add(['config' => $this->formatConfig($request)]);

        $setting = Setting::byKey('css-override');

        if (!$setting) {
            $setting = new Setting();
        }
        $request->validate(Setting::rules($setting));
        $setting->fill($request->input());
        $setting->saveOrFail();

        if ($request->has('fileLogo')) {
            $this->uploadFile($setting->refresh(), $request, 'fileLogo', Setting::COLLECTION_CSS_LOGO, Setting::DISK_CSS);
            Cache::forget('css-logo');
        }
        if ($request->has('fileIcon')) {
            $this->uploadFile($setting->refresh(), $request, 'fileIcon', Setting::COLLECTION_CSS_ICON, Setting::DISK_CSS);
            Cache::forget('css-icon');
        }

        $this->writeColors(json_decode($request->input('variables', '[]'), true));

        return new ApiResource($setting);
    }

    /**
     * Update a Setting Css override.
     *
     * @param Request $request
     *
     * @return ApiResource
     *
     * @OA\Put(
     *     path="/css_settings",
     *     summary="Update a setting css",
     *     operationId="updateSettingCss",
     *     tags={"SettingsCss"},
     *     @OA\Parameter(
     *         description="ID of setting to return",
     *         in="path",
     *         name="css_override_id",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *         )
     *     ),
     *     @OA\RequestBody(
     *       required=true,
     *       @OA\JsonContent(ref="#/components/schemas/settingsEditable")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="success",
     *         @OA\JsonContent(ref="#/components/schemas/settings")
     *     ),
     * )
     * @throws \Throwable
     *
     */
    public function update(Request $request)
    {
        $setting = Setting::byKey('css-override');
        $request->request->add(['config' => $this->formatConfig($request)]);
        $request->validate(Setting::rules($setting));
        $setting->fill($request->input());
        $setting->saveOrFail();

        if ($request->has('fileLogo') && $request->input('fileLogo')) {
            $this->uploadFile($setting->refresh(), $request, 'fileLogo', Setting::COLLECTION_CSS_LOGO, Setting::DISK_CSS);
            Cache::forget('css-logo');
        }
        if ($request->has('fileIcon') && $request->input('fileIcon')) {
            $this->uploadFile($setting->refresh(), $request, 'fileIcon', Setting::COLLECTION_CSS_ICON, Setting::DISK_CSS);
            Cache::forget('css-icon');
        }

        $this->writeColors(json_decode($request->input('variables', '[]'), true));

        return response([], 204);
    }

    /**
     * Write variables in file
     *
     * @param $data
     */
    private function writeColors($data)
    {
        // Now generate the _colors.scss file
        $contents = "// Changing the theme colors\n";
        // Build out the file contents
        foreach ($data as $key => $value) {
            $contents .= $value['id'] . ': ' . $value['value'] . ";\n";
        }
        //now store it.
        File::put(app()->resourcePath('sass') . '/_colors.scss', $contents);

        //compiled
        $this->dispatch(new CompileSass([
            'tag' => 'sidebar',
            'origin' => 'resources/sass/sidebar/sidebar.scss',
            'target' => 'public/css/sidebar.css'
        ]));
        $this->dispatch(new CompileSass([
            'tag' => 'app',
            'origin' => 'resources/sass/app.scss',
            'target' => 'public/css/app.css'
        ]));
        $this->dispatch(new CompileSass([
            'tag' => 'queues',
            'origin' => 'resources/sass/admin/queues.scss',
            'target' => 'public/css/admin/queues.css'
        ]));
    }

    /**
     * Format field config
     *
     * @param Request $request
     *
     * @return array
     */
    private function formatConfig(Request $request)
    {
        return [
            'logo' => $request->input('fileLogoName', ''),
            'icon' => $request->input('fileIconName', ''),
            'variables' => $request->input('variables', ''),
            'font' => $request->input('font', ''),
        ];
    }

    /**
     * Upload file
     *
     * @param Setting $setting
     * @param Request $request
     * @param $filename
     * @param $collectionName
     * @param $diskName
     * @throws \Exception
     */
    private function uploadFile(Setting $setting, Request $request, $filename, $collectionName, $diskName)
    {
        $data = $request->all();
        if (preg_match('/^data:image\/(\w+);base64,/', $data[$filename], $type)) {
            $data = substr($data[$filename], strpos($data[$filename], ',') + 1);
            $type = strtolower($type[1]); // jpg, png, gif

            if (!in_array($type, ['jpg', 'jpeg', 'gif', 'png', 'svg'])) {
                throw new \Exception('invalid image type');
            }

            $data = base64_decode($data);

            if ($data === false) {
                throw new \Exception('base64_decode failed');
            }

            file_put_contents("/tmp/img.{$type}", $data);

            $setting->addMedia("/tmp/img.{$type}")
                ->toMediaCollection($collectionName, $diskName);
        } else if (isset($data[$filename]) && !empty($data[$filename]) && $data[$filename] != 'null') {
            $setting->addMedia($request->file($filename))
                ->toMediaCollection($collectionName, $diskName);
        }

    }

}
