<?php

namespace ProcessMaker\Console\Scheduling;

use Illuminate\Console\Application;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Container\Container;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

class FastSchedule extends Schedule
{
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
