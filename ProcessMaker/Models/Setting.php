<?php

namespace ProcessMaker\Models;

use Carbon\Carbon;
use DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use ProcessMaker\Traits\ExtendedPMQL;
use ProcessMaker\Traits\SerializeToIso8601;
use Spatie\MediaLibrary\HasMedia\HasMedia;
use Spatie\MediaLibrary\HasMedia\HasMediaTrait;

/**
 * Class Settings
 *
 * @package ProcessMaker\Models
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
 *
 */
class Setting extends Model implements HasMedia
{
    use ExtendedPMQL;
    use HasMediaTrait;
    use SerializeToIso8601;

    protected $connection = 'processmaker';

    //Disk
    public const DISK_CSS = 'settings';
    //collection media library
    public const COLLECTION_CSS_LOGIN = 'login';
    public const COLLECTION_CSS_LOGO = 'logo';
    public const COLLECTION_CSS_ICON = 'icon';

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
        'config'
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
     * @param $existing
     *
     * @return array
     */
    public static function rules($existing = null)
    {
        $unique = Rule::unique('settings')->ignore($existing);

        return [
            'key' => ['required', $unique],
            'config' => 'required',
        ];
    }

    /**
     * Get setting by key
     *
     * @param $key
     *
     * @return null|Setting
     */
    public static function byKey($key)
    {
        return self::where('key', $key)->first();
    }

    /**
     * Get config by key
     *
     * @param $key
     *
     * @return null|Setting
     */
    public static function configByKey($key)
    {
        $setting = self::byKey($key);
        if ($setting) {
            return $setting->config;
        } else {
            return null;
        }
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
        } else {
            return $this->attributes['group'] = $this->attributes['group'];
        }
    }

    public function getConfigAttribute()
    {
        switch ($this->format) {
            case 'text':
            case 'textarea':
            case 'choice':
                return $this->attributes['config'] = $this->attributes['config'];
            case 'boolean':
                return $this->attributes['config'] = (boolean) $this->attributes['config'];
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
    public function scopeFilterGroups($query, $filter)
    {
        $filter = '%' . mb_strtolower($filter) . '%';
        $query->where(function ($query) use ($filter) {
            $query->where(DB::raw('LOWER(`group`)'), 'like', $filter);
        });

        return $query;
    }

    public static function getLogin()
    {
        //default login
        $url = asset(env('LOGIN_LOGO_PATH', '/img/processmaker_login.png'));
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

    public static function getLogo()
    {
        //default logo
        $url = asset(env('MAIN_LOGO_PATH', '/img/processmaker_logo.png'));
        //custom logo
        $setting = self::byKey('css-override');
        if ($setting) {
            $mediaFile = $setting->getMedia(self::COLLECTION_CSS_LOGO);

            foreach ($mediaFile as $media) {
                $url = $media->getFullUrl();
            }
        }

        return $url;
    }

    public static function getIcon()
    {
        //default icon
        $url = asset(env('ICON_PATH_PATH', '/img/processmaker_icon.png'));
        //custom icon
        $setting = self::byKey('css-override');
        if ($setting) {
            $mediaFile = $setting->getMedia(self::COLLECTION_CSS_ICON);

            foreach ($mediaFile as $media) {
                $url = $media->getFullUrl();
            }
        }

        return $url . '?id=' . bin2hex(random_bytes(16));
    }
}
