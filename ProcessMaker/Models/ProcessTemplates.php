<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use ProcessMaker\Models\ProcessMakerModel;
use ProcessMaker\Models\Template;

class ProcessTemplates extends Template
{
    use HasFactory;

    protected $table = 'process_templates';
}
