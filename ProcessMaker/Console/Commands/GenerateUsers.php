<?php

namespace ProcessMaker\Console\Commands;

use Throwable;
use ProcessMaker\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;

class GenerateUsers extends Command
{
    public $bar;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:users {count?} {--without-events}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate users as dummy data';

    protected static int $created = 0;

    protected static int $pointer = 0;

    protected static int $count = 0;

    protected static bool $infinite = false;

    protected static bool $without_events = false;

    protected static bool $createSucceeded = true;

    protected static bool $canceled = false;

    /**
     * Execute the console command.
     *
     * @return void
     *
     * @throws \Exception
     */
    public function handle(): void
    {
        $this->registerSignalHandler();

        $this->registerEventsFiring();

        $this->registerProgressBar();

        $this->loop();

        $this->afterLoop();
    }

    protected function registerEventsFiring(): void
    {
        static::$without_events = $this->option('without-events');
    }

    protected static function isCanceled(): bool
    {
        return static::$canceled === true;
    }

    protected static function userCreatedSuccessfully(): bool
    {
        return static::$createSucceeded === true;
    }

    protected static function userCreateFailed(): void
    {
        static::$createSucceeded = false;
    }

    protected static function beforeLoop(): void
    {
        static::$createSucceeded = true;
    }

    protected static function isInfinite(): bool
    {
        return static::$infinite === true;
    }

    protected function loop(): void
    {
        do {
            static::beforeLoop();

            try {
                static::attemptUserCreate();
            } catch (Throwable $e) {
                static::userCreateFailed();
            } finally {
                $this->beforeNextLoop();
            }
        } while ($this->loopIterationComparison());
    }

    protected static function cancel(): void
    {
        static::$canceled = true;
        static::$infinite = false;
    }

    protected function registerSignalHandler(): void
    {
        \pcntl_signal(SIGINT, static function () {
            static::cancel();
        });
    }

    protected function afterLoop(): void
    {
        $this->bar->finish();
        $this->bar->clear();

        $count = static::$created;

        $this->info("{$count} users created successfully");

        App::terminate();
    }

    protected function loopIterationComparison(): bool
    {
        if (static::isInfinite()) {
            return true;
        }

        if (static::isCanceled()) {
            return false;
        }

        return static::$pointer < static::$count;
    }

    protected static function attemptUserCreate(): void
    {
        $createUser = static function () {
            if (User::factory()->create() instanceof User) {
                static::$created++;
            }
        };

        if (static::$without_events) {
            User::withoutEvents($createUser);
        } else {
            $createUser();
        }
    }

    protected function registerProgressBar(): void
    {
        static::$count = (int) ($this->argument('count') ?? 0);

        if (static::$count === 0) {
            static::$infinite = true;
        }

        $this->bar = $this->getOutput()->createProgressBar(static::$count);
        $this->bar->start();
    }

    protected function beforeNextLoop(): void
    {
        if (!static::userCreatedSuccessfully()) {
            return;
        }

        ++static::$pointer;

        $this->bar->advance();

        User::clearBootedModels();

        if (!static::$without_events) {
            App::terminate();
        }
    }
}
