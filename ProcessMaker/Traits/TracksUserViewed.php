<?php

namespace ProcessMaker\Traits;

trait TracksUserViewed
{
    public function scopeWithUserViewed($query, $user_id)
    {
        $query->addSelect('user_resource_views.created_at as user_viewed_at')
            ->leftJoin('user_resource_views', function ($join) use ($user_id) {
                $table = $this->getTable();
                $join->on('user_resource_views.viewable_id', '=', $table . '.id')
                    ->where('user_resource_views.user_id', $user_id)
                    ->where('user_resource_views.viewable_type', '=', get_class($this));
            });
    }
}
