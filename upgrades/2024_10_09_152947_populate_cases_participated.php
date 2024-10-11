<?php

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use ProcessMaker\Repositories\CaseUtils;
use ProcessMaker\Upgrades\UpgradeMigration as Upgrade;

class PopulateCasesParticipated extends Upgrade
{
    /**
     * Run any validations/pre-run checks to ensure the environment, settings,
     * packages installed, etc. are right correct to run this upgrade.
     *
     * Throw a \RuntimeException if the conditions are *NOT* correct for this
     * upgrade migration to run. If this is not a required upgrade, then it
     * will be skipped. Otherwise the exception thrown will be caught, noted,
     * and will prevent the remaining migrations from continuing to run.
     *
     * Returning void or null denotes the checks were successful.
     *
     * @return void
     *
     * @throws \RuntimeException
     */
    public function preflightChecks()
    {
        //
    }

    /**
     * Run the upgrade migration.
     *
     * @return void
     */
    public function up()
    {
        try {
            DB::table('cases_participated')->truncate();

            $this->getProcessRequests()
                ->chunk(1000, function ($rows) {
                    $casesParticipated = [];

                    foreach ($rows as $row) {
                        $processes = CaseUtils::storeProcesses(collect(json_decode($row->processes)));
                        $requests = CaseUtils::storeRequests(collect(json_decode($row->requests)));
                        $requestTokens = CaseUtils::storeRequestTokens(collect(json_decode($row->request_tokens)));
                        $tasks = CaseUtils::storeTasks(collect(json_decode($row->tasks)));
                        $participants = CaseUtils::storeParticipants(collect(json_decode($row->participants)));

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
                            'keywords' => CaseUtils::getCaseNumberByKeywords($row->case_number) . ' ' . $row->case_title,
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

    protected function getProcessRequests(): Builder
    {
        return DB::table('process_requests as pr')
            ->select([
                'u.id as user_id',
                'pr.case_number',
                'pr.case_title',
                'pr.case_title_formatted',
                DB::raw("IF(pr.status = 'ACTIVE', 'IN_PROGRESS', pr.status) as case_status"),
                DB::raw('JSON_ARRAYAGG(JSON_OBJECT("id", pc.id, "name", pc.name)) as processes'),
                DB::raw('JSON_ARRAYAGG(JSON_OBJECT("id", pr.id, "name", pc.name, "parent_request_id", pr.parent_request_id)) as requests'),
                DB::raw('JSON_ARRAYAGG(prt.id) as request_tokens'),
                DB::raw('JSON_ARRAYAGG(IF(prt.element_type != "callActivity", JSON_OBJECT("id", prt.id, "name", prt.element_name, "element_id", prt.element_id, "process_id", prt.process_id), NULL)) as tasks'),
                'par.participants',
                'pr.initiated_at',
                'pr.completed_at',
                'pr.created_at',
                'pr.updated_at'
            ])
            ->join('process_request_tokens as prt', 'pr.id', '=', 'prt.process_request_id')
            ->join('users as u', 'prt.user_id', '=', 'u.id')
            ->join('processes as pc', 'pr.process_id', '=', 'pc.id')
            ->joinSub(
                DB::table('users as u2')
                    ->selectRaw('JSON_ARRAYAGG(u2.id) as participants, pr2.case_number')
                    ->join('process_request_tokens as prt2', 'u2.id', '=', 'prt2.user_id')
                    ->join('process_requests as pr2', 'prt2.process_request_id', '=', 'pr2.id')
                    ->whereNotNull('pr2.case_number')
                    ->groupBy('pr2.case_number'),
                'par',
                'par.case_number',
                '=',
                'pr.case_number'
            )
            ->whereIn('pr.status', ['ACTIVE', 'COMPLETED'])
            ->whereIn('prt.element_type', ['task', 'callActivity', 'scriptTask'])
            ->whereNotNull('pr.case_number')
            ->groupBy('u.id', 'pr.id')
            ->orderBy('pr.id')
            ->orderBy('u.id');
    }
}

