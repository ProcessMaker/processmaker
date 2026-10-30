<?php

namespace ProcessMaker\Console\Commands;

use Illuminate\Console\Command;
use ProcessMaker\Managers\PluginManager;

class PluginListCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'plugin:list';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all installed plugins';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $manager = new PluginManager();
        $plugins = $manager->list();

        if (empty($plugins)) {
            $this->info('No plugins installed.');

            return Command::SUCCESS;
        }

        $headers = ['Name', 'Description', 'Branch/Tag'];
        $rows = [];

        foreach ($plugins as $plugin) {
            $rows[] = [
                $plugin['name'],
                $plugin['description'],
                $plugin['branch'] ?? 'N/A',
            ];
        }

        $this->table($headers, $rows);

        return Command::SUCCESS;
    }
}
