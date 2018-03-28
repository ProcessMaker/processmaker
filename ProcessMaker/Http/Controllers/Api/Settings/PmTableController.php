<?php
namespace ProcessMaker\Http\Controllers\Api\Settings;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use ProcessMaker\Facades\SchemaManager;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Model\PmTable;
use Ramsey\Uuid\Uuid;

/**
 * Handles requests for PmTables
 * @package ProcessMaker\Http\Controllers\Api\Settings
 */
class PmTableController extends Controller
{
    /**
     * Gets the list of PmTables of a workspace without report tables
     *
     * @return array
     */
    public function index()
    {
        // an empty string in the field PRO_UID is a marker for a PmTable, otherwise is a ReportTable
        return PmTable::where('PRO_UID', '')
            ->orWhereNull('PRO_UID')
            ->get()
            ->each(function (PmTable $pmTable) {
                $pmTable->fields = SchemaManager::getMetadataFromSchema($pmTable)->columns;
            })->toArray();
    }

    /**
     * Returns one pmTable and its columns metadata
     *
     * @param PmTable $pmTable
     *
     * @return array
     */
    public function show(PmTable $pmTable)
    {
        $result = $this->lowerCaseModelAttributes($pmTable);
        $result['fields'] = SchemaManager::getMetadataFromSchema($pmTable)->columns;
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

        // try to save as pmTable
        $pmTable->saveOrFail();

        // we get the saved table, so we have its id
        $lastTable = PmTable::find($pmTable->ADD_TAB_UID);
        $pmTable->ADD_TAB_ID = $lastTable->ADD_TAB_ID;

        // add the fields passed in the request to the PmTable
        foreach ($request->fields as $field) {
            $pmTableField = $this->mapRequestFieldToPmTableField($field);

            $pmTableField['FLD_UID'] =  str_replace('-', '', Uuid::uuid4());
            $pmTableField['ADD_TAB_UID'] = $pmTable->ADD_TAB_UID;
            $pmTableField['ADD_TAB_ID'] = $pmTable->ADD_TAB_ID;
            SchemaManager::updateOrCreateColumn($pmTable, $pmTableField);
        }

        $result = $this->lowerCaseModelAttributes($pmTable);
        $result['fields'] = SchemaManager::getMetadataFromSchema($pmTable)->columns;

        return response($result, 201);
    }

    /**
     *  Updates a PmTable and the columns that its physical table has
     *
     * @param Request $request
     * @param PmTable $pmTable
     *
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function update(Request $request, PmTable $pmTable)
    {
        $this->mapRequestToPmTable($request, $pmTable);
        $pmTable->saveOrFail();

        // changing fields of the pmTable
        if ($request->has('fields')) {
            foreach ($request->fields as $field) {
                $pmTableField = $this->mapRequestFieldToPmTableField($field);

                $pmTableField['FLD_UID'] = str_replace('-', '', Uuid::uuid4());
                $pmTableField['ADD_TAB_UID'] = $pmTable->ADD_TAB_UID;
                $pmTableField['ADD_TAB_ID'] = $pmTable->ADD_TAB_ID;
                SchemaManager::updateOrCreateColumn($pmTable, $pmTableField);
            }
        }

        $result = $this->lowerCaseModelAttributes($pmTable);
        $result['fields'] = SchemaManager::getMetadataFromSchema($pmTable)->columns;

        return response($result, 200);
    }

    /**
     * Deletes a PmTable and its related physical table
     *
     * @param PmTable $pmTable
     *
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function remove(PmTable $pmTable)
    {
        // to remove first we drop the physical table and afterwards the model
        SchemaManager::dropPhysicalTable($pmTable->physicalTableName());
        $pmTable->delete();
        return response([], 204);
    }

    /**
     * Returns all the data stored in the physical table
     *
     * @param Request $request
     * @param PmTable $pmTable
     *
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function getAllDataRows(Request $request, PmTable $pmTable)
    {
        $data = $pmTable->allDataRows();
        return response($data, 200);
    }

    /**
     *  Stores in the physical table the data passed to the function
     *
     * @param Request $request
     * @param PmTable $pmTable
     *
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function storeDataRow(Request $request, PmTable $pmTable)
    {
        $pmTable->addDataRow($request->all());
        return response($request->all(), 201);
    }

    /**
     * Updates a row of the physical table. The table must have an primary key,
     * and the primary key should be in the data passed
     *
     * @param Request $request
     * @param PmTable $pmTable
     *
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function updateDataRow(Request $request, PmTable $pmTable)
    {
        $updateData = $pmTable->updateDataRow($request->all());
        return response($updateData, 200);
    }

    /**
     *  Deletes a row in the physical table. Up to 3 keys can be passed as part of the path
     *
     * @param PmTable $pmTable
     * @param string $key1
     * @param string $value1
     * @param string null $key2
     * @param string null $value2
     * @param string null $key3
     * @param string null $value3
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function deleteDataRow(
        PmTable $pmTable,
        $key1,
        $value1,
        $key2 = null,
        $value2 = null,
        $key3 = null,
        $value3 = null
    ) {
        // add the passed keys in the path to an array
        $keys = [$key1 => $value1];

        if ($key2 !== null) {
            $keys[$key2] = $value2;
        }

        if ($key3 !== null) {
            $keys[$key3] = $value3;
        }

        $updateData = $pmTable->deleteDataRow($keys);
        return response($updateData, 204);
    }

    /**
     * Maps the fields passed in a request to the fields of a pmTable
     *
     * @param Request $request
     * @param PmTable $pmTable
     */
    private function mapRequestToPmTable(Request $request, PmTable $pmTable)
    {
        $attributesList = [
            'ADD_TAB_UID',
            'ADD_TAB_NAME',
            'ADD_TAB_DESCRIPTION',
            'ADD_TAB_PLG_UID',
            'DBS_UID',
            'PRO_UID',
            'ADD_TAB_TYPE',
            'ADD_TAB_GRID',
            'ADD_TAB_TAG'
        ];

        foreach ($attributesList as $attribute) {
            $attributeLowerCase = strtolower($attribute);
            if ($request->has($attributeLowerCase)) {
                $pmTable->$attribute = $request->$attributeLowerCase;
            }
        }
    }

    /**
     * Maps the field's data that comes from the request to the format accepted by the model
     *
     * @param array $field
     *
     * @return array
     */
    private function mapRequestFieldToPmTableField(array $field)
    {
        $pmTableField = [];
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
            $pmTableField[$attribute] = array_key_exists($attributeLowerCase, $field)
                ? $field[$attributeLowerCase]
                : null;
        }

        return $pmTableField;
    }

    /**
     * Returns an array from a model where all the keys are in lower case
     *
     * @param Model $model
     *
     * @return array
     */
    private function lowerCaseModelAttributes(Model $model)
    {
        // with a null model an empty array is returned
        if ($model === null) {
            return [];
        }

        $attributeArray = $model->toArray();
        return array_change_key_case($attributeArray, CASE_LOWER);
    }
}
