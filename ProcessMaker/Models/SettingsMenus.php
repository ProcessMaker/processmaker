<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use ProcessMaker\Models\ProcessMakerModel;
use ProcessMaker\Models\Setting;

class SettingsMenus extends ProcessMakerModel
{
    use HasFactory;

    protected $connection = 'processmaker';

    protected $table = 'settings_menus';

    public const EMAIL_MENU_GROUP = 'Email';

    public const EMAIL_MENU_ICON = 'envelope-open-text';

    public const EMAIL_MENU_ORDER = 1;

    public const INTEGRATIONS_MENU_GROUP = 'Integrations';

    public const INTEGRATIONS_MENU_ICON = 'puzzle-piece';

    public const INTEGRATIONS_MENU_ORDER = 4;

    public const LOG_IN_AUTH_MENU_GROUP = 'Log-In & Auth';

    public const LOG_IN_AUTH_MENU_ICON = 'sign-in-alt';

    public const LOG_IN_AUTH_MENU_ORDER = 2;

    public const USER_SETTINGS_MENU_GROUP = 'User Settings';

    public const USER_SETTINGS_MENU_ICON = 'users';

    public const USER_SETTINGS_MENU_ORDER = 3;

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
        'menu_group',
        'menu_group_order',
        'ui',
    ];

    public static function rules(): array
    {
        return [
            'menu_group' => 'required',
        ];
    }

    public function groups()
    {
        return $this->hasMany(Setting::class, 'group_id');
    }

    /**
     * Get the Id related to the specific menu_group
     */
    public static function getId($menuName)
    {
        return SettingsMenus::where('menu_group', $menuName)->pluck('id')->first();
    }

    public static function populateSettingMenus()
    {
        // Menu 1. Email SettingsMenus::EMAIL_GROUP_ID = 1
        SettingsMenus::firstOrCreate([
            'menu_group' => SettingsMenus::EMAIL_MENU_GROUP
        ], [
            'menu_group_order' => SettingsMenus::EMAIL_MENU_ORDER,
            'ui' => json_encode(["icon" => SettingsMenus::EMAIL_MENU_ICON]),
        ]);
        // Menu 2. Integrations SettingsMenus::INTEGRATIONS_GROUP_ID = 2
        SettingsMenus::firstOrCreate([
            'menu_group' => SettingsMenus::INTEGRATIONS_MENU_GROUP
        ], [
            'menu_group_order' => SettingsMenus::INTEGRATIONS_MENU_ORDER,
            'ui' => json_encode(["icon" => SettingsMenus::INTEGRATIONS_MENU_ICON]),
        ]);
        // Menu 3. Log-in & Auth SettingsMenus::LOG_IN_AUTH_GROUP_ID = 3
        SettingsMenus::firstOrCreate([
            'menu_group' => SettingsMenus::LOG_IN_AUTH_MENU_GROUP
        ], [
            'menu_group_order' => SettingsMenus::LOG_IN_AUTH_MENU_ORDER,
            'ui' => json_encode(["icon" => SettingsMenus::LOG_IN_AUTH_MENU_ICON]),
        ]);
        // Menu 4. Users Settings SettingsMenus::USER_SETTINGS_GROUP_ID = 4
        SettingsMenus::firstOrCreate([
            'menu_group' => SettingsMenus::USER_SETTINGS_MENU_GROUP
        ], [
            'menu_group_order' => SettingsMenus::USER_SETTINGS_MENU_ORDER,
            'ui' => json_encode(["icon" => SettingsMenus::USER_SETTINGS_MENU_ICON]),
        ]);
    }
}
