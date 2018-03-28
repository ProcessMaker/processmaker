<?php
namespace ProcessMaker\Http\Controllers\Api\Cases;

use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Model\Application;

/**
 * Handles requests for variables for cases
 * @package ProcessMaker\Http\Controllers\Api\Cases
 */
class VariableController extends Controller
{
    /**
     * Get variables from a case. Note that this endpoint can return the variables from any case, regardless of its
     * status, so it can get the variables for paused, completed or even canceled cases. The only cases for which it
     * can't get the variables are deleted cases, because their records has been removed from the APPLICATION table in
     * the database.
     * @param Application $application The case requested
     * @return mixed
     */
    public function get(Application $application)
    {
        // Simply return a combined array of our system constants and the data model for our case
        return array_merge(app()->getSystemConstants(), (array) $application->getData());
    }
}
