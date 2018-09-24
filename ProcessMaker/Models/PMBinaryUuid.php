<?php
namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Spatie\BinaryUuid\HasBinaryUuid;

trait PMBinaryUuid
{
    use HasBinaryUuid;
    
    // protected static function find($uuid)
    // {
    //     $result = self::withUuid($uuid)->first();
    //     if (!$result) {
    //         throw new ModelNotFoundException;
    //     }
    //     return $result;
    // }
}
