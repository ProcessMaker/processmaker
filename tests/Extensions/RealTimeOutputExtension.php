<?php

namespace Tests\Extensions;

use PHPUnit\Event\Test\Errored;
use PHPUnit\Event\Test\ErroredSubscriber;
use PHPUnit\Event\Test\Failed;
use PHPUnit\Event\Test\FailedSubscriber;
use PHPUnit\Event\Test\Passed;
use PHPUnit\Event\Test\PassedSubscriber;
use PHPUnit\Event\Test\PreparationStarted;
use PHPUnit\Event\Test\PreparationStartedSubscriber;
use PHPUnit\Event\Test\Prepared;
use PHPUnit\Event\Test\PreparedSubscriber;
use PHPUnit\Event\Test\Skipped;
use PHPUnit\Event\Test\SkippedSubscriber;
use PHPUnit\Runner\Extension\Extension;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;

class RealTimeOutputExtension implements Extension
{
    /** @var array<string, float> */
    private static array $startedAt = [];

    /** @var array<string, float> */
    private static array $preparedAt = [];

    public function bootstrap(Configuration $configuration, Facade $facade, ParameterCollection $parameters): void
    {
        $facade->registerSubscriber(new class implements PreparationStartedSubscriber {
            public function notify(PreparationStarted $event): void
            {
                $id = $event->test()->id();
                RealTimeOutputExtension::markStarted($id);
                RealTimeOutputExtension::write('START', $id, "\033[1;33m", true);
            }
        });

        $facade->registerSubscriber(new class implements PreparedSubscriber {
            public function notify(Prepared $event): void
            {
                $id = $event->test()->id();
                RealTimeOutputExtension::markPrepared($id);
                $setupDuration = RealTimeOutputExtension::elapsedSinceStart($id);
                RealTimeOutputExtension::write(
                    'PREPARED',
                    $id,
                    "\033[1;36m",
                    false,
                    $setupDuration !== null ? sprintf('setup=%.2fs', $setupDuration) : null
                );
            }
        });

        $facade->registerSubscriber(new class implements PassedSubscriber {
            public function notify(Passed $event): void
            {
                RealTimeOutputExtension::writeFinished('PASS', $event->test()->id(), "\033[1;32m");
            }
        });

        $facade->registerSubscriber(new class implements FailedSubscriber {
            public function notify(Failed $event): void
            {
                RealTimeOutputExtension::writeFinished('FAIL', $event->test()->id(), "\033[1;31m");
            }
        });

        $facade->registerSubscriber(new class implements ErroredSubscriber {
            public function notify(Errored $event): void
            {
                RealTimeOutputExtension::writeFinished('ERROR', $event->test()->id(), "\033[1;31m");
            }
        });

        $facade->registerSubscriber(new class implements SkippedSubscriber {
            public function notify(Skipped $event): void
            {
                RealTimeOutputExtension::writeFinished('SKIP', $event->test()->id(), "\033[1;34m");
            }
        });
    }

    public static function markStarted(string $id): void
    {
        self::$startedAt[$id] = microtime(true);
        unset(self::$preparedAt[$id]);
    }

    public static function markPrepared(string $id): void
    {
        self::$preparedAt[$id] = microtime(true);
    }

    public static function elapsedSinceStart(string $id): ?float
    {
        if (!isset(self::$startedAt[$id])) {
            return null;
        }

        return microtime(true) - self::$startedAt[$id];
    }

    public static function writeFinished(string $label, string $id, string $color): void
    {
        $total = self::elapsedSinceStart($id);
        $body = null;
        if ($total !== null) {
            $body = sprintf('total=%.2fs', $total);
            if (isset(self::$preparedAt[$id])) {
                $body .= sprintf(' body=%.2fs', microtime(true) - self::$preparedAt[$id]);
            }
        }

        self::write($label, $id, $color, false, $body);
        unset(self::$startedAt[$id], self::$preparedAt[$id]);
    }

    public static function write(string $label, string $id, string $color, bool $leadingNewline = false, ?string $extra = null): void
    {
        $timestamp = date('H:i:s');
        $prefix = $leadingNewline ? "\n" : '';
        $suffix = $extra ? " ({$extra})" : '';
        fwrite(STDERR, sprintf(
            "%s%s[%s]%s [%s] %s%s\n",
            $prefix,
            $color,
            $label,
            "\033[0m",
            $timestamp,
            $id,
            $suffix
        ));
    }
}
