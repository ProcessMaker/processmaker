<?php

namespace ProcessMaker\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use ProcessMaker\Multitenancy\Tenant;

class TenantsList extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenants:list {--ids : Only output the ids} {--json : Output the tenants as JSON}';

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

        if ($this->option('json')) {
            $this->line(json_encode($tenants->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return;
        }

        $formattedTenants = $tenants->map(function ($tenant) {
            $config = $tenant->config;

            if (isset($config['app.key'])) {
                $config['app.key'] = substr($config['app.key'], 0, 30);
            }

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
