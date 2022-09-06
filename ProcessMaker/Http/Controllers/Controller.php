<?php

namespace ProcessMaker\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use ProcessMaker\Traits\HasControllerAddons;

/**
 * Our base controller.  Any shared functionality across all web controllers can go here
 */
class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * Our overridden callAction unsets the parameters used by our middleware since
     * controllers don't care about them
     * @param string $method
     * @param array $parameters
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function callAction($method, $parameters)
    {
        // Handled by the SetWorkspace middleware
        unset($parameters['workspace']);
        // Handled by the SetSkin middleware
        unset($parameters['skin']);

        // Handled by the SetLocale middleware
        unset($parameters['lang']);

        // Now call our parent callAction which will route to the appropriate method
        return parent::callAction($method, $parameters);
    }
}
