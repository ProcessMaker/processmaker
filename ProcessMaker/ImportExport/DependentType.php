<?php

namespace ProcessMaker\ImportExport;

abstract class DependentType
{
    const SCRIPTS = 'scripts';

    const CATEGORIES = 'categories';

    const SCREENS = 'screens';

    const NOTIFICATION_SETTINGS = 'process_notification_settings';

    const ENVIRONMENT_VARIABLES = 'environment_variables';

    const SCRIPT_EXECUTORS = 'script_executors';

    const SUB_PROCESSES = 'sub_processes';
}
