<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use ProcessMaker\Models\ProcessMakerModel;

class SettingsMenus extends ProcessMakerModel
{
    use HasFactory;

    protected $connection = 'processmaker';

    protected $table = 'settings_menus';

    public const EMAIL_GROUP_ID = 1; // 1. Email

    public const EMAIL_MENU_GROUP = 'Email';

    public const EMAIL_MENU_ICON = 'envelope-open-text';

    public const EMAIL_MENU_ORDER = 1;

    public const INTEGRATIONS_GROUP_ID = 2; // 2. Integrations

    public const INTEGRATIONS_MENU_GROUP = 'Integrations';

    public const INTEGRATIONS_MENU_ICON = 'puzzle-piece';

    public const INTEGRATIONS_MENU_ORDER = 4;

    public const LOG_IN_AUTH_GROUP_ID = 3; // 3. Log-in & Auth

    public const LOG_IN_AUTH_MENU_GROUP = 'Log-In & Auth';

    public const LOG_IN_AUTH_MENU_ICON = 'sign-in-alt';

    public const LOG_IN_AUTH_MENU_ORDER = 2;

    public const USER_SETTINGS_GROUP_ID = 4; // 4. Users Settings

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
            'menu_group_icon' => 'required',
        ];
    }
}
