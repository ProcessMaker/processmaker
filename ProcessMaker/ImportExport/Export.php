<?php

namespace ProcessMaker\ImportExport;

use ProcessMaker\Models\Screen;
use Illuminate\Support\Facades\App;

class Export
{
    public static function exportScreen($id)
    {
        App::singleton(EntityStore::class);
        App::singleton(AssetStore::class);
        app(EntityFactory::class)->build();
        app(EntityStore::class)->get('Screen')->export($id);

        return app(AssetStore::class);
    }
}
