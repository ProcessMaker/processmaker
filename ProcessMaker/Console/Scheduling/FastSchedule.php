<?php

namespace ProcessMaker\Console\Scheduling;

use Illuminate\Console\Application;
use Illuminate\Console\Scheduling\CacheAware;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Container\Container;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use LogicException;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

class FastSchedule extends Schedule
{
    /**
     * The index where the current tenant's scheduled events begin.
     */
    private ?int $tenantEventStartIndex = null;

    /**
     * Begin tracking events registered for the current tenant.
     *
     * @return void
     */
    public function beginTenantEventRegistration(): void
    {
        if ($this->tenantEventStartIndex !== null) {
            throw new LogicException(
                'Tenant schedule event registration is already active.'
            );
        }

        $this->tenantEventStartIndex = count($this->events);
        $this->mutexCache = [];
    }

    /**
     * Remove events registered for the current tenant.
     *
     * @return void
     */
    public function clearTenantEvents(): void
    {
        if ($this->tenantEventStartIndex === null) {
            return;
        }

        $this->events = array_slice($this->events, 0, $this->tenantEventStartIndex);
        $this->tenantEventStartIndex = null;
        $this->mutexCache = [];
    }

    /**
     * Re-point the event and scheduling mutexes at the given cache factory.
     *
     * The mutexes hold a hard reference to the CacheManager resolved when the
     * schedule was created. When the tenant cache prefix changes, that stale
     * manager keeps writing mutex keys under the previous prefix, so
     * onOneServer()/withoutOverlapping() locks leak across tenants. Refreshing
     * the factory (and clearing the in-process mutex cache) ensures locks use
     * the currently active, tenant-prefixed cache store.
     *
     * @param  CacheFactory|null  $cache
     * @return void
     */
    public function resetCache(?CacheFactory $cache = null): void
    {
        $cache ??= Container::getInstance()->make('cache');

        if ($this->eventMutex instanceof CacheAware) {
            $this->eventMutex->cache = $cache;
        }

        if ($this->schedulingMutex instanceof CacheAware) {
            $this->schedulingMutex->cache = $cache;
        }

        $this->mutexCache = [];
    }

    /**
     * Add a new Artisan command event that runs in-process when possible.
     *
     * Falls back to a normal shell Event when the command string contains
     * shell metacharacters (redirects, pipes, etc.).
     *
     * @param  SymfonyCommand|string  $command
     * @param  array  $parameters
     * @return \Illuminate\Console\Scheduling\Event
     */
    public function command($command, array $parameters = [])
    {
        $commandDescription = null;

        if ($command instanceof SymfonyCommand) {
            $command = Container::getInstance()->make(get_class($command));
            $commandDescription = $command->getDescription();
            $artisanCommand = $command->getName();
        } elseif (is_string($command) && class_exists($command)) {
            $command = Container::getInstance()->make($command);
            $commandDescription = $command->getDescription();
            $artisanCommand = $command->getName();
        } else {
            $artisanCommand = $command;
        }

        $artisanSignature = $artisanCommand;
        if ($parameters !== []) {
            $artisanSignature .= ' ' . $this->compileParameters($parameters);
        }

        if ($this->containsShellMetacharacters($artisanSignature)) {
            $event = parent::command($artisanCommand, $parameters);

            if ($commandDescription !== null) {
                $event->description($commandDescription);
            }

            return $event;
        }

        $shellCommand = Application::formatCommandString($artisanCommand);
        if ($parameters !== []) {
            $shellCommand .= ' ' . $this->compileParameters($parameters);
        }

        $this->events[] = $event = new FastCommandEvent(
            $this->eventMutex,
            $shellCommand,
            $artisanCommand,
            $parameters,
            $this->timezone
        );

        $this->mergePendingAttributes($event);

        if ($commandDescription !== null) {
            $event->description($commandDescription);
        }

        if (empty($event->description)) {
            $event->name($artisanSignature);
        }

        return $event;
    }

    /**
     * Determine if the artisan signature requires a shell (redirects, pipes, etc.).
     */
    protected function containsShellMetacharacters(string $command): bool
    {
        return (bool) preg_match('/(&&|&|\||>>|>)/', $command);
    }
}
