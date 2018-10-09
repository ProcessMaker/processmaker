<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\BinaryUuid\HasBinaryUuid;

class Permission extends Model
{
    use HasBinaryUuid;
}
