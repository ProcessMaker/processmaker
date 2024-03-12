<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use ProcessMaker\Exception\PmqlMethodException;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\ScreenCategory;
use ProcessMaker\Models\Template;
use ProcessMaker\Traits\ExtendedPMQL;
use ProcessMaker\Traits\HasCategories;
use ProcessMaker\Traits\HideSystemResources;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ScreenTemplates extends Template implements HasMedia
{
    use HasFactory;
    use HasCategories;
    use HideSystemResources;
    use ExtendedPMQL;
    use InteractsWithMedia;

    protected $table = 'screen_templates';

    protected $appends = [
        'thumbnails',
        // 'css',
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

    public function getTemplateMediaAttribute()
    {
        $mediaCollectionName = 'st-' . $this->uuid . '-media';
        $previewThumbs = $this->getMedia($mediaCollectionName, ['media_type' => 'preview-thumbs']);
        $previewThumbsUrls = $previewThumbs->map(function ($slide) {
            return $slide->getFullUrl();
        });
        $thumbnailMedia = $this->getMedia($mediaCollectionName, ['media_type' => 'thumbnail'])->first();

        return [
            'thumbnail' => !is_null($thumbnailMedia) ? $thumbnailMedia->getFullUrl() : '',
            'previewThumbs' => $previewThumbsUrls,
        ];
    }

    /**
     * Get the associated thumbnails for the given screen template
     */
    public function getThumbnailsAttribute()
    {
        $mediaCollectionName = 'st-' . $this->uuid . '-media';
        $previewThumbs = $this->getMedia($mediaCollectionName);

        return $previewThumbs->map(function ($thumb) {
            return $thumb->getFullUrl();
        });
    }

    /**
     * Get the associated css for the given screen template
     */
    // public function getCssAttribute()
    // {
    //     dd('GET CSS', $this);
    //     // if (!class_exists('ProcessMaker\Package\Projects\Models\Project')) {
    //     //     // return an empty collection
    //     //     return new HasMany($this->newQuery(), $this, '', '');
    //     // }

    //     // return $this->belongsToMany('ProcessMaker\Package\Projects\Models\Project',
    //     //     'project_assets',
    //     //     'asset_id',
    //     //     'project_id',
    //     //     'id',
    //     //     'id'
    //     // )->wherePivot('asset_type', static::class);
    // }

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
