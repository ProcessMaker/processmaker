<?php

namespace ProcessMaker\Facades;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Facade;
use ProcessMaker\Model\OutPutDocument;
use ProcessMaker\Model\Process;

/**
 * Facade for our OutPut Document Manager
 *
 * @package ProcessMaker\Facades
 * @see \ProcessMaker\Managers\OutPutDocumentManager
 *
 * @method static Paginator index(Process $process)
 * @method static OutPutDocument save(Process $process, array $data)
 * @method static array update(Process $process, OutPutDocument $outPutDocument, array $data)
 * @method static boolean|null remove(OutPutDocument $outPutDocument)
 *
 */
class OutPutDocumentManager extends Facade
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
