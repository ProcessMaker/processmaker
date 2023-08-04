<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use ProcessMaker\Events\CustomizeUiUpdated;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Http\Resources\ApiResource;
use ProcessMaker\Jobs\CompileSass;
use ProcessMaker\Models\Setting;

class CssOverrideController extends Controller
{
    /**
     * A whitelist of attributes that should not be
     * sanitized by our SanitizeInput middleware.
     *
     * @var array
     */
    public $doNotSanitize = [
        'loginFooter',
    ];

    /**
     * Create a new Settings css-override
     *
     * @param Request $request
     *
     * @return ApiResource
     *
     * @OA\Post(
     *     path="/customize-ui",
     *     summary="Create or update a new setting",
     *     operationId="updateCssSetting",
     *     tags={"CssSettings"},
     *     @OA\RequestBody(
     *       required=true,
     *       @OA\JsonContent(
     *         @OA\Property(property="variables", type="string"),
     *         @OA\Property(property="sansSerifFont", type="string"),
     *       )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="success",
     *         @OA\JsonContent(ref="#/components/schemas/settings")
     *     ),
     * )
     * @throws \Throwable
     */
    public function store(Request $request)
    {
        if (!Auth::user()->is_administrator) {
            throw new AuthorizationException(__('Not authorized to complete this request.'));
        }

        $request->request->add(['config' => $this->formatConfig($request)]);

        $setting = Setting::byKey('css-override');

        // If setting does not exist will define
        if (!$setting) {
            $setting = new Setting();
        }
        $changes = [];
        $initConfig = $request->input('config');
        if ($request->has('fileLogo')) {
            $this->uploadFile(
                $setting->refresh(),
                $request,
                'fileLogo',
                Setting::COLLECTION_CSS_LOGO,
                Setting::DISK_CSS
            );
            Cache::forget('css-logo');
            // Check if the default logo value was updated
            if (isset($initConfig['logo']) && $initConfig['logo'] != "null" && !is_null($initConfig['logo'])) {
                $changes['logo'] = $initConfig['logo'];
            }
        }
        if ($request->has('fileIcon')) {
            $this->uploadFile(
                $setting->refresh(),
                $request,
                'fileIcon',
                Setting::COLLECTION_CSS_ICON,
                Setting::DISK_CSS
            );
            Cache::forget('css-icon');
            // Check if the default icon value was updated
            if (isset($initConfig['icon']) && $initConfig['icon'] != "null" && !is_null($initConfig['icon'])) {
                $changes['icon'] = $initConfig['icon'];
            }
        }
        if ($request->has('fileFavicon')) {
            $this->uploadFile(
                $setting->refresh(),
                $request,
                'fileFavicon',
                Setting::COLLECTION_CSS_FAVICON,
                Setting::DISK_CSS
            );
            Cache::forget('css-favicon');
            // Check if the default favicon value was updated
            if (isset($initConfig['favicon']) && $initConfig['favicon'] != "null" && !is_null($initConfig['favicon'])) {
                $changes['favicon'] = $initConfig['favicon'];
            }
        }
        if ($request->has('fileLogin')) {
            $this->uploadFile(
                $setting->refresh(),
                $request,
                'fileLogin',
                Setting::COLLECTION_CSS_LOGIN,
                Setting::DISK_CSS
            );
            Cache::forget('css-login');
            // Check if the default value was updated
            if (isset($initConfig['login']) && $initConfig['login'] != "null" && !is_null($initConfig['login'])) {
                $changes['login'] = $initConfig['login'];
            }
        }
        if ($request->has('variables')) {
            $changes['variables'] = $initConfig['variables'];
        }
        if ($request->has('sansSerifFont')) {
            $changes['sansSerifFont'] = $initConfig['sansSerifFont'];
        }
        // Review if the reset is true
        $reset = false;
        if ($request->has('reset') && $request->input('reset')) {
            Setting::destroy($setting->id);
            $reset = true;
        }

        $request->validate(Setting::rules($setting));
        $setting->fill($request->input());
        // Get the original values
        $original = array_intersect_key($setting->getOriginal(), $setting->getDirty());
        // Save the changes
        $setting->saveOrFail();
        // Get the changes
        if (isset($setting->getChanges()['config'])) {
            $changes = (array) json_decode($setting->getChanges()['config']);
        }

        $footer = $this->setLoginFooter($request);
        $altText = $this->setAltText($request);
        $this->writeColors(json_decode($request->input('variables', '[]'), true));
        $this->writeFonts(json_decode($request->input('sansSerifFont', '')));
        $this->compileSass($request->user('api')->id, json_decode($request->input('variables', '[]'), true));

        $changes = array_merge(
            $footer['changes'] ?? [],
            $altText['changes'] ?? [],
            $changes
        );

        if (!empty($changes)) {
            $original['config'] = array_merge(
                $footer['original'] ?? [],
                $altText['original'] ?? [],
                $original['config'] ?? []
            );
            event(new CustomizeUiUpdated($original, $changes, $reset));
        }

        return new ApiResource($setting);
    }

