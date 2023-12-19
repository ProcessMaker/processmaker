<?php

namespace ProcessMaker\Traits;

trait TracksUserViewed
{
    public function scopeWithUserViewed($query, $user_id)
    {
        $class = get_class($this);
        $table = $this->getTable();

        $query->selectSub(function ($q) use ($user_id, $class, $table) {
            $q->select('created_at')
                ->from('user_resource_views')
                ->whereRaw("viewable_id = {$table}.id")
                ->where('user_id', $user_id)
                ->where('viewable_type', $class)
                ->limit(1);
        }, 'user_viewed_at');
    }
}
