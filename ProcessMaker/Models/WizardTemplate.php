<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\File;
use ProcessMaker\Models\Media;
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
        'name',
        'description',
        'media_collection',
        'template_details',
        'unique_template_id',
    ];

    protected $appends = [
        'template_media',
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
     * Filter settings with a string
     *
     * @param $query
     *
     * @param $filter string
     */
    public function scopeFilter($query, $filterStr)
    {
        $filter = '%' . mb_strtolower($filterStr) . '%';
        $query->where(function ($query) use ($filter) {
            $query->where('wizard_templates.name', 'like', $filter)
                ->orWhere('wizard_templates.description', 'like', $filter);
        });

        return $query;
    }

    public function getTemplateMediaAttribute()
    {
        $mediaCollectionName = 'wt-' . $this->uuid . '-media';
        $slides = $this->getMedia($mediaCollectionName, ['media_type' => 'slide']);
        $slideUrls = $slides->map(function ($slide) {
            return $slide->getFullUrl();
        });
        $iconMedia = $this->getMedia($mediaCollectionName, ['media_type' => 'icon'])->first();
        $cardBackgroundMedia = $this->getMedia($mediaCollectionName, ['media_type' => 'cardBackground'])->first();
        $listIconMedia = $this->getMedia($mediaCollectionName, ['media_type' => 'listIcon'])->first();

        return [
            'icon' => !is_null($iconMedia) ? $iconMedia->getFullUrl() : '',
            'cardBackground' => !is_null($cardBackgroundMedia) ? $cardBackgroundMedia->getFullUrl() : '',
            'listIcon' => !is_null($listIconMedia) ? $listIconMedia->getFullUrl() : '',
            'slides' => $slideUrls,
        ];
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
