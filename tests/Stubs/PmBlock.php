<?php

namespace ProcessMaker\Package\PackagePmBlocks\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Test stand-in for the optional PM Blocks model.
 *
 * ProcessPolicy::edit denies edits when this class exists and the related
 * row has is_imported_locked set. The package is not part of this
 * application's required dependencies, so the authorization tests load this
 * model only when the real one is absent.
 */
class PmBlock extends Model
{
    protected $connection = 'processmaker';

    protected $table = 'pm_blocks';

    protected $guarded = [];

    public $timestamps = false;

    protected $casts = [
        'is_imported_locked' => 'boolean',
    ];
}
