<?php

namespace ProcessMaker\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class NotificationWithValidJson implements Scope
{
    // Conditions added with apply are inserted as the last condition of a query.
    // We need to filter valid json data as the first condition of the query, so that the rest of where
    // clauses, if they contain a json_extract and the data has a non json string,
    // will not be evaluated and generate errors.
    // For the reasons above we'll left apply empty just as a placeholder for the method defined in Scope
    public function apply(Builder $builder, Model $model)
    {
    }

    // This is an undocumented function, it adds the where condition at the start of the query
    public function extend(Builder $builder)
    {
        $builder->whereRaw('json_valid(data)');
    }
}
