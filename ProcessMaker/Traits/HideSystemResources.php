<?php

namespace ProcessMaker\Traits;
use Illuminate\Support\Str;
use ProcessMaker\Models\ProcessRequest;

trait HideSystemResources
{
    private $_isSystemResource = null;

    public function resolveRouteBinding($value)
    {
        $item = parent::resolveRouteBinding($value);

        if (!$item || $item->isSystemResource()) {
            abort(404);
        }

        return $item;
    }

    public function isSystemResource()
    {
        if (isset($this->_isSystemResource)) {
            return $this->_isSystemResource;
        }
        $this->_isSystemResource = $this->isSystemResourceReal();
        return $this->_isSystemResource;
    }

    private function isSystemResourceReal()
    {
        if (method_exists($this, 'process')) {
            if (
                $this->process()->first()->category()->count() > 0 &&
                $this->process()->first()->category()->first()->is_system
            ) {
                return true;
            }
            return false;

        } elseif (substr(self::class, -8) === 'Category') {
            return $this->is_system;

        } else {
            if (
                $this->category()->count() > 0 &&
                $this->category()->first()->is_system
            ) {
                return true;
            }
            return false;
        }
    }

    public function scopeNonSystem($query)
    {
        // Note that ProcessRequests can not be filtered with
        // scopes because they live on a different database server

        if (method_exists($this, 'process')) { // like ProcessRequestToken
            return $query->whereHas('process', function($processQuery) {
                $processQuery->nonSystem();
            });
        } else if (substr(self::class, -8) === 'Category') {
            return $query->where('is_system', false);
        } else {
            return $query->whereDoesntHave('categories', function ($query) {
                $query->where('is_system', true);
            });
        }
    }
}
