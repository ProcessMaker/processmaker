<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use ProcessMaker\Models\ProcessMakerModel;

class InboxRuleLog extends ProcessMakerModel
{
    use HasFactory;

    protected $table = 'inbox_rule_logs';
}
