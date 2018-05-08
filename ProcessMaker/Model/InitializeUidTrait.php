<?php

namespace ProcessMaker\Model;

use Ramsey\Uuid\Uuid;

/**
 * Initialize a UID field for a model.
 *
 */
trait InitializeUidTrait
{

    /**
     * Initialize a UID for the route key.
     *
     * @param  string  $key
     * @return mixed
     */
    public function getAttributeValue($key)
    {
        $uidField = $this->getRouteKeyName();
        if (!empty($uidField) && !$this->attributes[$uidField]) {
            $this->attributes[$uidField] = str_replace('-', '', Uuid::uuid4());
        }
        return parent::getAttributeValue($key);
    }
}
