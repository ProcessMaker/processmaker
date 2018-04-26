<?php

namespace ProcessMaker\Http\Controllers\Api\Cases;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Ramsey\Uuid\Uuid;
use ProcessMaker\Http\Controllers\Controller;
use Illuminate\Pagination\Paginator;
use ProcessMaker\Model\Delegation;
use ProcessMaker\Model\Application;

/**
 * API endpoint for returning Cases
 */
class CasesController extends Controller
{


    /**
     * This function return information by searching cases
     *
     * The query is related to advanced search with diferents filters
     * We can search by process, status of case, category of process, users, delegate date from and to
     *
     * @param string $userUid
     * @param integer $start for the pagination
     * @param integer $limit for the pagination
     * @param string $request ->search
     * @param integer $process the pro_UID
     * @param integer $status of the case
     * @param string $dir if the order is DESC or ASC
     * @param string $sort name of column by sort
     * @param string $category uid for the process
     * @param date $dateFrom
     * @param date $dateTo
     * @param string $request ->columnSearch name of column for a specific search
     * @return array $result result of the query
     */

    public function index(Request $request)
    {
        $cases = Delegation::select(
            'APPLICATION.APP_NUMBER',
            'APPLICATION.APP_UID',
            'APPLICATION.APP_STATUS',
            'APPLICATION.APP_STATUS AS APP_STATUS_LABEL',
            'APPLICATION.PRO_UID',
            'APPLICATION.APP_CREATE_DATE',
            'APPLICATION.APP_FINISH_DATE',
            'APPLICATION.APP_UPDATE_DATE',
            'APPLICATION.APP_TITLE',
            'APP_DELEGATION.USR_UID',
            'APP_DELEGATION.TAS_UID',
            'APP_DELEGATION.DEL_INDEX',
            'APP_DELEGATION.DEL_LAST_INDEX',
            'APP_DELEGATION.DEL_DELEGATE_DATE',
            'APP_DELEGATION.DEL_INIT_DATE',
            'APP_DELEGATION.DEL_FINISH_DATE',
            'APP_DELEGATION.DEL_TASK_DUE_DATE',
            'APP_DELEGATION.DEL_RISK_DATE',
            'APP_DELEGATION.DEL_THREAD_STATUS',
            'APP_DELEGATION.DEL_PRIORITY',
            'APP_DELEGATION.DEL_DURATION',
            'APP_DELEGATION.DEL_QUEUE_DURATION',
            'APP_DELEGATION.DEL_STARTED',
            'APP_DELEGATION.DEL_DELAY_DURATION',
            'APP_DELEGATION.DEL_FINISHED',
            'APP_DELEGATION.DEL_DELAYED',
            'APP_DELEGATION.DEL_DELAY_DURATION',
            'TASK.TAS_TITLE AS APP_TAS_TITLE',
            'TASK.TAS_TYPE AS APP_TAS_TYPE',
            'USERS.USR_LASTNAME',
            'USERS.USR_FIRSTNAME',
            'USERS.USR_USERNAME',
            'PROCESS.PRO_TITLE AS APP_PRO_TITLE'
        )
        ->join('APPLICATION', 'APP_DELEGATION.APP_UID', '=', 'APPLICATION.APP_UID')
        ->join('TASK', 'APP_DELEGATION.TAS_UID', '=', 'TASK.TAS_UID')
        ->join('USERS', 'APP_DELEGATION.USR_UID', '=', 'USERS.USR_UID')
        ->join('PROCESS', 'APP_DELEGATION.PRO_UID', '=', 'PROCESS.PRO_UID')
        ->whereNotIn('TASK.TAS_TYPE', [
            "WEBENTRYEVENT",
            "END-MESSAGE-EVENT",
            "START-MESSAGE-EVENT",
            "INTERMEDIATE-THROW-MESSAGE-EVENT",
            "INTERMEDIATE-CATCH-MESSAGE-EVENT"
            ]);
    
        switch ($request->status) {
            case 1:
                $cases
                    ->where('APP_DELEGATION.DEL_THREAD_STATUS', 'OPEN')
                    ->where('APPLICATION.APP_STATUS_ID', '1');
                break;
            case 2:
                $cases
                    ->where('APP_DELEGATION.DEL_THREAD_STATUS', 'OPEN')
                    ->where('APPLICATION.APP_STATUS_ID', '2');
                break;
            case 3:
                $cases
                    ->where('APP_DELEGATION.DEL_LAST_INDEX', '1')
                    ->where('APPLICATION.APP_STATUS_ID', '3');
                break;
            case 4:
                $cases
                    ->where('APP_DELEGATION.DEL_LAST_INDEX', '1')
                    ->where('APPLICATION.APP_STATUS_ID', '4');
                break;
            default:
                $cases
                    ->where('APP_DELEGATION.DEL_THREAD_STATUS', 'OPEN')
                    ->orWhere('APP_DELEGATION.DEL_THREAD_STATUS', 'CLOSED')
                    ->where('APP_DELEGATION.DEL_LAST_INDEX', '1')
                    ->where('APPLICATION.APP_STATUS_ID', '3');
                break;

        }

        if ($request->has('userUid') && $request->userUid <> '') {
            $cases->where('APP_DELEGATION.USR_UID', $request->userUid);
        }

        if ($request->has('process') && $request->process <> '') {
            $cases->where('APP_DELEGATION.PRO_UID', $request->process);
        }

        if ($request->has('category') && $request->category <> '') {
            $cases->where('PROCESS.PRO_CATEGORY', $request->category);
        }

        if ($request->has('search') && $request->search <> '') {
            if ($request->has('columnSearch') && in_array($request->columnSearch, ['APP_TITLE', 'APP_NUMBER'])) {
                $application = Application::where($request->columnSearch, 'LIKE', "%{$request->search}%");
                
                if ($request->columnSearch == 'APP_NUMBER') {
                    if (substr($request->search, 0, 1) != '0' && ctype_digit($request->search)) {
                        $application->where($request->columnSearch, '>=', $request->search);
                    }
                }

                if ($application->count() > 0) {
                    $cases->whereIn('APP_DELEGATION.APP_NUMBER', $application->pluck('APP_NUMBER', 'APP_NUMBER'));
                }
            } elseif ($request->has('columnSearch') && $request->columnSearch === 'TAS_TITLE') {
                $cases->where('TASK.TAS_TITLE', 'LIKE', "%{$request->search}%");
            }
        }

        if ($request->has('dateFrom') && $request->dateFrom <> '') {
            $cases->where('APP_DELEGATION.DEL_DELEGATE_DATE', '>=', Carbon\Carbon::createFromFormat('Y-m-d', $request->dateFrom));
        }

        if ($request->has('dateTo') && $request->dateTo <> '') {
            $cases->where('APP_DELEGATION.DEL_DELEGATE_DATE', '>=', Carbon\Carbon::createFromFormat('Y-m-d 23:59:59', $request->dateTo));
        }

        if ($request->has('sort')) {
            $sort = 'APP_DELEGATION.APP_NUMBER';

            if ($request->sort == 'APP_CURRENT_USER') {
                $sort = 'USR_LASTNAME, USR_FIRSTNAME';
            }

            $dir = "asc";

            if ($request->dir == 'desc') {
                $dir = "desc";
            }

            $cases->orderBy($sort, $dir);
        }

        $limit = 25;

        if ($request->has('limit') && $request->limit > 0) {
            $limit = (int)$request->limit;
        }

        return $cases->paginate($limit);
    }
}
