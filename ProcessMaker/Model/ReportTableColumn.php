<?php
namespace ProcessMaker\Model;

use Illuminate\Database\Eloquent\Model;
use ProcessMaker\Model\Traits\Uuid;

class ReportTableColumn extends Model
{
    use Uuid;
    public $timestamps = false;

}
