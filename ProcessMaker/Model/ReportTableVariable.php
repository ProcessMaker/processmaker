<?php
namespace ProcessMaker\Model;

use Illuminate\Database\Eloquent\Model;

class ReportTableVariable extends Model
{
    public $timestamps = false;
    public $incrementing = false;

    protected $table = 'FIELDS';
}
