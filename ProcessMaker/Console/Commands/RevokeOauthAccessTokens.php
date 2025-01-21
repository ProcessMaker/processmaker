<?php

namespace ProcessMaker\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class RevokeOauthAccessTokens extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'processmaker:revoke-oauth-access-tokens {--name=} {--after=} {--client_id=} {--no-interaction|n}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Revoke oauth access tokens for a given token name, client_id, or after a given date';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $name = $this->option('name');
        $after = $this->option('after');
        $clientId = $this->option('client_id');
        $noInteraction = $this->option('no-interaction');

        if (!$name && !$after && !$clientId) {
            $this->error('At least one of --name, --after, or --client_id must be specified.');

            return;
        }

        if (!$noInteraction && !$this->confirm('Are you sure you want to revoke this certificate?')) {
            $this->info('Certificate revocation cancelled.');

            return;
        }

        $query = DB::table('oauth_access_tokens')->where('revoked', false);

        if ($name) {
            $names = explode(',', $name);
            $query->whereIn('name', $names);
        }

        if ($after) {
            $date = Carbon::createFromFormat('Y-m-d', $after);
            $query->where('created_at', '>', $date);
        }

        if ($clientId) {
            $clientIds = explode(',', $clientId);
            $query->whereIn('client_id', $clientIds);
        }

        $revokedCount = $query->update(['revoked' => true]);

        $this->info("Revoked $revokedCount tokens.");
    }
}
