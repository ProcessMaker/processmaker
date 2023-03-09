<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use ProcessMaker\Models\ProcessMakerModel;
use ProcessMaker\Models\TemplateCategory;
use ProcessMaker\Traits\HasUuids;

class Template extends ProcessMakerModel
{
    use HasFactory;
    use HasUuids;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [
        'id',
        'uuid',
        'created_at',
        'updated_at',
    ];

    /**
     * Table columns.
     *
     * @var array
     */
    protected $columns = [
        'id',
        'template_category_id',
        'name',
        'description',
        'user_id',
        'manifest',
        'svg',
        'is_system',
        'created_at',
        'updated_at',
    ];

    /**
     * Category of the template.
     *
     * @return BelongsTo
     */
    public function category()
    {
        return $this->belongsTo(TemplateCategory::class, 'template_category_id')->withDefault();
    }

    /**
     * Get the creator/author of this template.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Validation rules.
     *
     * @param null $existing
     *
     * @return array
     */
    public static function rules($existing = null)
    {
        $unique = Rule::unique('templates')->ignore($existing);

        return [
            'name' => ['required', $unique, 'alpha_spaces'],
            'description' => 'required',
            'template_category_id' => 'exists:template_categories,id',
            'process_id' => 'required',
            'manifest' => 'required',
            'svg' => 'nullable',
        ];
    }
}
