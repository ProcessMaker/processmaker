<?php

namespace ProcessMaker\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use ProcessMaker\Multitenancy\Tenant;

class TenantsDisable extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenants:disable {--default-tenant-domain=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Disable tenant support';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Get just the domain from the app url
        $domain = parse_url(Env::get('APP_URL'), PHP_URL_HOST);

        $tenant = null;
        if ($this->option('default-tenant-domain')) {
            $tenantDomain = $this->option('default-tenant-domain');
            $tenantId = explode('.', $tenantDomain)[0];
        } else {
            $tenant = Tenant::where('domain', $domain)->firstOrFail();
            $tenantDomain = explode('.', $tenant->domain)[0];
            $tenantId = $tenantDomain;
        }

        $this->recursiveDelete(base_path('storage/framework'));

        // Move storage/tenant_{id} to storage/
        $storageFiles = glob(base_path('storage/tenant_' . $tenantId . '/*'));
        foreach ($storageFiles as $file) {
            if (basename($file) === 'logs') {
                continue;
            }
            $newPath = base_path('storage/' . basename($file));
            rename($file, $newPath);
        }

        $this->recursiveDelete(base_path('storage/tenant_' . $tenantId));
        $this->recursiveDelete(base_path('bootstrap/cache/tenant_' . $tenantId));

        if ($tenant) {
            $tenant->delete();
        }

        $this->info('Tenant support disabled. You must switch to a non-tenant branch to continue. You should delete bootstrap/cache/* and run php artisan optimize');
    }

    private function recursiveDelete($path)
    {
        if (!file_exists($path)) {
            return;
        }

        if (is_file($path)) {
            unlink($path);

            return;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getPathname());
            } else {
                unlink($file->getPathname());
            }
        }

        rmdir($path);
    }
}
