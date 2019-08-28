<?php

namespace ProcessMaker\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
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
 *   @OA\Property(property="id", type="string", format="id"),
 *   @OA\Property(property="key", type="string"),
 *   @OA\Property(property="config", type="string"),
 * ),
 * @OA\Schema(
 *   schema="settings",
 *   allOf={@OA\Schema(ref="#/components/schemas/settingsEditable")},
 *   @OA\Property(property="created_at", type="string", format="date-time"),
 *   @OA\Property(property="updated_at", type="string", format="date-time"),
 * )
 *
 */
class Setting extends Model implements HasMedia
{
    use SerializeToIso8601;
    use HasMediaTrait;

    protected $connection = 'processmaker';

    //Disk
    public const DISK_CSS = 'settings';
    //collection media library
    public const COLLECTION_CSS_LOGIN = 'login';
    public const COLLECTION_CSS_LOGO = 'logo';
    public const COLLECTION_CSS_ICON = 'icon';

    protected $casts = [
        'config' => 'array',
    ];

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

    public static function getLogin()
    {
        //default login
        $url = asset(env('MAIN_LOGO_PATH', '/img/processmaker_login.png'));
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
