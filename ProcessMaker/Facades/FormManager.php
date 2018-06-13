<?php

namespace ProcessMaker\Facades;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Facade;
use ProcessMaker\Model\Form;
use ProcessMaker\Model\Process;

/**
 * Facade for our OutPut Document Manager
 *
 * @package ProcessMaker\Facades
 * @see \ProcessMaker\Managers\FormsManager
 *
 * @method static Paginator index(Process $process, array $options)
 * @method static Form copyImport(Process $process, array $data)
 * @method static Form createBasedPmTable(Process $process, array $data)
 * @method static array update(Process $process, Form $form, array $data)
 * @method static boolean|null remove(Form $form)
 *
 */
class FormManager extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'form.manager';
    }
}