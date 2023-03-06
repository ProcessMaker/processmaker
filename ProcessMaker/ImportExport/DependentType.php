<?php

namespace ProcessMaker\ImportExport;

abstract class DependentType
{
    const SCRIPTS = 'scripts';

    const CATEGORIES = 'categories';

    const SCREENS = 'screens';

    const INTERSTITIAL_SCREEN = 'interstitial_screen';

    const NOTIFICATION_SETTINGS = 'process_notification_settings';

    const ENVIRONMENT_VARIABLES = 'environment_variables';

    const ENVIRONMENT_VARIABLE_VALUE = 'environment_variables_value';

    const SCRIPT_EXECUTORS = 'script_executors';

    const SUB_PROCESSES = 'sub_processes';

    const GROUPS = 'groups';

    const USERS = 'users';

    const USER_ASSIGNMENT = 'user_assignment';

    const GROUP_ASSIGNMENT = 'group_assignment';

    const USER_RECIPIENT = 'user_recipient';

    const GROUP_RECIPIENT = 'group_recipient';
}
