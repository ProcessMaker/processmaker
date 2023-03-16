<?php

namespace ProcessMaker\Models;

use Illuminate\Validation\Rule;
use ProcessMaker\Models\Template;
use ProcessMaker\Models\TemplateCategory;
use ProcessMaker\Traits\Exportable;
use ProcessMaker\Traits\HideSystemResources;
use ProcessMaker\Traits\SerializeToIso8601;

class ProcessTemplateCategory extends TemplateCategory
{
    protected $table = 'process_template_categories';
}
