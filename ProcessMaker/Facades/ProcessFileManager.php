<?php

namespace ProcessMaker\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Facade for the Process File Manager
 *
 * @package ProcessMaker\Facades
 * @see \ProcessMaker\Managers\ProcessFileManager
 *
 * @method array read($path, \ProcessMaker\Model\Process $process, $getContents = false)
 * @method array store(\ProcessMaker\Model\Process $process, User $user, $data)
 * @method array update(array $data, \ProcessMaker\Model\Process $process, \ProcessMaker\Model\ProcessFile $processFile, User $user)
 * @method void remove(\ProcessMaker\Model\Process $process, \ProcessMaker\Model\ProcessFile $processFile, $verifyingRelationship = true)
 * @method void removeFolder($path, \ProcessMaker\Model\Process $process)
 * @method array format(\ProcessMaker\Model\ProcessFile $processFile, $includeContent = false, $editableAsString = false)
 * @method mixed putUploadedFileIntoProcessFile(UploadedFile $file, ProcessFile $processFile)
 */
class ProcessFileManager extends Facade
{

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'process_file.manager';
    }
}
