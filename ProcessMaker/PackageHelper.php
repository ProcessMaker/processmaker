<?php

namespace ProcessMaker;

use Illuminate\Support\Str;

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
 * We don't use the ::class way to get the service provider's classname string, since
 * that would fail if the class is not installed.
 *
 *
 * @method static bool isPmDockerExecutorLuaInstalled()
 * @method static bool isPmDockerExecutorNodeInstalled()
 * @method static bool isPmDockerExecutorPhpInstalled()
 * @method static bool isPmPackageProcessDocumenterInstalled()
 * @method static bool isPmPackageWebentryInstalled()
 */
class PackageHelper
{
    const PM_DOCKER_EXECUTOR_LUA = 'ProcessMaker\Package\DockerExecutorLua\DockerExecutorLuaServiceProvider';

    const PM_DOCKER_EXECUTOR_NODE = 'ProcessMaker\Package\DockerExecutorNode\DockerExecutorNodeServiceProvider';

    const PM_DOCKER_EXECUTOR_PHP = 'ProcessMaker\Package\DockerExecutorPhp\DockerExecutorPhpServiceProvider';

    const PM_PACKAGE_PROCESS_DOCUMENTER = 'ProcessMaker\Package\PackageProcessDocumenter\PackageServiceProvider';

    const PM_PACKAGE_WEBENTRY = 'ProcessMaker\Package\WebEntry\WebEntryServiceProvider';

    const PM_PACKAGE_VERSIONS = 'ProcessMaker\Package\Versions\PluginServiceProvider';

    const PM_PACKAGE_PROJECTS = 'ProcessMaker\Package\Projects\PackageServiceProvider';

    const PM_PACKAGE_DATA_SOURCES = 'ProcessMaker\Packages\Connectors\DataSources\PluginServiceProvider';

    const PM_PACKAGE_DECISION_ENGINE = 'ProcessMaker\Package\PackageDecisionEngine\PackageServiceProvider';

    const PM_PACKAGE_AB_TESTING = 'ProcessMaker\Package\PackageABTesting\PackageServiceProvider';

    public static function isPackageInstalled(string $serviceProviderClass): bool
    {
        if (!$serviceProviderClass) {
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
    public static function __callStatic(string $methodName, array $parameters): bool
    {
        $matches = [];
        $matchesMagicMethodSignature = preg_match('/^is(.+)Installed$/', $methodName, $matches);
        if ($matchesMagicMethodSignature) {
            $constantName = self::magicNameToConstantName($matches[1]);
            if (!defined('self::' . $constantName)) {
                throw new \Exception(
                    sprintf('%s: No constant named \'%s\' defined.', self::class, $constantName)
                );
            }

            return self::isPackageInstalled(constant('self::' . $constantName));
        }

        throw new \Exception(sprintf('%s: No function named \'%s\' defined.', self::class, $methodName));
    }

    /**
     * Turns the inside of a magic method invocation into its
     * symbolic constant form (i.e. ALL_UPPER_SNAKE).
     */
    private static function magicNameToConstantName(string $magicName): string
    {
        return strtoupper(Str::snake($magicName));
    }
}
