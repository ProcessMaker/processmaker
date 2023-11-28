<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use ProcessMaker\Traits\HasUuids;

class WizardTemplate extends ProcessMakerModel
{
    use HasFactory;
    use HasUuids;

    protected $table = 'wizard_templates';

    protected $fillable = [
        'uuid',
        'process_template_id',
        'process_id',
        'media_collection',
    ];
}
