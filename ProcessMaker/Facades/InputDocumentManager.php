<?php

namespace ProcessMaker\Facades;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Facade;
use ProcessMaker\Model\InputDocument;
use ProcessMaker\Model\Process;

/**
 * Facade for our Input Document Manager
 *
 * @package ProcessMaker\Facades
 * @see \ProcessMaker\Managers\InputDocumentManager
 *
 * @method static Paginator index(Process $process)
 * @method static InputDocument save(Process $process, array $data)
 * @method static array update(Process $process, InputDocument $inputDocument, array $data)
 * @method static boolean|null remove(InputDocument $inputDocument)
 *
 */
class InputDocumentManager extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'input_document.manager';
    }
}