<?php

namespace ProcessMaker\Managers;

use Illuminate\Support\Facades\DB;
use ProcessMaker\Model\ReportTable;

/**
 * Manager to handle Report Table related functionalities
 */
class ReportTableManager
{
    /**
     * Fills the report table with the variable values of all the instances of the associated report's process
     *
     * @param ReportTable $reportTable
     */
    public function populateFromInstanceVariables(ReportTable $reportTable)
    {
        $pmTable = $reportTable->getAssociatedPmTable();

        // we delete the report table content
        DB::table($pmTable->physicalTablename())->delete();

        $insertString = $this->buildInsertString($reportTable);
        $selectString = $this->buildSelectString($reportTable);
        $reportTableName = $pmTable->physicalTableName();

        $qry = "insert into $reportTableName (APP_UID, $insertString)
                  select uid, $selectString  from APPLICATION";

        DB::statement($qry);
    }

    /**
     * Returns the string that will be used in the insert section of the query that
     * inserts the values of the variables of all instances of a process
     *
     * @param $reportTable
     * @return string
     */
    private function buildInsertString(ReportTable $reportTable)
    {
        $colsInReportTable = $reportTable->variables->map(function ($var) {
            return collect($var->pivot)
                ->only('name')
                ->all();
        })
            ->pluck('name')
            ->toArray();

        return implode(', ', $colsInReportTable);
    }

    /**
     * Returns the string that will be used in the select section of the query that
     * inserts the values of the variables of all instances of a process
     *
     * @param $reportTable
     * @return string
     */
    private function buildSelectString(ReportTable $reportTable)
    {
        $varsInInstance = $reportTable->variables->map(function ($var) {
            return collect($var)
                ->only('VAR_NAME')
                ->all();
        })
            ->pluck('VAR_NAME')
            ->toArray();

        $varsForJsonColumn = array_map(function ($var) {
            return 'APP_DATA->"$.' . $var . '"';
        }, $varsInInstance);

        return implode(', ', $varsForJsonColumn);
    }
}
