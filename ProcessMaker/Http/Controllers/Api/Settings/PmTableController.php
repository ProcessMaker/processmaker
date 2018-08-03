<?php

namespace ProcessMaker\Http\Controllers\Api\Settings;

use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use ProcessMaker\Facades\SchemaManager;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Model\DbSource;
use ProcessMaker\Model\Form;
use ProcessMaker\Model\PmTable;
use ProcessMaker\Model\Process;
use ProcessMaker\Transformers\PmTableTransformer;
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
    /**
     * Gets the list of PmTables of a workspace without report tables
     *
     * @param Request $request
     *
     * @return ResponseFactory|Response
     */
    public function index(Request $request)
    {
        $options = [
            'filter' => $request->input('filter', ''),
            'current_page' => $request->input('page', 1),
            'per_page' => $request->input('per_page', 10),
            'sort_by' => $request->input('order_by', 'name'),
            'sort_order' => $request->input('order_direction', 'ASC'),
        ];
        $query = PmTable::where('type', 'PMTABLE');

        if (!empty($options['filter'])) {
            $filter = '%' . $options['filter'] . '%';
            $query->where(function ($query) use ($filter) {
                $query->Where('name', 'like', $filter)
                    ->orWhere('description', 'like', $filter)
                    ->orWhere('type', 'like', $filter);
            });
        }

        $response = $query->paginate($options['per_page'])
            ->appends($options)
            ->each(function (PmTable $pmTable) {
                $pmTable->fields = SchemaManager::getMetadataFromSchema($pmTable)->columns;
            });

        return fractal($response, new PmTableTransformer())
            ->parseIncludes(['fields'])
            ->respond();
    }

    /**
     * Returns one pmTable and its columns metadata
     *
     * @param PmTable $pmTable
     *
     * @return ResponseFactory|Response
     */
    public function show(PmTable $pmTable)
    {
        $pmTable->fields = SchemaManager::getMetadataFromSchema($pmTable)->columns;
        return fractal($pmTable, new PmTableTransformer())
            ->parseIncludes(['fields'])
            ->respond();
    }

    /**
     * Stores and creates its physical table defined by the passed request data
     *
     * @param Request $request
     *
     * @return ResponseFactory|\Symfony\Component\HttpFoundation\Response
     * @throws \Throwable
     */
    public function store(Request $request)
    {
        $pmTable = new PmTable();
        $this->mapRequestToPmTable($request, $pmTable);
        $pmTable->uid = Uuid::uuid4();

        // try to save as pmTable
        $pmTable->saveOrFail();

        // add the fields passed in the request to the PmTable
        foreach ($request->fields as $field) {
            $pmTableField = $this->mapRequestFieldToPmTableField($field);

            $pmTableField['additional_table_id'] = $pmTable->id;
            SchemaManager::updateOrCreateColumn($pmTable, $pmTableField);
        }

        $pmTable->fields = SchemaManager::getMetadataFromSchema($pmTable)->columns;

        return fractal($pmTable, new PmTableTransformer())
            ->parseIncludes(['fields'])
            ->respond(201);
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

                $pmTableField['additional_table_id'] = $pmTable->id;
                SchemaManager::updateOrCreateColumn($pmTable, $pmTableField);
            }
        }

        $pmTable->fields = SchemaManager::getMetadataFromSchema($pmTable)->columns;

        return fractal($pmTable, new PmTableTransformer())
            ->parseIncludes(['fields'])
            ->respond();
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
    )
    {
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
            'uid',
            'name',
            'description',
            'type',
            'grid',
            'tags'
        ];

        foreach ($attributesList as $attribute) {
            $attributeLowerCase = strtolower($attribute);
            if ($request->has($attributeLowerCase)) {
                $pmTable->$attribute = $request->$attributeLowerCase;
            }
        }

        // Add support for inserting with db_source_uid
        if ($request->has('db_source_uid')) {
            // Fetch matching db source
            $dbs = DbSource::where('uid', $request->get('db_source_uid'))->first();
            if ($dbs) {
                $pmTable->db_source_id = $dbs->id;
            }
        }
        // Add support for inserting with process_uid
        if ($request->has('process_uid')) {
            // fetch matching process
            $process = Process::where('uid', $request->get('process_uid'))->first();
            if ($process) {
                $pmTable->process_id = $process->id;
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
            'uid',
            'id',
            'additional_table_id',
            'additional_table_uid',
            'name',
            'description',
            'type',
            'size',
            'null',
            'auto_increment',
            'key',
            'table_index',
            'dynaform_name',
            'dynaform_uid',
            'filter'
        ];

        foreach ($attributesList as $attribute) {
            $attributeLowerCase = strtolower($attribute);
            $pmTableField[$attribute] = array_key_exists($attributeLowerCase, $field)
                ? $field[$attributeLowerCase]
                : null;
        }

        // Handle Dynaform UID reference
        if (array_key_exists('dynaform_uid', $field)) {
            // Fetch matching dynaform
            $dynaform = Form::where('uid', $field['dynaform_uid'])->first();
            if ($dynaform) {
                $pmTableField['dynaform_id'] = $dynaform->id;
            }
        }


        return $pmTableField;
    }
}
