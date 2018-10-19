<?php
namespace ProcessMaker\Exception;

use Exception;

/**
 * The required script language is not supported.
 *
 */
class ScriptLanguageNotSupported extends Exception
{

    /**
     * @param string $language
     */
    public function __construct($language)
    {
        parent::__construct(__('exceptions.ScriptLanguageNotSupported', ['language' => $language]));
    }
}
