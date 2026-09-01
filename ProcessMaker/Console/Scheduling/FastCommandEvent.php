<?php

namespace ProcessMaker\Console\Scheduling;

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\EventMutex;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Input\StringInput;

class FastCommandEvent extends Event
{
    /**
     * The Artisan command name or signature string.
     */
    protected string $artisanCommand;

    /**
     * Parameters to pass to Artisan::call().
     *
     * @var array<string|int, mixed>
     */
    protected array $artisanParameters;

    /**
     * @param  array<string|int, mixed>  $artisanParameters
     * @param  \DateTimeZone|string|null  $timezone
     */
    public function __construct(
        EventMutex $mutex,
        string $shellCommand,
        string $artisanCommand,
        array $artisanParameters = [],
        $timezone = null
    ) {
        parent::__construct($mutex, $shellCommand, $timezone);

        $this->artisanCommand = $artisanCommand;
        $this->artisanParameters = $artisanParameters;
    }

    public function artisanCommandName(): string
    {
        return (new StringInput($this->artisanCommand))->getFirstArgument() ?? $this->artisanCommand;
    }

    /**
     * Run the command in-process, or shell out when runInBackground is set.
     *
     * @param  \Illuminate\Contracts\Container\Container  $container
     * @return int
     */
    protected function execute($container)
    {
        if ($this->runInBackground) {
            return parent::execute($container);
        }

        return Artisan::call($this->artisanCommand, $this->artisanParameters);
    }
}
