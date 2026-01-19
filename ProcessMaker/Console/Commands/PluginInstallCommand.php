<?php

namespace ProcessMaker\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Event;
use ProcessMaker\Events\PluginLog;
use ProcessMaker\Managers\PluginManager;
use RuntimeException;

class PluginInstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'plugin:install {repo} {--branch=} {--tag=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install a plugin from a GitHub repository or local path';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $repo = $this->argument('repo');
        $branch = $this->option('branch');
        $tag = $this->option('tag');

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
            $manager->install($repo, $branch, $tag);

            return Command::SUCCESS;
        } catch (RuntimeException $e) {
            $this->error('Installation failed: ' . $e->getMessage());
            event(new PluginLog('Installation failed: ' . $e->getMessage(), 'error'));

            return Command::FAILURE;
        } finally {
            // Clean up listener
            Event::forget(PluginLog::class);
        }
    }
}
