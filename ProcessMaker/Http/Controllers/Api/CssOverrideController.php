<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Http\Resources\ApiResource;
use ProcessMaker\Jobs\CompileSass;
use ProcessMaker\Models\Setting;

class CssOverrideController extends Controller
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
        if (!Auth::user()->is_administrator) {
            throw new AuthorizationException(__('Not authorized to complete this request.'));
        }

        $request->request->add(['config' => $this->formatConfig($request)]);

        $setting = Setting::byKey('css-override');

        if (!$setting) {
            $setting = new Setting();
        }

        if ($request->has('fileLogo')) {
            $this->uploadFile($setting->refresh(), $request, 'fileLogo', Setting::COLLECTION_CSS_LOGO, Setting::DISK_CSS);
            Cache::forget('css-logo');
        }
        if ($request->has('fileIcon')) {
            $this->uploadFile($setting->refresh(), $request, 'fileIcon', Setting::COLLECTION_CSS_ICON, Setting::DISK_CSS);
            Cache::forget('css-icon');
        }

        if ($request->has('fileLogin')) {
          $this->uploadFile($setting->refresh(), $request, 'fileLogin', Setting::COLLECTION_CSS_LOGIN, Setting::DISK_CSS);
          Cache::forget('css-login');
        }

        if ($request->has('reset') && $request->input('reset')) {
          $setting->delete();
        }

        $request->validate(Setting::rules($setting));
        $setting->fill($request->input());
        $setting->saveOrFail();

        $this->writeColors(json_decode($request->input('variables', '[]'), true));
        $this->writeFonts(json_decode($request->input("sansSerifFont", '')));
        $this->compileSass(json_decode($request->input('variables', '[]'), true));

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
        if (!Auth::user()->is_administrator) {
            throw new AuthorizationException(__('Not authorized to complete this request.'));
        }

        $setting = Setting::byKey('css-override');
        $request->request->add(['config' => $this->formatConfig($request)]);
        $request->validate(Setting::rules($setting));
        $setting->fill($request->input());
        $setting->saveOrFail();

        if ($request->has('fileLogin') && $request->input('fileLogin')) {
            $this->uploadFile($setting->refresh(), $request, 'fileLogin', Setting::COLLECTION_CSS_LOGO, Setting::DISK_CSS);
            Cache::forget('css-login');
        }
        if ($request->has('fileLogo') && $request->input('fileLogo')) {
            $this->uploadFile($setting->refresh(), $request, 'fileLogo', Setting::COLLECTION_CSS_LOGO, Setting::DISK_CSS);
            Cache::forget('css-logo');
        }
        if ($request->has('fileIcon') && $request->input('fileIcon')) {
            $this->uploadFile($setting->refresh(), $request, 'fileIcon', Setting::COLLECTION_CSS_ICON, Setting::DISK_CSS);
            Cache::forget('css-icon');
        }

        $this->writeColors(json_decode($request->input('variables', '[]'), true));
        $this->writeFonts(json_decode($request->input("sansSerifFont", '')));
        $this->compileSass(json_decode($request->input('variables', '[]'), true));

        return response([], 204);
    }

    /**
     * Write variables in file
     *
     * @param $request
     */
    private function writeColors($data)
    {
        // Now generate the _colors.scss file
        $contents = "// Changed theme colors\n";
        foreach ($data as $key => $value) {
            $contents .= $value['id'] . ': ' . $value['value'] . ";\n";
        }
        File::put(app()->resourcePath('sass') . '/_colors.scss', $contents);

    }

    /**
     * Write variables font in file
     *
     * @param $sansSerif
     * @param $serif
     */
    private function writeFonts($sansSerif)
    {
        $sansSerif = $sansSerif ? $sansSerif : $this->sansSerifFontDefault();
        // Generate the _fonts.scss file
        $contents = "// Changed theme fonts\n";
        $contents .= '$font-family-sans-serif: ' . $sansSerif->id . " !default;\n";
        File::put(app()->resourcePath('sass') . '/_fonts.scss', $contents);
    }

    /**
     * run jobs compile
     */
    private function compileSass()
    {
        // Compile the Sass files
        $this->dispatch(new CompileSass([
            'tag' => 'sidebar',
            'origin' => 'resources/sass/sidebar/sidebar.scss',
            'target' => 'public/css/sidebar.css',
            'user' => Auth::user()->getKey()
        ]));
        $this->dispatch(new CompileSass([
            'tag' => 'app',
            'origin' => 'resources/sass/app.scss',
            'target' => 'public/css/app.css',
            'user' => Auth::user()->getKey()
        ]));
        $this->dispatch(new CompileSass([
            'tag' => 'queues',
            'origin' => 'resources/sass/admin/queues.scss',
            'target' => 'public/css/admin/queues.css',
            'user' => Auth::user()->getKey()
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
            'login' => $request->input('fileLoginName', ''),
            'logo' => $request->input('fileLogoName', ''),
            'icon' => $request->input('fileIconName', ''),
            'variables' => $request->input('variables', ''),
            'sansSerifFont' => $request->input('sansSerifFont', $this->sansSerifFontDefault()),
        ];
    }

    /**
     * Default Sans Serif Font
     *
     * @return \stdClass
     */
    private function sansSerifFontDefault()
    {
        $data = new \stdClass();
        $data->id = "'Open Sans'";
        $data->title = "'Open Sans'";
        return $data;
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

            if (!in_array($type, ['jpg', 'jpeg', 'gif', 'png'])) {
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
            $customMessage = ['mimes' => __('The :attribute must be a file of type: jpg, jpeg, png, or gif.')];
            $this->validate($request, [ $filename => '  mimes:jpg,jpeg,png,gif'], $customMessage);
            $setting->addMedia($request->file($filename))
                ->toMediaCollection($collectionName, $diskName);
        }

    }

}
