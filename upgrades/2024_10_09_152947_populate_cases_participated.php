<?php

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use ProcessMaker\Repositories\CaseUtils;
use ProcessMaker\Upgrades\UpgradeMigration as Upgrade;

class PopulateCasesParticipated extends Upgrade
{
    /**
     * Run the upgrade migration.
     *
     * @return void
     */
    public function up()
    {
        try {
            DB::table('cases_participated')->truncate();

            $this->createProcessRequestsTempTable();
            $this->createProcessRequestTokensTempTable();
            $this->process_request_participants_temp();

            $this->getProcessRequests()
                ->chunk(1000, function ($rows) {
                    $casesParticipated = [];

                    foreach ($rows as $row) {
                        $processes = CaseUtils::storeProcesses(collect(json_decode($row->processes)));
                        $requests = CaseUtils::storeRequests(collect(json_decode($row->requests)));
                        $requestTokens = CaseUtils::storeRequestTokens(collect(json_decode($row->request_tokens)));
                        $tasks = CaseUtils::storeTasks(collect(json_decode($row->tasks)));
                        $participants = CaseUtils::storeParticipants(collect(json_decode($row->participants)));

                        $dataKeywords = [
                            'case_number' => $row->case_number,
                            'case_title' => $row->case_title,
                        ];

                        array_push($casesParticipated, [
                            'user_id' => $row->user_id,
                            'case_number' => $row->case_number,
                            'case_title' => $row->case_title,
                            'case_title_formatted' => $row->case_title_formatted,
                            'case_status' => $row->case_status,
                            'processes' => $processes,
                            'requests' => $requests,
                            'request_tokens' => $requestTokens,
                            'tasks' => $tasks,
                            'participants' => $participants,
                            'initiated_at' => $row->initiated_at,
                            'completed_at' => $row->completed_at,
                            'created_at' => $row->created_at,
                            'updated_at' => $row->updated_at,
                            'keywords' => CaseUtils::getKeywords($dataKeywords),
                        ]);
                    }

                    DB::table('cases_participated')->insert($casesParticipated);

                    Log::info('Inserted ' . count($casesParticipated) . ' cases participated records');
                });
        } catch (Exception $e) {
            Log::error($e->getMessage());
            echo $e->getMessage();
        }
    }

    /**
     * Reverse the upgrade migration.
     *
     * @return void
     */
    public function down()
    {
        DB::table('cases_participated')->truncate();
    }

    protected function createProcessRequestsTempTable(): void
    {
        DB::statement('DROP TEMPORARY TABLE IF EXISTS process_requests_temp');

        $query = DB::table('process_requests as pr')
            ->select(
                'pr.id',
                'pr.name',
                'pr.case_number',
                'pr.case_title',
                'pr.case_title_formatted',
                'pr.status',
                'pr.parent_request_id',
                'pr.initiated_at',
                'pr.completed_at',
                'pr.created_at',
                'pr.updated_at',
                'pc.id as process_id',
                'pc.name as process_name'
            )
            ->join('processes as pc', 'pr.process_id', '=', 'pc.id')
            ->whereIn('pr.status', ['ACTIVE', 'COMPLETED'])
            ->whereNotNull('pr.case_number');

        DB::statement("CREATE TEMPORARY TABLE IF NOT EXISTS process_requests_temp AS ({$query->toSql()})", $query->getBindings());
    }

    protected function createProcessRequestTokensTempTable(): void
    {
        DB::statement('DROP TEMPORARY TABLE IF EXISTS process_request_tokens_temp');

        $query = DB::table('process_request_tokens as prt')
            ->select([
                'prt.id',
                DB::raw('CAST(prt.id AS CHAR(50)) as task_id'),
                'prt.element_name',
                'prt.element_id',
                'prt.element_type',
                'prt.process_id',
                'prt.status',
                'prt.user_id',
                'prt.process_request_id',
            ])
            ->join('process_requests_temp as prt2', 'prt.process_request_id', '=', 'prt2.id')
            ->whereIn('prt.element_type', ['task', 'callActivity', 'scriptTask'])
            ->whereNotNull('prt.user_id');

        DB::statement("CREATE TEMPORARY TABLE IF NOT EXISTS process_request_tokens_temp AS ({$query->toSql()})", $query->getBindings());
    }

    protected function process_request_participants_temp(): void
    {
        DB::statement('DROP TEMPORARY TABLE IF EXISTS process_request_participants_temp');

        $query = DB::table('process_request_tokens_temp as prt2')
            ->select([
                DB::raw('JSON_ARRAYAGG(prt2.user_id) as participants'),
                'pr2.case_number'
            ])
            ->join('process_requests_temp as pr2', 'prt2.process_request_id', '=', 'pr2.id')
            ->groupBy('pr2.case_number');

        DB::statement("CREATE TEMPORARY TABLE IF NOT EXISTS process_request_participants_temp AS ({$query->toSql()})", $query->getBindings());
    }

    protected function getProcessRequests(): Builder
    {
        return DB::table('process_requests_temp as pr')
            ->select([
                'prt.user_id',
                'pr.case_number',
                'pr.case_title',
                'pr.case_title_formatted',
                DB::raw("IF(pr.status = 'ACTIVE', 'IN_PROGRESS', pr.status) as case_status"),
                DB::raw("JSON_ARRAYAGG(JSON_OBJECT('id', pr.process_id, 'name', pr.process_name)) as processes"),
                DB::raw("JSON_ARRAYAGG(JSON_OBJECT('id', pr.id, 'name', pr.name, 'parent_request_id', pr.parent_request_id)) as requests"),
                DB::raw("JSON_ARRAYAGG(prt.id) as request_tokens"),
                DB::raw("JSON_ARRAYAGG(
                            IF(prt.element_type != 'callActivity',
                                JSON_OBJECT(
                                    'id', prt.task_id,
                                    'name', prt.element_name,
                                    'element_id', prt.element_id,
                                    'process_id', prt.process_id,
                                    'status', prt.status
                                ), JSON_OBJECT())
                        ) as tasks"),
                'par.participants',
                'pr.initiated_at',
                'pr.completed_at',
                'pr.created_at',
                'pr.updated_at'
            ])
            ->join('process_request_tokens_temp as prt', 'pr.id', '=', 'prt.process_request_id')
            ->join('process_request_participants_temp as par', 'pr.case_number', '=', 'par.case_number')
            ->groupBy(
                'prt.user_id',
                'pr.case_number',
                'pr.case_title',
                'pr.case_title_formatted',
                DB::raw("IF(pr.status = 'ACTIVE', 'IN_PROGRESS', pr.status)"),
                'par.participants',
                'pr.initiated_at',
                'pr.completed_at',
                'pr.created_at',
                'pr.updated_at'
            )
            ->orderBy('pr.case_number');
    }
}
