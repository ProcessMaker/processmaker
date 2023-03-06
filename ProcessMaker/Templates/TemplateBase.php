<?php

namespace ProcessMaker\Templates;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use ProcessMaker\Exception\ExportModelNotFoundException;
use ProcessMaker\Models\User;

abstract class TemplateBase implements TemplateInterface
{
    // public $model = null;

    public static function modelFinder($uuid, $assetInfo)
    {
        dd('MODEL FINDER');
        // $class = $assetInfo['model'];
        // $column = 'uuid';
        // $matchedBy = null;
        // $baseQuery = $class::query();

        // // Check if the model has soft deletes
        // if (in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses($class))) {
        //     $baseQuery->withTrashed();
        // }

        // $query = clone $baseQuery;
        // $model = $query->where($column, $uuid)->first();

        // if (!$model) {
        //     foreach ((array) static::$fallbackMatchColumn as $column) {
        //         $value = Arr::get($assetInfo, 'attributes.' . $column);
        //         if (!$value) {
        //             continue;
        //         }
        //         $query = clone $baseQuery;
        //         $model = $query->where($column, $value)->first();
        //         if ($model) {
        //             $matchedBy = $column;
        //             break;
        //         }
        //     }
        // } else {
        //     $matchedBy = $column;
        // }

        // return [$model, $matchedBy];
    }

    public function __construct(public Model|Psudomodel|null $model)
    {
        dd('CONSTRUCT TEMPLATE BASE');
        // $this->mode = $options->get('mode', $this->model->uuid);
    }

    public function save() : string
    {
        dd('SAVE TEMPLATE');
    }
}
