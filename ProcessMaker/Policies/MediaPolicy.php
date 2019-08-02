<?php

namespace ProcessMaker\Policies;

use ProcessMaker\Models\User;
use ProcessMaker\Models\Media;
use Illuminate\Auth\Access\HandlesAuthorization;

class MediaPolicy
{
    use HandlesAuthorization;

    /**
     * Run before all methods to determine if the
     * user is an admin and can do everything.
     *
     * @param  \ProcessMaker\Models\User  $user
     * @return mixed
     */    
    public function before(User $user)
    {
        if ($user->is_administrator) {
            return true;
        }        
    }

    /**
     * Determine whether the user can view the media.
     *
     * @param  \ProcessMaker\Models\User  $user
     * @param  \ProcessMaker\Models\Media  $media
     * @return mixed
     */
    public function view(User $user, Media $media)
    {
        if ($user->hasPermission('view-files')) {
            return true;
        }

        return $user->can('view', $media->model);
    }

    /**
     * Determine whether the user can create media.
     *
     * @param  \ProcessMaker\Models\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        if ($user->hasPermission('create-files')) {
            return true;
        }
        
        $request = request();
        
        $class = $request->input('model');
        $modelId = $request->input('model_id');
        
        if ($class && $modelId && class_exists($class)) {
            $model = new $class;
            $model = $model->find($modelId);
            
            if ($model) {
                if ($user->can('create', $model)) {
                    return true;
                }
                
                if ($user->can('update', $model)) {
                    return true;
                }
            }
        }
        
        return false;
    }

    /**
     * Determine whether the user can update the media.
     *
     * @param  \ProcessMaker\Models\User  $user
     * @param  \ProcessMaker\Models\Media  $media
     * @return mixed
     */
    public function update(User $user, Media $media)
    {
        if ($user->hasPermission('update-files')) {
            return true;
        }
        
        return $user->can('update', $media->model);
    }

    /**
     * Determine whether the user can delete the media.
     *
     * @param  \ProcessMaker\Models\User  $user
     * @param  \ProcessMaker\Models\Media  $media
     * @return mixed
     */
    public function delete(User $user, Media $media)
    {
        if ($user->hasPermission('delete-files')) {
            return true;
        }
        
        return $user->can('update', $media->model);
    }
}
