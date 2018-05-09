<?php

namespace ProcessMaker\Http\Controllers\Api\Project;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use ProcessMaker\Exception\DatabaseConnectionFailedException;
use ProcessMaker\Exception\DatabaseConnectionTypeNotSupportedException;
use ProcessMaker\Facades\DatabaseManager;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Model\DbSource;
use ProcessMaker\Model\Process;
use Ramsey\Uuid\Uuid;

/**
 * Handles requests for Database connections of projects (processes)
 * @package ProcessMaker\Http\Controllers\Api\Project
 */
class DatabaseConnectionController extends Controller
{
    /**
     * Gets the list of Database sources of a process
     *
     * @param Process $process
     * @return array Collection of DbSource elements that belong to the process
     */
    public function index(Process $process)
    {
        return $this->lowerCaseListAttributes($process->dbSources);
    }

    /**
     * Gets a database connection
     *
     * @param Process $process
     * @param DbSource $dbSource
     *
     * @return DbSource
     */
    public function show(Process $process, DbSource $dbSource)
    {
        $dbSource = $process->dbSources()->where('id', $dbSource->id)->firstOrFail();
        return $this->lowerCaseModelAttributes($dbSource);
    }

    /**
     * Creates new instance of a DbSource
     *
     * @param Request $request
     * @param Process $process
     *
     * @return DbSource
     */
    public function store(Request $request, Process $process)
    {
        $dbSource = new DbSource();
        $this->mapRequestToDbSource($request, $dbSource);

        $dbSource->process_id = $process->id;

        //the connection should be active and working
        $connectionParams = $this->getConnectionParamsFromRequest($request);

        if (!$dbSource->isValid()) {
            $dbSource->throwValidationException();
        }

        try {
            DatabaseManager::testConnection($connectionParams);
        } catch (Exception $e) {
            return response($e->getMessage(), 422);
        }

        //Try to save
        $dbSource->saveOrFail();
        return $this->lowerCaseModelAttributes($dbSource);
    }

    /**
     * Updates a database connection data
     *
     * @param Request $request
     * @param Process $process
     * @param DbSource $dbSource
     *
     * @return DbSource
     */
    public function update(Request $request, Process $process, DbSource $dbSource)
    {
        $this->mapRequestToDbSource($request, $dbSource);
        $dbSource->process_id = $process->id;

        //the connection should be active and working
        $connectionParams = $this->getConnectionParamsFromRequest($request);

        if (!$dbSource->isValid()) {
            $dbSource->throwValidationException();
        }

        try {
            DatabaseManager::testConnection($connectionParams);
        } catch (Exception $e) {
            return response($e->getMessage(), 422);
        }

        //Try to save
        $dbSource->saveOrFail();
        return $this->lowerCaseModelAttributes($dbSource);
    }

    /**
     * Deletes a database connection
     *
     * @param Process $process
     * @param DbSource $dbSource
     */
    public function remove(Process $process, DbSource $dbSource)
    {
        $dbSource->delete();
    }

    /**
     *  Tries to connect to the database server
     *
     * @param Request $request
     * @param Process $process
     *
     * @return array|\Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function testConnection(Request $request, Process $process)
    {
        $connectionParams = [];
        $connectionParams['driver'] = $request->get('type');
        $connectionParams['host'] = $request->get('server');
        $connectionParams['database'] = $request->get('database_name');
        $connectionParams['username'] = $request->get('username');
        $connectionParams['password'] = $request->get('password');
        $connectionParams['port'] = $request->get('port');

        try {
            DatabaseManager::testConnection($connectionParams);
        } catch (DatabaseConnectionTypeNotSupportedException $e) {
            return response($e->getMessage(), 422);
        } catch (DatabaseConnectionFailedException $e) {
            return response($e->getMessage(), 422);
        }
        return ['resp' => true];
    }

    /**
     * Maps the fields passed in a request to the fields of the passed DbSource
     *
     * @param Request $request
     * @param DbSource $dbSource
     */
    private function mapRequestToDbSource(Request $request, DbSource $dbSource)
    {
        // Check to see if process exists
        if($request->has('process_uid')) {
            $process = Process::where('uid', $request->process_uid)->first();
            if(!$process) {
                throw new \Exception(__("Process not found"));
            }
            $dbSource->process_id = $process->id;
        }
        $dbSource->type = $request->get('type');
        $dbSource->tns = $request->get('tns', null);
        $dbSource->connection_type = $request->get('connection_type', 'NORMAL');
        $dbSource->server = $request->get('server', '');
        $dbSource->database_name = $request->get('database_name', '');
        $dbSource->username = $request->get('username');
        $dbSource->password = encrypt($request->get('password'));
        $dbSource->port = $request->get('port', '');
        $dbSource->encode = $request->get('encode', 'utf8');
        $dbSource->description = $request->get('description');
    }

    /**
     * Returns an array with the list of database parameters used to test a connection
     *
     * @param Request $request
     *
     * @return array
     */
    private function getConnectionParamsFromRequest(Request $request)
    {
        $connectionParams = [];
        $connectionParams['driver'] = $request->get('type');
        $connectionParams['host'] = $request->get('server');
        $connectionParams['port'] = $request->get('port');
        $connectionParams['database'] = $request->get('database_name');
        $connectionParams['username'] = $request->get('username');
        $connectionParams['password'] = $request->get('password');
        return $connectionParams;
    }

    /**
     * Returns an array from a list of models where all the model attributes are converted to array keys in lower case
     *
     * @param Collection $list
     *
     * @return array
     */
    private function lowerCaseListAttributes(Collection $list)
    {
        // with a null list an empty array is returned
        if ($list === null) {
            $list = [];
        }

        $result = [];
        foreach ($list as $model) {
            $result[] = $this->lowerCaseModelAttributes($model);
        }
        return $result;
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
