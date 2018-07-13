<?php

namespace ProcessMaker\Transformers;

use League\Fractal\TransformerAbstract;
use ProcessMaker\Model\Application;

class ApplicationTransformer extends TransformerAbstract
{
    public function transform(Application $application)
    {
        return $application->toArray();
    }
}
