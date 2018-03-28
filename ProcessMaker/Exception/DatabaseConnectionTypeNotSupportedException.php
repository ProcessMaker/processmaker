<?php

namespace ProcessMaker\Exception;

use Exception;

/**
 * Exception thrown when a connection to a not supported driver (mysql, oracle, etc.) is tried.
 *
 * @package ProcessMaker\Exceptions
 */
class DatabaseConnectionTypeNotSupportedException extends Exception
{

}
