<?php
namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Http\Request;
use Spatie\BinaryUuid\HasBinaryUuid;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\Collection;

/**
 * Implements methods to get common parameters in a resource request
 *
 */
trait ResourceRequestsTrait
{

    /**
     * Get the where array to filter the resources.
     *
     * @param \ProcessMaker\Http\Controllers\Api\Request $request
     * @param array $searchableColumns
     *
     * @return array
     */
    protected function getRequestFilterBy(Request $request, array $searchableColumns)
    {
        $where = [];
        $filter = $request->input('filter');
        if ($filter) {
            foreach ($searchableColumns as $column) {
                $where[] = [$column, 'like', $filter, 'or'];
            }
        }
        return $where;
    }

    /**
     * Get included relationships.
     *
     * @param Request $request
     *
     * @return array
     */
    protected function getRequestSortBy(Request $request, $default)
    {
        $column = $request->input('order_by', $default);
        $direction = $request->input('order_direction', 'asc');
        return [$column, $direction];
    }

    /**
     * Get included relationships.
     *
     * @param Request $request
     *
     * @return array
     */
    protected function getRequestInclude(Request $request)
    {
        $include = $request->input('include');
        return $include ? explode(',', $include) : [];
    }

    /**
     * Change uuid text to uuid binary
     *
     * @param Request $request
     * @param array $fields
     */
    protected function encodeRequestUuids(Request $request, array $fields = [])
    {
        foreach ($fields as $field) {
            $value = $request->input($field);
            if ($value) {
                $request->merge([$field => HasBinaryUuid::encodeUuid($value)]);
            }
        }
    }

    /**
     * Get the size of the page.
     * per_page=# (integer, the page requested) (Default: 10)
     *
     * @param Request $request
     * @return type
     */
    protected function getPerPage(Request $request)
    {
        return $request->input('per_page', 10);
    }
    
    protected function validateModel($model, $rules=[], $messages=[], $customAttributes=[])
    {
        $data = [];
        foreach ($rules as $key => $rule) {
            $data[$key] = $model->$key;
        }
        /* @var $validator \Illuminate\Validation\Validator */
        $validator = Validator::make($data, $rules, $messages, $customAttributes);
        /**
         * Validate if the path points to a valid drive.
         */
        $validator->addExtension(
            'empty',
            function ($attribute, $value) {
                return $value instanceof Collection ? $value->count() === 0 : empty($value);
            }
        );

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }
}
