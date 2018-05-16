<?php

namespace ProcessMaker\Facades;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Facade;
use ProcessMaker\Model\OutputDocument;
use ProcessMaker\Model\Process;

/**
 * Facade for our OutPut Document Manager
 *
 * @package ProcessMaker\Facades
 * @see \ProcessMaker\Managers\OutputDocumentManager
 *
 * @method static Paginator index(Process $process)
 * @method static OutputDocument save(Process $process, array $data)
 * @method static array update(Process $process, OutputDocument $outPutDocument, array $data)
 * @method static boolean|null remove(OutputDocument $outPutDocument)
 *
 */
class OutputDocumentManager extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'output_document.manager';
    }
}
