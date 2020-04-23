<?php

namespace ProcessMaker;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\ErrorHandler\Error\UndefinedFunctionError;

/**
 * A way to check if a package is installed by seeing if its service provider
 * class exists. This should be computationally cheaper than the method used in the
 * about page (but doesn't provide the extra data that the about page has).
 *
 * The constants below are added as a convenience, and are not a complete list of package
 * service providers. They do drive the "magic" method way of using this helper, though.
 *
 * You can use this helper by:
 *  - using the magic methods:
 *      e.g. PackageHelper::isPmPackageProcessDocumenterInstalled()
 *  - passing the constants directly:
 *      e.g. PackageHelper::isPackageInstalled(PackageHelper::PM_DOCKER_EXECUTOR_LUA)
 *  - passing your own service provider class string:
 *      e.g. PackageHelper::isPackageInstalled('ProcessMaker\Package\WebEntry\WebEntryServiceProvider')
 *
 * We don't use the ::class way to get the classname string, since that would fail if the class is not
 * installed.
 *
 * @package ProcessMaker
 */
class PackageHelper
{
    const PM_DOCKER_EXECUTOR_LUA = 'ProcessMaker\Package\DockerExecutorLua\DockerExecutorLuaServiceProvider';
    const PM_DOCKER_EXECUTOR_NODE = 'ProcessMaker\Package\DockerExecutorNode\DockerExecutorNodeServiceProvider';
    const PM_DOCKER_EXECUTOR_PHP = 'ProcessMaker\Package\DockerExecutorPhp\DockerExecutorPhpServiceProvider';
    const PM_PACKAGE_PROCESS_DOCUMENTER = 'ProcessMaker\Package\PackageProcessDocumenter\PackageServiceProvider';
    const PM_PACKAGE_WEBENTRY = 'ProcessMaker\Package\WebEntry\WebEntryServiceProvider';

    public static function isPackageInstalled(string $serviceProviderClass): bool
    {
        if (! $serviceProviderClass) {
            return false;
        }

        return class_exists($serviceProviderClass);
    }

    /**
     * Handle "magic" invocation.
     *
     * @param $methodName
     * @param $parameters
     * @return bool
     * @throws \Exception
     */
    public static function __callStatic($methodName, $parameters): bool
    {
        $matches = [];
        $matchesMagicMethodSignature = preg_match('/^is(.+)Installed$/', $methodName, $matches);
        if ($matchesMagicMethodSignature) {
            $constantName = self::magicNameToConstantName($matches[1]);
            if (! self::isConstantDefined($constantName)) {
                throw new \Exception(
                    sprintf('%s: No constant named \'%s\' defined.', self::class, $constantName)
                );
            }
            return self::getInstalledStatusForConstant($constantName);
        }

        throw new \Exception(sprintf('%s: No function named \'%s\' defined.', self::class, $methodName));
    }

    private static function getInstalledStatusForConstant(string $constantName)
    {
        return self::isPackageInstalled(constant('self::' . $constantName));
    }

    private static function isConstantDefined($constantName)
    {
        return defined('self::' . $constantName);
    }

    /**
     * Turns the inside of a magic method invocation into its
     * symbolic constant form (i.e. ALL_UPPER_SNAKE).
     */
    private static function magicNameToConstantName($magicName): string
    {
        return strtoupper(Str::snake($magicName));
    }
}
