<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use ProcessMaker\Exception\PmqlMethodException;
use ProcessMaker\Traits\ExtendedPMQL;
use ProcessMaker\Traits\HasCategories;
use ProcessMaker\Traits\HideSystemResources;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class ScreenTemplates extends Template implements HasMedia
{
    use HasFactory;
    use HasCategories;
    use HideSystemResources;
    use ExtendedPMQL;
    use InteractsWithMedia;

    protected $table = 'screen_templates';

    protected $appends = [
        'template_media',
    ];

    const categoryClass = ScreenCategory::class;

    public $screen_category_id;

    /**
     * Category of the screen template
     *
     * @return BelongsTo
     */
    public function category()
    {
        return $this->belongsTo(ScreenCategory::class, 'screen_category_id')->withDefault();
    }

    /**
     * Set multiple|single categories to the screen template
     *
     * @param string $value
     */
    public function setScreenCategoryIdAttribute($value)
    {
        return $this->setMultipleCategories($value, 'screen_category_id');
    }

    /**
     * Get multiple|single categories of the screen template
     *
     * @param string $value
     */
    public function getScreenCategoryIdAttribute($value)
    {
        return implode(',', $this->categories()->pluck('category_id')->toArray()) ?: $value;
    }

    /**
     * PMQL value alias for fulltext field
     *
     * @param string $value
     *
     * @return callable
     */
    public function valueAliasFullText($value, $expression)
    {
        return function ($query) use ($value) {
            $this->scopeFilter($query, $value);
        };
    }

    /**
     * PMQL value alias for screen template field
     *
     * @param string $value
     *
     * @return callable
     */
    public function valueAliasName($value, $expression)
    {
        return function ($query) use ($value, $expression) {
            $templates = self::where('name', $expression->operator, $value)->pluck('id');
            $query->whereIn('screen_templates.id', $templates);
        };
    }

    /**
     * PMQL value alias for screen templates field
     *
     * @param string $value
     *
     * @return callable
     */
    public function valueAliasScreen($value, $expression)
    {
        return function ($query) use ($value, $expression) {
            $templates = self::where('name', $expression->operator, $value)->pluck('id');
            $query->whereIn('screen_templates.id', $templates);
        };
    }

    /**
     * PMQL value alias for id field
     *
     * @param string $value
     *
     * @return callable
     */
    public function valueAliasId($value, $expression)
    {
        return function ($query) use ($value, $expression) {
            $templates = self::where('id', $expression->operator, $value)->pluck('id');
            $query->whereIn('screen_templates.id', $templates);
        };
    }

    /**
     * PMQL value alias for category field
     *
     * @param string $value
     *
     * @return callable
     */
    public function valueAliasCategory($value, $expression)
    {
        return function ($query) use ($value, $expression) {
            $categoryAssignment = DB::table('category_assignments')->leftJoin('screen_categories', function ($join) {
                $join->on('screen_categories.id', '=', 'category_assignments.category_id');
                $join->where('category_assignments.category_type', '=', ScreenCategory::class);
                $join->where('category_assignments.assignable_type', '=', self::class);
            })
                ->where('name', $expression->operator, $value);
            $query->whereIn('screen_templates.id', $categoryAssignment->pluck('assignable_id'));
        };
    }

    /**
     * PMQL value alias for owner field
     *
     * @param string $value
     *
     * @return callable
     */
    private function valueAliasOwner($value, $expression)
    {
        $user = User::where('username', $value)->get()->first();

        if ($user) {
            return function ($query) use ($user, $expression) {
                $query->where('screen_templates.user_id', $expression->operator, $user->id);
            };
        } else {
            throw new PmqlMethodException('owner', 'The specified owner username does not exist.');
        }
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
            $query->where('screen_templates.name', 'like', $filter)
                 ->orWhere('screen_templates.description', 'like', $filter)
                 ->orWhereHas('user', function ($query) use ($filter) {
                     $query->where('firstname', 'like', $filter)
                         ->orWhere('lastname', 'like', $filter);
                 })
                 ->orWhereIn('screen_templates.id', function ($qry) use ($filter) {
                     $qry->select('assignable_id')
                         ->from('category_assignments')
                         ->leftJoin('screen_categories', function ($join) {
                             $join->on('screen_categories.id', '=', 'category_assignments.category_id');
                             $join->where('category_assignments.category_type', '=', ScreenCategory::class);
                             $join->where('category_assignments.assignable_type', '=', self::class);
                         })
                         ->where('screen_categories.name', 'like', $filter);
                 });
        });

        return $query;
    }

    /**
     * Get the associated thumbnails for the given screen template
     */
    public function getTemplateMediaAttribute()
    {
        $mediaCollectionName = 'st-' . $this->uuid . '-media';

        // Get preview thumbs
        $previewThumbs = $this->getMedia($mediaCollectionName, ['media_type' => 'preview-thumbs']);

        // Get thumbnail media
        $thumbnailMedia = $this->getMedia($mediaCollectionName, ['media_type' => 'thumbnail'])->first();

        // If thumbnail media is not found and no preview thumbs are available,
        // get any media associated with the template
        if (is_null($thumbnailMedia) && $previewThumbs->isEmpty()) {
            $allMedia = $this->getMedia($mediaCollectionName);

            return $allMedia->map(function ($media) {
                $media->url = $media->getFullUrl();

                return $media;
            })->all();
        } else {
            // Prepare URLs for thumbnail and preview thumbs
            if ($thumbnailMedia) {
                $thumbnailMedia->url = $thumbnailMedia->getFullUrl();
            }
            $previewThumbs = $previewThumbs->map(function ($thumb) {
                $thumb->url = $thumb->getFullUrl();

                return $thumb;
            })->all();

            return [
                'thumbnail' => $thumbnailMedia ? $thumbnailMedia : '',
                'previewThumbs' => $previewThumbs,
            ];
        }
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

    /**
     * Get the value of the "isOwner" attribute.
     */
    public function getIsOwnerAttribute(): bool
    {
        return $this->isOwner(auth()->user());
    }

    /**
     * Checks if the given user is the owner of the screen template.
     */
    public function isOwner(User $user): bool
    {
        return $user->is_administrator || $this->user_id === $user->id;
    }

    /**
     * Listen for the deleting event on a ScreenTemplate
     * instance and delete any associated Screen if exists
     */
    protected static function boot()
    {
        parent::boot();
        static::deleting(function ($screenTemplate) {
            Screen::where('uuid', $screenTemplate->editing_screen_uuid)->delete();
        });
    }
}
