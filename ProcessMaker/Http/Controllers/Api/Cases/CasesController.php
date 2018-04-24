<?php

namespace ProcessMaker\Http\Controllers\Api\Cases;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Ramsey\Uuid\Uuid;
use ProcessMaker\Http\Controllers\Controller;
use Illuminate\Pagination\Paginator;

/**
 * API endpoint for VueJS data front end
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

      foreach($request->toArray() as $req_key => $req){

        $request->$req_key = filter_var($req,FILTER_SANITIZE_STRING);

      }

        $limit = 25;

        if ($request->has('limit') && $request->limit > 0) {

            $limit = (int)$request->limit;

        }

        $sqlData = 'SELECT
             STRAIGHT_JOIN APPLICATION.APP_NUMBER,
             APPLICATION.APP_UID,
             APPLICATION.APP_STATUS,
             APPLICATION.APP_STATUS AS APP_STATUS_LABEL,
             APPLICATION.PRO_UID,
             APPLICATION.APP_CREATE_DATE,
             APPLICATION.APP_FINISH_DATE,
             APPLICATION.APP_UPDATE_DATE,
             APPLICATION.APP_TITLE,
             APP_DELEGATION.USR_UID,
             APP_DELEGATION.TAS_UID,
             APP_DELEGATION.DEL_INDEX,
             APP_DELEGATION.DEL_LAST_INDEX,
             APP_DELEGATION.DEL_DELEGATE_DATE,
             APP_DELEGATION.DEL_INIT_DATE,
             APP_DELEGATION.DEL_FINISH_DATE,
             APP_DELEGATION.DEL_TASK_DUE_DATE,
             APP_DELEGATION.DEL_RISK_DATE,
             APP_DELEGATION.DEL_THREAD_STATUS,
             APP_DELEGATION.DEL_PRIORITY,
             APP_DELEGATION.DEL_DURATION,
             APP_DELEGATION.DEL_QUEUE_DURATION,
             APP_DELEGATION.DEL_STARTED,
             APP_DELEGATION.DEL_DELAY_DURATION,
             APP_DELEGATION.DEL_FINISHED,
             APP_DELEGATION.DEL_DELAYED,
             APP_DELEGATION.DEL_DELAY_DURATION,
             TASK.TAS_TITLE AS APP_TAS_TITLE,
             TASK.TAS_TYPE AS APP_TAS_TYPE,
             USERS.USR_LASTNAME,
             USERS.USR_FIRSTNAME,
             USERS.USR_USERNAME,
             PROCESS.PRO_TITLE AS APP_PRO_TITLE
          FROM APP_DELEGATION
          LEFT JOIN APPLICATION ON (APP_DELEGATION.APP_UID = APPLICATION.APP_UID)
          LEFT JOIN TASK ON (APP_DELEGATION.TAS_UID = TASK.TAS_UID)
          LEFT JOIN USERS ON (APP_DELEGATION.USR_UID = USERS.USR_UID)
          LEFT JOIN PROCESS ON (APP_DELEGATION.PRO_UID = PROCESS.PRO_UID)
          WHERE TASK.TAS_TYPE NOT IN ("WEBENTRYEVENT","END-MESSAGE-EVENT","START-MESSAGE-EVENT","INTERMEDIATE-THROW-MESSAGE-EVENT","INTERMEDIATE-CATCH-MESSAGE-EVENT")';

        $status = [
            1 => " AND APP_DELEGATION.DEL_THREAD_STATUS='OPEN' AND APPLICATION.APP_STATUS_ID = 1",
            2 => " AND APP_DELEGATION.DEL_THREAD_STATUS='OPEN' AND APPLICATION.APP_STATUS_ID = 2",
            3 => " AND APPLICATION.APP_STATUS_ID = 3 AND APP_DELEGATION.DEL_LAST_INDEX = 1",
            4 => " AND APPLICATION.APP_STATUS_ID = 4 AND APP_DELEGATION.DEL_LAST_INDEX = 1",
        ];

        if ($request->has('status') && array_key_exists($request->status, $status)) {

            $sqlData .= $status[$request->status];

        } else {

            $sqlData .= " AND (APP_DELEGATION.DEL_THREAD_STATUS = 'OPEN' OR (APP_DELEGATION.DEL_THREAD_STATUS = 'CLOSED' AND APP_DELEGATION.DEL_LAST_INDEX = 1 AND APPLICATION.APP_STATUS_ID = 3)) ";

        }

        if ($request->has('userUid') && $request->userUid <> '') {
            $sqlData .= " AND APP_DELEGATION.USR_UID = " . $request->userUid;
        }

        if ($request->has('process') && $request->process <> '') {
            $sqlData .= " AND APP_DELEGATION.PRO_UID = " . $request->process;
        }

        if ($request->has('category') && $request->category <> '') {
            // $category = mysqli_real_escape_string($con->getResource(), $category);
            $sqlData .= " AND PROCESS.PRO_CATEGORY = '{$request->category}'";
        }

        if ($request->has('search') && $request->search <> '') {

            //If the filter is related to the APPLICATION table: APP_NUMBER or APP_TITLE
            if ($request->has('columnSearch') && in_array($request->columnSearch, ['APP_TITLE', 'APP_NUMBER'])) {

                $sqlSearch = "SELECT APPLICATION.APP_NUMBER FROM APPLICATION WHERE APPLICATION.{$request->columnSearch} LIKE '%{$request->search}%'";

                if ($request->columnSearch == 'APP_NUMBER') {

                    //Cast the search criteria to string
                    if (!is_string($request->search)) {
                        $request->search = (string)$request->search;
                    }
                    //Only if is integer we will to add to greater equal in the query
                    if (substr($request->search, 0, 1) != '0' && ctype_digit($request->search)) {
                        $sqlSearch .= " AND APPLICATION.{$request->columnSearch} >= {$request->search}";
                    }

                }

                if ($request->has('start') && $request->start <> '') {
                    $sqlSearch .= " LIMIT $request->start, " . $limit;
                } else {
                    $sqlSearch .= " LIMIT " . $limit;
                }

                $appNumbers = \DB::select($sqlSearch);

                if (count($appNumbers) > 0) {

                    $sqlData .= " AND APP_DELEGATION.APP_NUMBER IN (" . implode(",", $appNumbers) . ")";

                }

            }

            if ($request->has('columnSearch') && $request->columnSearch === 'TAS_TITLE') {

                $sqlData .= " AND TASK.TAS_TITLE LIKE '%{$request->search}%' ";

            }

        }

        if ($request->has('dateFrom') && $request->dateFrom <> '') {
            $sqlData .= " AND APP_DELEGATION.DEL_DELEGATE_DATE >= '" . date('Y-m-d', strtotime($request->dateFrom)) . "'";
        }

        if ($request->has('dateTo') && $request->dateTo <> '') {
            $sqlData .= " AND APP_DELEGATION.DEL_DELEGATE_DATE <= '" . date('Y-m-d', strtotime($request->dateTo)) . " 23:59:59'";
        }

        //Sorts the records in descending order by default
        if ($request->has('sort') && $request->has('search')) {

            $sort = 'APP_DELEGATION.APP_NUMBER';

            if ($request->sort == 'APP_CURRENT_USER') {

                $sort = 'USR_LASTNAME, USR_FIRSTNAME';

            }

            $dir = "asc";

            if ($request->dir == 'desc') {

                $dir = "desc";

            }

            $sqlData .= " ORDER BY $sort $dir";

        }

        // echo $sqlData."\n";

        // return \DB::select($sqlData);


        // if ($request->has('start') && $request->start <> '') {
        //
        //     $sqlData .= " LIMIT $request->start, " . $limit;
        //
        // } else {
        //
        //     $sqlData .= " LIMIT " . $limit;
        //
        // }

        // \Log::debug($sqlData);


        // dd($sqlData);

        // echo \DB::connection()->getDatabaseName();

        // $tmp = \DB::select('SELECT COUNT(*) FROM APPLICATION');
        //
        // dd($tmp);
        //
        //
        $records = new Paginator(\DB::select($sqlData),$limit);
        //
        // dd($records);

        return $records;

        // die($sqlData);


    }

}
