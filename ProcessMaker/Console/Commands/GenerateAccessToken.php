<?php

declare(strict_types=1);

namespace ProcessMaker\Console\Commands;

use Illuminate\Console\Command;
use ProcessMaker\Models\User;

class GenerateAccessToken extends Command
{
    protected $signature = 'processmaker:generate-access-token {username : The username of the user}';

    protected $description = 'Generate an API access token for a user';

    public function handle(): int
    {
        $user = User::where('username', $this->argument('username'))->first();

        if (!$user) {
            return 1;
        }

        $tokenResult = $user->createToken('api-user-token');
        $this->line($tokenResult->accessToken);

        return 0;
    }
}
