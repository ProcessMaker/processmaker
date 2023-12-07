<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\File;
use ProcessMaker\Traits\HasUuids;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class WizardTemplate extends ProcessMakerModel implements HasMedia
{
    use HasFactory;
    use HasUuids;
    use InteractsWithMedia;

    protected $table = 'wizard_templates';

    protected $fillable = [
        'uuid',
        'process_template_id',
        'helper_process_id',
    ];

    /**
     * Get the process associated with the wizard template.
     */
    public function process(): BelongsTo
    {
        return $this->belongsTo(Process::class, 'helper_process_id');
    }

    /**
     * Get the process template associated with the wizard template.
     */
    public function process_template(): BelongsTo
    {
        return $this->belongsTo(ProcessTemplates::class, 'process_template_id');
    }

    /**
     * Add files to media collection
     */
    public function addFilesToMediaCollection(string $directoryPath)
    {
        $files = File::allFiles($directoryPath);
        $collectionName = basename($directoryPath);

        foreach ($files as $file) {
            $this->addMedia($file->getPathname())->toMediaCollection($collectionName);
        }
    }
}