    private function setLoginFooter(Request $request)
    {
        $footerContent = $request->input('loginFooter', '');
        if ($footerContent === 'null') {
            $footerContent = '';
        }

        $original = Setting::where('key', 'login-footer')->first();

        $setting = Setting::updateOrCreate([
            'key' => 'login-footer',
        ], [
            'config' => ['html' => $footerContent],
        ]);

        $response = [];

        if ((!$setting->wasRecentlyCreated && $setting->wasChanged()) || $setting->wasRecentlyCreated) {
            $response = [
                'changes' => ['loginFooter' => $setting->getAttribute('config')['html'] ?? ''],
                'original' => ['loginFooter' => $original->getAttribute('config')['html'] ?? ''],
            ];
        }

        return $response;
    }

    private function setAltText(Request $request)
    {
        $altText = $request->input('altText', '');
        if ($altText === 'null') {
            $altText = '';
        }

        $original = Setting::where('key', 'logo-alt-text')->first();

        $setting = Setting::updateOrCreate([
            'key' => 'logo-alt-text',
        ], [
            'format' => 'text',
            'config' => $altText,
        ]);

        $response = [];

        if ((!$setting->wasRecentlyCreated && $setting->wasChanged()) || $setting->wasRecentlyCreated) {
            $response = [
                'changes' => ['altText' => $setting->getAttribute('config') ?? ''],
                'original' => ['altText' => $original->getAttribute('config') ?? ''],
            ];
        }

        return $response;
    }

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
        if ($request->has('fileFavicon') && $request->input('fileFavicon')) {
            $this->uploadFile($setting->refresh(), $request, 'fileFavicon', Setting::COLLECTION_CSS_FAVICON, Setting::DISK_CSS);
            Cache::forget('css-favicon');
        }

        $this->setLoginFooter($request);
        $this->setAltText($request);

        $this->writeColors(json_decode($request->input('variables', '[]'), true));
        $this->writeFonts(json_decode($request->input('sansSerifFont', '')));
        $this->compileSass($request->user('api')->id, json_decode($request->input('variables', '[]'), true));

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
    private function compileSass($userId)
    {
        // Compile the Sass files
        $this->dispatch(new CompileSass([
            'tag' => 'sidebar',
            'origin' => 'resources/sass/sidebar/sidebar.scss',
            'target' => 'public/css/sidebar.css',
            'user' => $userId,
        ]));
        $this->dispatch(new CompileSass([
            'tag' => 'app',
            'origin' => 'resources/sass/app.scss',
            'target' => 'public/css/app.css',
            'user' => $userId,
        ]));
        $this->dispatch(new CompileSass([
            'tag' => 'queues',
            'origin' => 'resources/sass/admin/queues.scss',
            'target' => 'public/css/admin/queues.css',
            'user' => $userId,
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
            'favicon' => $request->input('fileFaviconName', ''),
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
        } elseif (isset($data[$filename]) && !empty($data[$filename]) && $data[$filename] != 'null') {
            $customMessage = ['mimes' => __('The :attribute must be a file of type: jpg, jpeg, png, or gif.')];
            $this->validate($request, [$filename => '  mimes:jpg,jpeg,png,gif'], $customMessage);
            $setting->addMedia($request->file($filename))
                ->toMediaCollection($collectionName, $diskName);
        }
    }
}
