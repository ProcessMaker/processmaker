<?php

namespace ProcessMaker\Traits;

use Facades\ProcessMaker\Helpers\CachedSchema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessCategory;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\ProcessTemplates;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\ScreenCategory;
use ProcessMaker\Models\ScreenTemplates;
use ProcessMaker\Models\Script;
use ProcessMaker\Models\ScriptCategory;
use ProcessMaker\Models\ScriptExecutor;
use ProcessMaker\Models\User;
use ProcessMaker\Packages\Connectors\DataSources\Models\DataSource;
use ProcessMaker\Packages\Connectors\DataSources\Models\DataSourceCategory;

trait HideSystemResources
{
    public function resolveRouteBinding($value, $field = null)
    {
        $item = parent::resolveRouteBinding($value);

        if (request() && request()->is('api/*')) {
            // Allow the API to directly access hidden resources by ID
            return $item;
        }

        if (!$item) {
            abort(404);
        }

        $prefix = strtolower(substr(strrchr(get_class($item), '\\'), 1));
        $attribute = "{$prefix}_category_id";
        if ($item->$attribute && $item->category()->first()->is_system) {
            abort(404);
        } elseif ($item->is_system) {
            abort(404);
        }

        return $item;
    }

    public function scopeSystem($query)
    {
        if (substr(static::class, -8) === 'Category') {
            return $query->where('is_system', true);
        } else {
            return $query->whereHas('categories', function ($query) {
                $query->where('is_system', true);
            });
        }
    }

    public function scopeNonSystem($query, $optimized = false)
    {
        return $this->applyNonSystemScope($query, $optimized);
    }

    private function applyNonSystemScope($query, $optimized = false)
    {
        if (substr(static::class, -8) === 'Category') {
            return $query->where('is_system', false);
        }

        $templatedModels = [
            Process::class => 'processes',
            Screen::class => 'screens',
            Script::class => 'scripts',
            DataSource::class => 'data_sources',
        ];
        if (isset($templatedModels[static::class])) {
            return $this->scopeNonSystemTemplatedAsset($query, $templatedModels[static::class]);
        }

        $excludeSystemCategories = fn ($categoryQuery) => $categoryQuery->where('is_system', true);

        return match (static::class) {
            ProcessRequest::class => $query->whereNotIn(
                'process_id',
                Process::withTrashed()->system()->pluck('id')
            ),
            User::class, ScriptExecutor::class => $query->where('is_system', false),
            ProcessRequestToken::class => $this->scopeNonSystemProcessRequestToken($query, $optimized),
            ProcessTemplates::class => $query->where('process_templates.is_system', false)
                ->when(CachedSchema::hasColumn('process_templates', 'asset_type'), function ($query) {
                    return $query->whereNull('asset_type');
                }),
            'ProcessMaker\Plugins\Collections\Models\Collection' => $query->whereNull('collections.asset_type'),
            ScreenTemplates::class => $query->where('screen_templates.is_system', false)
                ->when(CachedSchema::hasColumn('screen_templates', 'asset_type'), function ($query) {
                    return $query->whereNull('asset_type');
                }),
            default => $query->whereDoesntHave('categories', $excludeSystemCategories),
        };
    }

    private function scopeNonSystemTemplatedAsset($query, string $tableName)
    {
        return $query
            ->where('is_template', false)
            ->when(CachedSchema::hasColumn($tableName, 'asset_type'), function ($query) {
                return $query->whereNull('asset_type');
            })
            ->whereDoesntHave('categories', function ($query) {
                $query->where('is_system', true);
            });
    }

    private function scopeNonSystemProcessRequestToken($query, bool $optimized)
    {
        // Direct EXISTS avoids Process global scopes (e.g. published/process_versions).
        if (!$optimized) {
            return $query->whereHas('process.categories', function ($query) {
                $query->where('is_system', false);
            });
        }

        return $query->whereExists(function ($sub) {
            $sub->select(DB::raw(1))
                ->from('processes')
                ->join('category_assignments', function ($join) {
                    $join->on('category_assignments.assignable_id', '=', 'processes.id')
                        ->where('category_assignments.assignable_type', '=', Process::class);
                })
                ->join('process_categories', 'process_categories.id', '=', 'category_assignments.category_id')
                ->whereColumn('processes.id', 'process_request_tokens.process_id')
                ->whereNull('processes.deleted_at')
                ->where('process_categories.is_system', false);
        });
    }
}
