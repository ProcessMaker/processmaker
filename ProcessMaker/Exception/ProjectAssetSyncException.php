<?php

namespace ProcessMaker\Exception;

use Exception;

class ProjectAssetSyncException extends Exception
{
    protected $message = 'Error syncing project assets.';
}
