<?php

namespace Tests\Extensions;

use PHPUnit\Runner\Extension\Extension;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;
use PHPUnit\Event\Test\PreparationStarted;
use PHPUnit\Event\Test\PreparationStartedSubscriber;
use PHPUnit\Event\Test\Passed;
use PHPUnit\Event\Test\PassedSubscriber;
use PHPUnit\Event\Test\Failed;
use PHPUnit\Event\Test\FailedSubscriber;
use PHPUnit\Event\Test\Errored;
use PHPUnit\Event\Test\ErroredSubscriber;
use PHPUnit\Event\Test\Skipped;
use PHPUnit\Event\Test\SkippedSubscriber;

class RealTimeOutputExtension implements Extension
{
    public function bootstrap(Configuration $configuration, Facade $facade, ParameterCollection $parameters): void
    {
        $facade->registerSubscriber(new class implements PreparationStartedSubscriber {
            public function notify(PreparationStarted $event): void
            {
                fwrite(STDERR, "\n\033[1;33m[START]\033[0m " . $event->test()->id() . "\n");
            }
        });

        $facade->registerSubscriber(new class implements PassedSubscriber {
            public function notify(Passed $event): void
            {
                fwrite(STDERR, "\033[1;32m[PASS]\033[0m  " . $event->test()->id() . "\n");
            }
        });

        $facade->registerSubscriber(new class implements FailedSubscriber {
            public function notify(Failed $event): void
            {
                fwrite(STDERR, "\033[1;31m[FAIL]\033[0m  " . $event->test()->id() . "\n");
                // TESTING_VERBOSE will handle the trace, but we can add a small marker here if needed.
            }
        });

        $facade->registerSubscriber(new class implements ErroredSubscriber {
            public function notify(Errored $event): void
            {
                fwrite(STDERR, "\033[1;31m[ERROR]\033[0m " . $event->test()->id() . "\n");
            }
        });

        $facade->registerSubscriber(new class implements SkippedSubscriber {
            public function notify(Skipped $event): void
            {
                fwrite(STDERR, "\033[1;34m[SKIP]\033[0m  " . $event->test()->id() . "\n");
            }
        });
    }
}
