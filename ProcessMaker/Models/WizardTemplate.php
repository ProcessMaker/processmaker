<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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

    /**
     * Get the process associated with the wizard template.
     */
    public function process(): BelongsTo
    {
        return $this->belongsTo(Process::class, 'process_id');
    }

    /**
     * Get the process template associated with the wizard template.
     */
    public function process_template(): BelongsTo
    {
        return $this->belongsTo(ProcessTemplates::class, 'process_template_id');
    }
}
