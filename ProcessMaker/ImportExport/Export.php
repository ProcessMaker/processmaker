<?php

namespace ProcessMaker\ImportExport;

use ProcessMaker\Models\Screen;

class Export
{
    public static function exportScreen($id)
    {
        $store = new EntityStore();
        $factory = new EntityFactory($store);
        $rootEntity = $factory->make('Screen', $store);

    }
}
