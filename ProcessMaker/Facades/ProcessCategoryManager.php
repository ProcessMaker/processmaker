<?php

namespace ProcessMaker\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Facade for the Process File Manager
 *
 * @package ProcessMaker\Facades
 * @see \ProcessMaker\Managers\ProcessCategoryManager
 *
 * @method \Illuminate\Database\Eloquent\Collection index($filter, $start, $limit)
 * @method \ProcessMaker\Model\ProcessCategory store($data)
 * @method \ProcessMaker\Model\ProcessCategory update(\ProcessMaker\Model\ProcessCategory $processCategory, $data)
 * @method bool remove(\ProcessMaker\Model\ProcessCategory $processCategory)
 * @method array format(\ProcessMaker\Model\ProcessCategory $processCategory)
 * @method array formatList(\Illuminate\Database\Eloquent\Collection $processCategories)
 */
class ProcessCategoryManager extends Facade
{

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'process_category.manager';
    }
}
