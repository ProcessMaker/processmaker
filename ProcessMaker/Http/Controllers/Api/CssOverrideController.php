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
use ProcessMaker\Jobs\CompileUI;
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

        if (!$setting) {
            $setting = new Setting();
        }
        // Custom Logo
        if ($request->has('fileLogo') && $request->input('fileLogo') !== 'null') {
            $this->uploadFile($setting->refresh(), $request, 'fileLogo', Setting::COLLECTION_CSS_LOGO, Setting::DISK_CSS);
            Cache::forget('css-logo');
        }
        // Custom Icon
        if ($request->has('fileIcon') && $request->input('fileIcon') !== 'null') {
            $this->uploadFile($setting->refresh(), $request, 'fileIcon', Setting::COLLECTION_CSS_ICON, Setting::DISK_CSS);
            Cache::forget('css-icon');
        }
        // Custom Favicon
        if ($request->has('fileFavicon') && $request->input('fileFavicon') !== 'null') {
            $this->uploadFile($setting->refresh(), $request, 'fileFavicon', Setting::COLLECTION_CSS_FAVICON, Setting::DISK_CSS);
            Cache::forget('css-favicon');
        }
        // Custom Login Logo
        if ($request->has('fileLogin') && $request->input('fileLogin') !== 'null') {
            $this->uploadFile($setting->refresh(), $request, 'fileLogin', Setting::COLLECTION_CSS_LOGIN, Setting::DISK_CSS);
            Cache::forget('css-login');
        }
        // Review the reset action
        $reset = false;
        if ($request->has('reset') && $request->input('reset')) {
            $setting->delete();
            $reset = true;
        }

        $request->validate(Setting::rules($setting));
        $setting->fill($request->input());
        $setting->saveOrFail();

        $this->setLoginFooter($request);
        $this->setAltText($request);

        CompileUI::dispatch($request->user('api')->id);

        // Register the Event
        CustomizeUiUpdated::dispatch([], [], $reset);

        return new ApiResource($setting);
    }

    private function setLoginFooter(Request $request)
    {
        $footerContent = $request->input('loginFooter', '');
        if ($footerContent === 'null') {
            $footerContent = '';
        }

        Setting::updateOrCreate([
            'key' => 'login-footer',
        ], [
            'config' => ['html' => $footerContent],
        ]);
    }

    private function setAltText(Request $request)
    {
        $altText = $request->input('altText', '');
        if ($altText === 'null') {
            $altText = '';
        }

        Setting::updateOrCreate([
            'key' => 'logo-alt-text',
        ], [
            'format' => 'text',
            'config' => $altText,
        ]);
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

        CompileUI::dispatch($request->user('api')->id);

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
