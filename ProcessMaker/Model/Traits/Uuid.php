<?php
namespace ProcessMaker\Model\Traits;

use Ramsey\Uuid\Uuid as UuidGenerator;

trait Uuid
{

    /**
     * Assign our creating event to ensure we have a uuid if not already set manually
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if(!$model->uid) {
                $model->uid = UuidGenerator::uuid4();
            }
        });
    }

    /*
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'uid';
    }

}