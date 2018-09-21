<?php

namespace ProcessMaker;

use Spatie\BinaryUuid\HasBinaryUuid;
use Spatie\MediaLibrary\Models\Media as BaseMedia;

class Media extends BaseMedia
{
    use HasBinaryUuid;
}