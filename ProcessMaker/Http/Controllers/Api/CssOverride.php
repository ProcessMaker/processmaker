<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Http\Resources\ApiResource;
use ProcessMaker\Models\Setting;

class CssOverride extends Controller
{

    /**
     * Create a new Settings css-override
     *
     * @param Request $request
     *
     * @throws \Throwable
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
     */
    public function store(Request $request)
    {
        $request->request->add(['config' => $this->formatConfig($request)]);
        $request->validate(Setting::rules());

        $setting = new Setting();
        $setting->fill($request->input());
        $setting->saveOrFail();

        if ($request->has('fileLogo') && $request->input('fileLogo')) {
            $this->uploadFile($setting->refresh(), $request, 'fileLogo', Setting::COLLECTION_CSS_LOGO, Setting::DISK_CSS);
        }
        if ($request->has('fileIcon') && $request->input('fileIcon')) {
            $this->uploadFile($setting->refresh(), $request ,'fileIcon', Setting::COLLECTION_CSS_ICON, Setting::DISK_CSS);
        }



        return new ApiResource($setting);
    }

    /**
     * Update a Setting Css override.
     *
     * @param Request $request
     *
     * @throws \Throwable
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
     */
    public function update(Request $request)
    {
        dd($request->all());
        $setting = Setting::byKey('css-override');
        $request->request->add(['config' => $this->formatConfig($request)]);
        $request->request->add(['key' => 'css-override']);
        $request->validate(Setting::rules($setting));

        if ($request->has('fileLogo')) {
            $this->uploadFile($setting, $request->input('fileLogo'), Setting::COLLECTION_CSS_LOGO, Setting::DISK_CSS);
        }

        $setting->fill($request->input());
        $setting->saveOrFail();



        return response([], 204);
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
        ];
    }

    private function uploadFile(Setting $setting, Request $request, $filename, $collectionName, $diskName)
    {
        $data = $request->all();
        if (preg_match('/^data:image\/(\w+);base64,/', $data[$filename] , $type)) {
            $data = substr($data[$filename], strpos($data[$filename], ',') + 1);
            $type = strtolower($type[1]); // jpg, png, gif

            if (!in_array($type, [ 'jpg', 'jpeg', 'gif', 'png' , 'svg'])) {
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
