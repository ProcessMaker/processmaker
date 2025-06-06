<?php

namespace ProcessMaker\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use ProcessMaker\Multitenancy\Tenant;

class MultitenancyList extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'multitenancy:list {--ids : Only output the ids}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all tenants';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tenants = Tenant::all();

        if ($this->option('ids')) {
            // only output the id per line
            foreach ($tenants as $tenant) {
                $this->line($tenant->id);
            }

            return;
        }

        $formattedTenants = $tenants->map(function ($tenant) {
            $config = $tenant->config;

            // Json encode, pretty print without slashes
            $config = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

            return [
                $tenant->id,
                $tenant->name,
                $tenant->database,
                $tenant->username ?? 'NULL',
                $tenant->password ?? 'NULL',
                $config,
            ];
        })->toArray();

        $this->table(['ID', 'Name', 'Database', 'Username', 'Password', 'Config'], $formattedTenants);
    }
}
