<?php

namespace ProcessMaker\Http\Controllers\Api\Designer;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use ProcessMaker\Facades\ProcessFileManager;
use ProcessMaker\Model\Process;
use ProcessMaker\Model\ProcessFile;

/**
 * FileManager implements endpoints to manage the resources of a process.
 *
 */
class FileManagerController extends \ProcessMaker\Http\Controllers\Controller
{

    /**
     * List the contents of a path.
     *
     * @param Request $request
     * @param Process $process
     *
     * @return array
     */
    public function index(Request $request, Process $process)
    {
        $path = $request->input("path", '');
        $getContents = $request->input("get_content", 'true') === 'true';
        return ProcessFileManager::read($path, $process, $getContents);
    }

    /**
     * Stores a process file.
     *
     * @param Request $request
     * @param Process $process
     *
     * @return array
     */
    public function store(Request $request, Process $process)
    {
        $user = Auth::user();
        $data = $request->json()->all();
        $response = ProcessFileManager::store($process, $user, $data);
        return response($response, 201);
    }

    /**
     * Update a process file.
     *
     * @param Request $request
     * @param Process $process
     * @param ProcessFile $processFile
     *
     * @return type
     */
    public function update(Request $request, Process $process, ProcessFile $processFile)
    {
        $user = Auth::user();
        $data = $request->json()->all();
        $response = ProcessFileManager::update($data, $process, $processFile, $user);
        return response($response);
    }

    /**
     * Remove a process file.
     *
     * @param Process $process
     * @param ProcessFile $processFile
     */
    public function remove(Process $process, ProcessFile $processFile)
    {
        ProcessFileManager::remove($process, $processFile);
    }

    /**
     * Remove process files from a path.
     *
     * @param Request $request
     * @param Process $process
     */
    public function removeFolder(Request $request, Process $process)
    {
        $path = $request->input("path", '');
        ProcessFileManager::removeFolder($path, $process);
    }

    /**
     * List a single file.
     *
     * @param Process $process
     * @param ProcessFile $processFile
     * @return type
     */
    public function show(Process $process, ProcessFile $processFile)
    {
        return ProcessFileManager::format($processFile, true, false);
    }

    /**
     * Upload a document.
     *
     * @param Process $process
     * @param ProcessFile $processFile
     * @return type
     */
    public function upload(Request $request, Process $process, ProcessFile $processFile)
    {
        $file = $request->file('prf_file');
        $response = ProcessFileManager::putUploadedFileIntoProcessFile($file, $processFile);
        return response($response, 201);
    }

    /**
     * Download a document.
     *
     * @param Process $process
     * @param ProcessFile $processFile
     * @return type
     */
    public function download(Request $request, Process $process, ProcessFile $processFile)
    {
        $path = $processFile->disk()->path($processFile->getPathInDisk());
        $filename = basename($path);
        //@todo The download response works only with local files.
        return response()->download($path, $filename);
    }
}
