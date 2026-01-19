<?php

namespace ProcessMaker\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Event;
use ProcessMaker\Events\PluginLog;
use ProcessMaker\Managers\PluginManager;
use RuntimeException;

class PluginUninstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'plugin:uninstall {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Uninstall a plugin';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $name = $this->argument('name');

        // Set up event listener to output log messages
        $listener = function (PluginLog $event) {
            $message = $event->message;
            if ($event->pluginName) {
                $message = "[{$event->pluginName}] {$message}";
            }

            switch ($event->status) {
                case 'done':
                    $this->info($message);
                    break;
                case 'error':
                    $this->error($message);
                    break;
                case 'running':
                default:
                    $this->line($message);
                    break;
            }

            // Stop listening if we get a 'done' or 'error' status
            if (in_array($event->status, ['done', 'error'])) {
                Event::forget(PluginLog::class);
            }
        };

        Event::listen(PluginLog::class, $listener);

        try {
            $manager = new PluginManager();
            $manager->uninstall($name);

            return Command::SUCCESS;
        } catch (RuntimeException $e) {
            $this->error('Uninstallation failed: ' . $e->getMessage());
            event(new PluginLog('Uninstallation failed: ' . $e->getMessage(), 'error', $name));

            return Command::FAILURE;
        } finally {
            // Clean up listener
            Event::forget(PluginLog::class);
        }
    }
}
