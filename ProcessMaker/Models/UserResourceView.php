<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Collection;
use ProcessMaker\Models\ProcessMakerModel;

class UserResourceView extends ProcessMakerModel
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'viewable_id',
        'viewable_type',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function setViewed($user, $viewable)
    {
        return self::firstOrCreate([
            'user_id' => $user->id,
            'viewable_type' => get_class($viewable),
            'viewable_id' => $viewable->id,
        ]);
    }

    public static function addToResourceCollection(Collection &$collection, User|null $user)
    {
        if (!$user) {
            return $collection;
        }

        if ($collection->count() === 0) {
            return $collection;
        }

        $class = null;
        $firstItem = $collection->first();
        if (property_exists($firstItem, 'resource')) {
            $firstResource = $firstItem->resource;
            if ($firstResource instanceof ProcessRequest) {
                $class = ProcessRequest::class;
            } elseif ($firstResource instanceof ProcessRequestToken) {
                $class = ProcessRequestToken::class;
            }
        }

        if (!$class) {
            return $collection;
        }

        $result = self::where('user_id', $user->id)
            ->whereIn('viewable_id', $collection->pluck('id'))
            ->where('viewable_type', $class)
            ->pluck('created_at', 'viewable_id');

        // Use transform instead of map to (maybe) save memory
        $collection->transform(function ($item) use ($result) {
            $userViewedAt = $result->get($item->id);
            $item['user_viewed_at'] = $userViewedAt ? (string) $userViewedAt : null;

            return $item;
        });
    }
}
