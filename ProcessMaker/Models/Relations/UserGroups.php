<?php

namespace ProcessMaker\Models\Relations;

use Illuminate\Database\Eloquent\Relations\MorphToMany;
use ProcessMaker\Models\User;

class UserGroups extends MorphToMany
{
    public function attach($id, array $attributes = [], $touch = true)
    {
        $result = parent::attach($id, $attributes, $touch);
        $this->flushSelfServiceGroupCache();

        return $result;
    }

    public function detach($ids = null, $touch = true, $pivotAttributes = [])
    {
        $result = parent::detach($ids, $touch, $pivotAttributes);
        $this->flushSelfServiceGroupCache();

        return $result;
    }

    public function sync($ids, $detaching = true)
    {
        $result = parent::sync($ids, $detaching);
        $this->flushSelfServiceGroupCache();

        return $result;
    }

    public function syncWithoutDetaching($ids)
    {
        $result = parent::syncWithoutDetaching($ids);
        $this->flushSelfServiceGroupCache();

        return $result;
    }

    public function toggle($ids, $touch = true)
    {
        $result = parent::toggle($ids, $touch);
        $this->flushSelfServiceGroupCache();

        return $result;
    }

    private function flushSelfServiceGroupCache(): void
    {
        if ($this->parent instanceof User && $this->parent->getKey() !== null) {
            User::flushSelfServiceGroupIdsCache((int) $this->parent->getKey());
        }
    }
}
