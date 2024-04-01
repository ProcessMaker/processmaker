<?php

namespace ProcessMaker\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Log;
use ProcessMaker\Traits\ExtendedPMQL;
use ProcessMaker\Traits\SerializeToIso8601;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * Class Settings
 *
 *
 * @property string id
 * @property string key
 * @property array config
 * @property Carbon $updated_at
 * @property Carbon $created_at
 *
 * @OA\Schema(
 *   schema="settingsEditable",
 *   @OA\Property(property="key", type="string"),
 *   @OA\Property(property="config", type="array", @OA\Items(type="object")),
 *   @OA\Property(property="name", type="string"),
 *   @OA\Property(property="helper", type="string"),
 *   @OA\Property(property="group", type="string"),
 *   @OA\Property(property="format", type="string"),
 *   @OA\Property(property="hidden", type="boolean"),
 *   @OA\Property(property="readonly", type="boolean"),
 *   @OA\Property(property="variables", type="string"),
 *   @OA\Property(property="sansSerifFont", type="string"),
 * ),
 * @OA\Schema(
 *   schema="settings",
 *   allOf={
 *     @OA\Schema(ref="#/components/schemas/settingsEditable"),
 *     @OA\Schema(
 *       @OA\Property(property="id", type="string", format="id"),
 *       @OA\Property(property="created_at", type="string", format="date-time"),
 *       @OA\Property(property="updated_at", type="string", format="date-time"),
 *     ),
 *   },
 * )
 */
class Setting extends ProcessMakerModel implements HasMedia
{
    use ExtendedPMQL;
    use InteractsWithMedia;
    use SerializeToIso8601;

    protected $table = 'settings';

    protected $connection = 'processmaker';

    public const DISK_CSS = 'settings';

    public const COLLECTION_CSS_LOGIN = 'login';

    public const COLLECTION_CSS_LOGO = 'logo';

    public const COLLECTION_CSS_ICON = 'icon';

    public const COLLECTION_CSS_FAVICON = 'favicon';

    public const LOGIN_OPTIONS_GROUP = 'Log-In Options';

    public const SESSION_CONTROL_GROUP = 'Session Control';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [
        'id',
        'created_at',
        'updated_at',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'key',
        'config',
        'format',
        'name',
        'helper',
        'group',
        'group_id',
        'hidden',
        'ui',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'hidden' => 'boolean',
        'readonly' => 'boolean',
        'ui' => 'object',
    ];

    /**
     * Validation rules
     *
     * @param  null  $existing
     * @param  bool  $validateConfig
     *
     * @return array
     */
    public static function rules($existing = null, $validateConfig = false)
    {
        $unique = Rule::unique('settings')->ignore($existing);

        return [
            'key' => ['required', $unique],
            'config.*' => ($validateConfig ? ['required', 'valid_variable'] : []),
        ];
    }

    /**
     * @return array
     */
    public static function messages()
    {
        return [
            'config.*.valid_variable' => trans('environmentVariables.validation.name.invalid_variable_name'),
        ];
    }

    /**
     * Get setting by key
     *
     * @param  string  $key
     *
     * @return \ProcessMaker\Models\Setting|null
     * @throws \Exception
     */
    public static function byKey(string $key)
    {
        return (new self)->where('key', $key)->first();
    }

    /**
     * Get config by key
     *
     * @param $key
     *
     * @return array|null
     * @throws \Exception
     */
    public static function configByKey($key)
    {
        $setting = self::byKey($key);

        return $setting instanceof self ? $setting->config : null;
    }

    public function scopeHidden($query)
    {
        return $query->where('hidden', true);
    }

    public function scopeNotHidden($query)
    {
        return $query->where('hidden', false);
    }

    public function getGroupAttribute()
    {
        if ($this->attributes['group'] === null) {
            return $this->attributes['group'] = 'System';
        }

        return $this->attributes['group'] = $this->attributes['group'];
    }

    public function getConfigAttribute()
    {
        switch ($this->format) {
            case 'text':
            case 'textarea':
            case 'file':
            case 'choice':
            case 'range':
                return $this->attributes['config'] = $this->attributes['config'];
            case 'boolean':
                return $this->attributes['config'] = (bool) $this->attributes['config'];
            case 'object':
                if (is_string($this->attributes['config'])) {
                    return $this->attributes['config'] = json_decode($this->attributes['config']);
                } elseif (is_object($this->attributes['config'])) {
                    return $this->attributes['config'];
                }
            case 'array':
            case 'checkboxes':
            default:
                if (is_string($this->attributes['config'])) {
                    return $this->attributes['config'] = json_decode($this->attributes['config'], true);
                } elseif (is_array($this->attributes['config'])) {
                    return $this->attributes['config'];
                }
        }
    }

    /**
     * PMQL value alias for fulltext field
     *
     * @param string $value
     *
     * @return callable
     */
    public function valueAliasFullText($value, $expression)
    {
        return function ($query) use ($value) {
            $this->scopeFilter($query, $value);
        };
    }

    /**
     * Filter settings with a string
     *
     * @param $query
     *
     * @param $filter string
     */
    public function scopeFilter($query, $filter)
    {
        $filter = '%' . mb_strtolower($filter) . '%';

        $query->where(function ($query) use ($filter) {
            $query->where(DB::raw('LOWER(`key`)'), 'like', $filter)
                ->orWhere(DB::raw('LOWER(`name`)'), 'like', $filter)
                ->orWhere(DB::raw('LOWER(`helper`)'), 'like', $filter)
                ->orWhere(DB::raw('LOWER(`group`)'), 'like', $filter);
        });

        return $query;
    }

