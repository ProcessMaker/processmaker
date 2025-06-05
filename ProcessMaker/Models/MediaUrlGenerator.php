<?php

namespace ProcessMaker\Models;

use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\UrlGenerator\DefaultUrlGenerator;

class MediaUrlGenerator extends DefaultUrlGenerator
{
    public function getUrl(): string
    {
        $url = parent::getUrl();

        // Get the current tenant ID
        $tenant = app('currentTenant');
        if ($tenant) {
            // Add tenant ID to the URL path
            $url = str_replace('/storage/', '/storage/tenant_' . $tenant->id . '/', $url);
        }

        return $url;
    }
}
