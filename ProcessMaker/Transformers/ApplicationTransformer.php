<?php
namespace ProcessMaker\Transformers;

use League\Fractal\TransformerAbstract;
use ProcessMaker\Model\Application;

class ApplicationTransformer extends TransformerAbstract
{
	public function transform(Application $application)
	{
	    return [
	        'id'      => $application->APP_UID,
	        'title'   => $application->APP_TITLE,
	        'created_at'    => $application->APP_CREATE_DATE,
	    ];
	}
}
