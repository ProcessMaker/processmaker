<?php

namespace ProcessMaker\Http\Controllers\Api\Project;

use Illuminate\Http\Request;
use ProcessMaker\Facades\ReportTableManager;
use ProcessMaker\Facades\SchemaManager;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Model\PmTable;
use ProcessMaker\Model\Process;
use ProcessMaker\Model\ReportTable;
use ProcessMaker\Model\DbSource;
use ProcessMaker\Transformers\ReportTableTransformer;
use Ramsey\Uuid\Uuid;

/**
 * Handles requests for ReportTables
 * @package ProcessMaker\Http\Controllers\Api\Settings
 */
class ReportTableController extends Controller
{
    /**
     * Gets the list of ReportTables of a process
     *
     * @param Process $process
     * @return array
     */
    public function index(Process $process)
    {
        // Eager load the fields, so that they're properly loaded for the serializer
        $repTables = ReportTable::where('process_id', $process->id)->get();

        return fractal($repTables, new ReportTableTransformer())->respond();

    }

    /**
     * Returns one reportTable and its columns metadata
     *
     * @param Process $process
     * @param ReportTable $reportTable
     * @return array
     */
    public function show(Process $process, ReportTable $reportTable)
    {
        return fractal($reportTable, new ReportTableTransformer())->respond(200);
    }

    /**
     * Stores and creates its physical table defined by the passed request data
     *
     * @param Request $request
     *
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function store(Request $request)
    {
        $pmTable = new PmTable();
        $this->mapRequestToPmTable($request, $pmTable);

        // try to save as reportTable
        $pmTable->saveOrFail();

        // we get the saved table, so we have its id

        // add the fields passed in the request to the ReportTable
        foreach ($request->fields as $field) {
            $reportTableField = $this->mapRequestFieldToReportTableField($field);

            $reportTableField['report_table_id'] = $pmTable->id;
            SchemaManager::updateOrCreateColumn($pmTable, $reportTableField);
        }

        $reportTable = ReportTable::where('id', $pmTable->id)->first();

        return fractal($reportTable, new ReportTableTransformer())->respond(201);
    }

    /**
     *  Updates a ReportTable and the columns that its physical table has
     *
     * @param Request $request
     * @param Process $process
     * @param ReportTable $reportTable
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function update(Request $request, Process $process, ReportTable $reportTable)
    {
        $pmTable = $reportTable->getAssociatedPmTable();
        $this->mapRequestToPmTable($request, $pmTable);
        $pmTable->saveOrFail();

        // changing fields of the reportTable
        if ($request->has('fields')) {
            foreach ($request->fields as $field) {
                $reportTableField = $this->mapRequestFieldToReportTableField($field);
                $reportTableField['report_table_id'] = $reportTable->id;
                SchemaManager::updateOrCreateColumn($pmTable, $reportTableField);
            }
        }


        $savedReportTable = ReportTable::where('id', $reportTable->id)->first();

        return fractal($savedReportTable, new ReportTableTransformer())->respond(200);
    }

    /**
     * Deletes a ReportTable and its related physical table
     *
     * @param Process $process
     * @param ReportTable $reportTable
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function remove(Process $process, ReportTable $reportTable)
    {
        // to remove first we drop the physical table and afterwards the model
        SchemaManager::dropPhysicalTable($reportTable->physicalTableName());
        $reportTable->delete();

        return response([], 204);
    }

    /**
     * Fills the report table with the variables values of all instances of a process
     *
     * @param Process $process
     * @param ReportTable $reportTable
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function populate(Process $process, ReportTable $reportTable)
    {
        ReportTableManager::populateFromInstanceVariables($reportTable);

        //the current PM endpoint returns an empty body
        return response(null, 200);
    }

    /**
     * Returns all the data stored in the physical table
     *
     * @param Process $process
     * @param ReportTable $reportTable
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     * @internal param Request $request
     */
    public function getAllDataRows(Process $process, ReportTable $reportTable)
    {
        $data = $reportTable->allDataRows();
        return response($data, 200);
    }

    /**
     * Maps the fields passed in a request to the fields of a reportTable
     *
     * @param Request $request
     * @param PmTable $pmTable
     * @internal param ReportTable $reportTable
     */
    private function mapRequestToPmTable(Request $request, PmTable $pmTable)
    {
        $colsToChange = $request->toArray();

        if (array_key_exists('name', $colsToChange)) {
            $pmTable->name = $request->name;
        }

        if (array_key_exists('connection', $colsToChange)) {
            $pmTable->db_source_id = DbSource::where('uid', $request->connection)->first()->id;
        }

        if (array_key_exists('description', $colsToChange)) {
            $pmTable->description = $request->description;
        }

        if (array_key_exists('process_uid', $colsToChange)) {
            $pmTable->process_id = Process::where('uid', $request->process_uid)->first()->id;
        }

        if (array_key_exists('type', $colsToChange)) {
            $pmTable->type = $request->type;
        }

        if (array_key_exists('grid', $colsToChange)) {
            $pmTable->grid = $request->grid;
        }
    }

    /**
     * Maps the field's data that comes from the request to the format accepted by the model
     *
     * @param array $field
     *
     * @return array
     */
    private function mapRequestFieldToReportTableField(array $field)
    {
        $reportTableField = [];
        $attributesList = [
            'uid',
            'id',
            'report_table_id',
            'name',
            'description',
            'type',
            'size',
            'null',
            'auto_increment',
            'key',
            'table_index',
            'dynaform_name',
            'dynaform_id',
            'filter'
        ];

        foreach ($attributesList as $attribute) {
            $attributeLowerCase = strtolower($attribute);
            $reportTableField[$attribute] = array_key_exists($attributeLowerCase, $field)
                ? $field[$attributeLowerCase]
                : null;
        }

        return $reportTableField;
    }

}
