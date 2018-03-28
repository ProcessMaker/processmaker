<?php

namespace ProcessMaker\Http\Controllers\Api\Project;

use Illuminate\Http\Request;
use League\Fractal\Manager;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;
use ProcessMaker\Facades\ReportTableManager;
use ProcessMaker\Facades\SchemaManager;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Model\PmTable;
use ProcessMaker\Model\Process;
use ProcessMaker\Model\ReportTable;
use ProcessMaker\Transformers\ProcessMakerSerializer;
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
        $repTables = ReportTable::where('PRO_UID', $process->PRO_UID)->get();

        $fractal = new Manager();
        $fractal->setSerializer(new ProcessMakerSerializer());
        $resource = new Collection($repTables, new ReportTableTransformer());

        return $fractal->createData($resource)->toArray();
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
        $result = $this->serializeReportTable($reportTable);
        $result['fields'] = $reportTable->fields;
        return $result;
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
        $pmTable->ADD_TAB_UID = str_replace('-', '', Uuid::uuid4());

        // try to save as reportTable
        $pmTable->saveOrFail();

        // we get the saved table, so we have its id
        $lastTable = ReportTable::find($pmTable->ADD_TAB_UID);
        $pmTable->ADD_TAB_ID = $lastTable->ADD_TAB_ID;

        // add the fields passed in the request to the ReportTable
        foreach ($request->fields as $field) {
            $reportTableField = $this->mapRequestFieldToReportTableField($field);

            $reportTableField['FLD_UID'] = str_replace('-', '', Uuid::uuid4());
            $reportTableField['ADD_TAB_UID'] = $pmTable->ADD_TAB_UID;
            $reportTableField['ADD_TAB_ID'] = $pmTable->ADD_TAB_ID;
            SchemaManager::updateOrCreateColumn($pmTable, $reportTableField);
        }

        $reportTable = ReportTable::where('ADD_TAB_UID', $pmTable->ADD_TAB_UID)
                        ->get()
                        ->first();

        $result = $this->serializeReportTable($reportTable);
        return response($result, 201);
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
                $reportTableField['FLD_UID'] = str_replace('-', '', Uuid::uuid4());
                $reportTableField['ADD_TAB_UID'] = $reportTable->ADD_TAB_UID;
                $reportTableField['ADD_TAB_ID'] = $reportTable->ADD_TAB_ID;
                SchemaManager::updateOrCreateColumn($pmTable, $reportTableField);
            }
        }


        $savedReportTable = ReportTable::where('ADD_TAB_UID', $reportTable->ADD_TAB_UID)
                            ->get()
                            ->first();

        return response($this->serializeReportTable($savedReportTable), 200);
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

        if (array_key_exists('rep_tab_name', $colsToChange)) {
            $pmTable->ADD_TAB_NAME = $request->rep_tab_name;
        }

        if (array_key_exists('rep_tab_description', $colsToChange)) {
            $pmTable->ADD_TAB_DESCRIPTION = $request->rep_tab_description;
        }

        if (array_key_exists('rep_tab_connection', $colsToChange)) {
            $pmTable->DBS_UID = $request->rep_tab_connection;
        }

        if (array_key_exists('pro_uid', $colsToChange)) {
            $pmTable->PRO_UID = $request->pro_uid;
        }

        if (array_key_exists('rep_tab_type', $colsToChange)) {
            $pmTable->ADD_TAB_TYPE = $request->rep_tab_type;
        }

        if (array_key_exists('rep_tab_grid', $colsToChange)) {
            $pmTable->ADD_TAB_GRID = $request->rep_tab_grid;
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
            'FLD_UID',
            'FLD_ID',
            'ADD_TAB_UID',
            'ADD_TAB_ID',
            'FLD_NAME',
            'FLD_DESCRIPTION',
            'FLD_TYPE',
            'FLD_SIZE',
            'FLD_NULL',
            'FLD_AUTO_INCREMENT',
            'FLD_KEY',
            'FLD_TABLE_INDEX',
            'FLD_DYN_NAME',
            'FLD_DYN_UID',
            'FLD_FILTER'
        ];

        foreach ($attributesList as $attribute) {
            $attributeLowerCase = strtolower($attribute);
            $reportTableField[$attribute] = array_key_exists($attributeLowerCase, $field)
                ? $field[$attributeLowerCase]
                : null;
        }

        return $reportTableField;
    }

    /**
     * Serializes a report table with the information that will be returned by the endpoints
     *
     * @param ReportTable $reportTable
     * @return array
     */
    private function serializeReportTable(ReportTable $reportTable)
    {
        $fractal = new Manager();
        $fractal->setSerializer(new ProcessMakerSerializer());
        $result = new Item($reportTable, new ReportTableTransformer());
        return $fractal->createData($result)->toArray();
    }
}
