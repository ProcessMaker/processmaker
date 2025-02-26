<?php

namespace Tests\Extensions;

use PHPUnit\Event\Application\Finished;
use PHPUnit\Event\Application\FinishedSubscriber;

final class NotifyMigrationsInTests implements FinishedSubscriber
{
    public function __construct(private string $message)
    {
    }

    public function notify(Finished $event): void
    {
        echo $this->message . PHP_EOL;
    }
}
