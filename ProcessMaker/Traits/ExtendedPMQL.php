<?php

namespace ProcessMaker\Traits;

use Illuminate\Database\Eloquent\Builder;
use ProcessMaker\Models\User;
use ProcessMaker\Query\Expression;
use ProcessMaker\Query\Traits\PMQL;

trait ExtendedPMQL
{
    use PMQL {
        PMQL::scopePMQL as parentScopePMQL;
    }

    /**
     * PMQL scope that extends the standard PMQL scope by supporting any custom
     * aliases specified in the model.
     *
     * @param \Illuminate\Database\Eloquent\Builder $builder
     * @param string $query
     * @param callable $callback
     *
     * @return mixed
     */    
    public function scopePMQL(Builder $builder, string $query, callable $callback = null, User $user = null)
    {
        if (! $callback) {
            // If a callback isn't passed to the scope, we handle it here
            return $this->parentScopePMQL($builder, $query, function($expression) use ($builder, $user) {
                return $this->handle($expression, $builder, $user);
            });
        } else {
            // If a callback is passed to the scope, we skip handling it here
            return $this->parentScopePMQL($builder, $query, $callback);
        }
    }

    /**
     * Callback function to check for and handle any field aliases, value
     * aliases, or field wildcards specified in the given model.
     *
     * @param \ProcessMaker\Query\Expression $expression
     * @param \Illuminate\Database\Eloquent\Builder $builder
     *
     * @return mixed
     */    
    private function handle(Expression $expression, Builder $builder, User $user = null)
    {
        // Setup our needed variables
        $field = $expression->field->field();
        $model = $builder->getModel();
        
        if (is_string($field)) {
            // Check the type of our value; set as string if possible
            if (is_a($expression->value, 'ProcessMaker\\Query\\LiteralValue')) {
                $value = $expression->value->value();
            } else {
                $value = $expression->value;
            }
            
            // Title case our field name so we can suffix it to our method names
            $fieldMethodName = ucfirst(strtolower($field));

            // A field alias specifies that a field name used in a PMQL query
            // translates to a different field name in our database.
            $method = "fieldAlias{$fieldMethodName}";
            if (method_exists($model, $method)) {
                return $expression->field->setField($model->{$method}());
            }

            // A value alias specifies that a value must be parsed by a callback
            // function if its field name matches a specific word.
            $method = "valueAlias{$fieldMethodName}";
            if (method_exists($model, $method)) {
                return $model->{$method}($value, $expression, $builder, $user);
            }

            // A field wildcard passes any fields not caught by a field or value
            // alias to a callback function for any needed processing. If the
            // callback returns void, the PMQL is parsed as if there is
            // no callback.
            $method = "fieldWildcard";
            if (method_exists($model, $method)) {
                return $model->{$method}($value, $expression, $builder, $user);
            }    
        }
    }
}
