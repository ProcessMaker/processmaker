<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 9/20/18
 * Time: 10:33 AM
 */

namespace ProcessMaker\Models;

use Spatie\MediaLibrary\Models\Media;
use Spatie\MediaLibrary\PathGenerator\PathGenerator;

class MediaPathGenerator implements PathGenerator
{

    public function getPath(Media $media): string
    {
        return ($media->id) . '/';
    }

    public function getPathForConversions(Media $media): string
    {
        return $this->getPath($media).'c/';
    }

    public function getPathForResponsiveImages(Media $media): string
    {
        return $this->getPath($media).'/cri/';
    }
}
