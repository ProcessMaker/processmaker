<?php

namespace ProcessMaker\Models;

use Illuminate\Validation\Rule;
use ProcessMaker\Models\Template;
use ProcessMaker\Models\TemplateCategory;
use ProcessMaker\Traits\Exportable;
use ProcessMaker\Traits\HideSystemResources;
use ProcessMaker\Traits\SerializeToIso8601;

/**
 * Represents a business screen category definition.
 *
 * @property string $id
 * @property string $name
 * @property string $status
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $created_at
 *
 * @OA\Schema(
 *   schema="ScreenCategoryEditable",
 *   @OA\Property(property="name", type="string"),
 *   @OA\Property(property="status", type="string", enum={"ACTIVE", "INACTIVE"}),
 * ),
 * @OA\Schema(
 *   schema="ScreenCategory",
 *   allOf={
 *      @OA\Schema(ref="#/components/schemas/ScreenCategoryEditable"),
 *      @OA\Schema(
 *          @OA\Property(property="id", type="string", format="id"),
 *          @OA\Property(property="created_at", type="string", format="date-time"),
 *          @OA\Property(property="updated_at", type="string", format="date-time"),
 *      )
 *   },
 * )
 */
class ProcessTemplateCategory extends TemplateCategory
{
    protected $table = 'process_template_categories';
}
