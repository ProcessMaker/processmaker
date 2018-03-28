<?php
// Bring in our composer autoloader
require __DIR__ . '/../vendor/autoload.php';

// Define any other autoloaders or include paths we need

// Propel (and any other thirdparty which requires strict require/include
set_include_path(
    get_include_path() . PATH_SEPARATOR
    . __DIR__ . '/../thirdparty/' . PATH_SEPARATOR
    . __DIR__ . '/../thirdparty/propel-generator/classes/' . PATH_SEPARATOR
    . __DIR__ . '/../thirdparty/pear/' . PATH_SEPARATOR
    . __DIR__ . '/../workflow/engine/' . PATH_SEPARATOR
    . __DIR__ . '/../rbac/engine/'
);
