<?php

namespace ProcessMaker\Package\Translations\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Used only when package-translations is not installed. ExporterBase::getExtraAttributes
 * selects code and name from this model and does not modify asset attributes or references.
 */
class Language extends Model
{
    protected $connection = 'processmaker';

    protected $table = 'languages';

    protected $guarded = [];

    public $timestamps = false;
}
