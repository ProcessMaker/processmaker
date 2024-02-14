<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use ProcessMaker\Models\ProcessMakerModel;

class InboxRule extends ProcessMakerModel
{
    use HasFactory;

    protected $table = 'inbox_rules';
}