    /**
     * Filter settings groups with a string
     *
     * @param $query
     *
     * @param $filter string
     */
    public function notHiddenGroups($query, $filter)
    {
        $filter = '%' . mb_strtolower($filter) . '%';

        $query->where(function ($query) use ($filter) {
            $query->where(DB::raw('LOWER(`group`)'), 'like', $filter);
        });

        return $query;
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public static function loginIsDefault()
    {
        return stripos(self::getLogin(), 'processmaker-login') !== false;
    }

    /**
     * @return string
     * @throws \Exception
     */
    public static function getLogin()
    {
        // default login
        $url = asset(config('app.settings.login_logo_path'));

        //custom login
        $setting = self::byKey('css-override');

        if ($setting) {
            $mediaFile = $setting->getMedia(self::COLLECTION_CSS_LOGIN);

            foreach ($mediaFile as $media) {
                $url = $media->getFullUrl();
            }
        }

        return $url . '?id=' . bin2hex(random_bytes(16));
    }

    /**
     * @return string
     * @throws \Exception
     */
    public static function getLogo()
    {
        // default logo
        $url = asset(config('app.settings.main_logo_path'));

        // custom logo
        if (config()->has($key = 'css-override')) {
            $setting = self::byKey($key);

            if ($setting instanceof self) {
                $mediaFile = $setting->getMedia(self::COLLECTION_CSS_LOGO);

                foreach ($mediaFile as $media) {
                    $url = $media->getFullUrl();
                }
            }
        }

        return $url;
    }

    /**
     * @return string
     * @throws \Exception
     */
    public static function getIcon()
    {
        // default icon
        $url = asset(config('app.settings.icon_path'));

        // custom icon
        if (config()->has($key = 'css-override')) {
            $setting = self::byKey($key);

            if ($setting instanceof self) {
                $mediaFile = $setting->getMedia(self::COLLECTION_CSS_ICON);

                foreach ($mediaFile as $media) {
                    $url = $media->getFullUrl();
                }
            }
        }

        return $url . '?id=' . bin2hex(random_bytes(16));
    }

    /**
     * @return string
     * @throws \Exception
     */
    public static function getFavicon()
    {
        // default icon
        $url = asset(config('app.settings.favicon_path'));

        // custom icon
        if (config()->has($key = 'css-override')) {
            $setting = self::byKey($key);

            if ($setting instanceof self) {
                $mediaFile = $setting->getMedia(self::COLLECTION_CSS_FAVICON);

                foreach ($mediaFile as $media) {
                    $url = $media->getFullUrl();
                }
            }
        }

        return $url . '?id=' . bin2hex(random_bytes(16));
    }

    /**
     * Get the groups related to the specific group_id
     *
     * @param int menuId
     *
     * @return array
     */
    public static function groupsByMenu($menuId)
    {
        $query = Setting::query()
            ->select('group')
            ->groupBy('group')
            ->where('group_id', $menuId)
            ->orderBy('group', 'ASC')
            ->notHidden()
            ->pluck('group');
        $response = $query->toArray();
        $result = [];
        foreach ($response as &$value) {
            // Technical debts: we need to add int key to identify a group, currently this is a label
            if (strpos($value, "SSO -") === 0) {
                $value = "- " . $value;
            }
            $result[] = [
                'id' => $value,
                'name' => $value,
            ];
        }

        return $result;
    }

    /**
     * Update the group_id related to Settings
     * @param string $settingsGroup
     * @param null|int $id
     */
    public static function updateSettingsGroup($settingsGroup, $id)
    {
        Setting::where('group', $settingsGroup)->whereNull('group_id')->chunk(
            50,
            function ($settings) use ($id) {
                foreach ($settings as $setting) {
                    if ($id !== null) {
                        $setting->group_id = $id;
                        $setting->save();
                    }
                }
            }
        );
    }

    /**
     * Update the group_id related to Settings
     * @param string $settingsGroup
     * @param null|int $id
     */
    public static function updateAllSettingsGroupId()
    {
        Setting::chunk(100, function ($settings) {
            foreach ($settings as $setting) {
                // Define the value of 'menu_group' based on 'group'
                switch ($setting->group) {
                    case 'Actions By Email':
                    case 'Email Default Settings':
                        $id = SettingsMenus::getId(SettingsMenus::EMAIL_MENU_GROUP);
                        break;
                    case 'Log-In Options': // Log-In and Password
                    case 'LDAP':
                    case 'SSO': // Single Sign-On
                    case 'SCIM':
                    case 'Session Control':
                    case 'SSO - Auth0':
                    case 'SSO - Atlassian':
                    case 'SSO - Facebook':
                    case 'SSO - GitHub':
                    case 'SSO - Google':
                    case 'SSO - Keycloak':
                    case 'SSO - Microsoft':
                    case 'SSO - SAML':
                        $id = SettingsMenus::getId(SettingsMenus::LOG_IN_AUTH_MENU_GROUP);
                        break;
                    case 'User Signals':
                    case 'Users': // Additional Properties
                        $id = SettingsMenus::getId(SettingsMenus::USER_SETTINGS_MENU_GROUP);
                        break;
                    case 'IDP': // Intelligent Document Processing
                    case 'DocuSign':
                    case 'External Integrations': // Enterprise Integrations
                        $id = SettingsMenus::getId(SettingsMenus::INTEGRATIONS_MENU_GROUP);
                        break;
                    default:
                        $id = null;
                        break;
                }
                if ($id !== null) {
                    $setting->group_id = $id;
                    $setting->save();
                    Log::info("Settings group {$setting->group} = {$setting->group_id} updated successfully");
                }
            }
        });
    }
}
