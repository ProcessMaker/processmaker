<?php

namespace Tests\Feature\Api;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;
use ProcessMaker\Events\ScriptResponseEvent;
use ProcessMaker\Models\EnvironmentVariable;
use ProcessMaker\Models\Script;
use ProcessMaker\Models\ScriptExecutor;
use ProcessMaker\Models\User;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

/**
 * Preview must not let a view-only user run caller-supplied code with the
 * script run-as token and production environment variables.
 *
 * These assertions fail while that behavior is still present.
 */
class ScriptPreviewAuthorizationTest extends TestCase
{
    use RequestHelper;

    public $withPermissions = true;

    private const PLACEHOLDER = 'placeholder-preview-env';

    private const DECOY_PLACEHOLDER = 'placeholder-decoy-env';

    public function setUpWithPersonalAccessClient(): void
    {
        $this->withPersonalAccessClient();
    }

    public function testViewOnlyUserCannotPreviewCallerCodeWithScriptSecrets(): void
    {
        $viewer = $this->makeUserWithPermissions(['view-scripts']);
        $context = $this->makeScriptContext();
        $this->useLocalPhpExecutor();

        $result = $this->postPreview($viewer, $context['script'], $context['decoy'], $context['nonce']);

        $this->assertSame([], $this->problemsForBlockedPreview($viewer, $context, $result));
    }

    public function testEditUserPreviewDoesNotReceiveProductionSecrets(): void
    {
        $editor = $this->makeUserWithPermissions(['view-scripts', 'edit-scripts']);
        $context = $this->makeScriptContext();
        $this->useLocalPhpExecutor();

        $result = $this->postPreview($editor, $context['script'], $context['decoy'], $context['nonce']);

        $this->assertSame([], $this->problemsForSecretFreePreview($context, $result));
    }

    public function testViewOnlyUserCannotPreviewPhpNayraWithScriptSecrets(): void
    {
        $viewer = $this->makeUserWithPermissions(['view-scripts']);
        $context = $this->makeScriptContext('php-nayra');
        $server = $this->startRefusingNayraServer();
        try {
            $result = $this->postPreview($viewer, $context['script'], $context['decoy'], $context['nonce']);
            $this->assertSame([], $this->problemsForBlockedPreview($viewer, $context, $result));
        } finally {
            $this->stopProcess($server);
        }
    }

    public function testViewOnlyUserCannotSendPreviewSecretsToMicroservice(): void
    {
        $viewer = $this->makeUserWithPermissions(['view-scripts']);
        $context = $this->makeScriptContext();
        config([
            'script-runner-microservice.enabled' => true,
            'script-runner-microservice.base_url' => 'http://script-runner.test',
            'script-runner-microservice.keycloak.base_url' => 'http://keycloak.test/token',
            'script-runner-microservice.keycloak.client_id' => 'placeholder-client',
            'script-runner-microservice.keycloak.client_secret' => 'placeholder-client-secret',
            'script-runner-microservice.keycloak.username' => 'placeholder-user',
            'script-runner-microservice.keycloak.password' => 'placeholder-password',
        ]);
        Http::fake(function ($request) {
            if ($request->method() === 'POST' && str_contains($request->url(), 'keycloak.test')) {
                return Http::response([
                    'access_token' => 'placeholder-keycloak-token',
                    'expires_in' => 3600,
                ]);
            }
            if ($request->method() === 'GET') {
                return Http::response([
                    ['language' => 'php', 'id' => 'php'],
                ]);
            }

            return Http::response(['status' => 'queued'], 200);
        });

        $result = $this->postPreview($viewer, $context['script'], $context['decoy'], $context['nonce']);
        $problems = $this->problemsForBlockedPreview($viewer, $context, $result);
        foreach (Http::recorded() as [$request]) {
            $body = (string) $request->body();
            if (str_contains($body, self::PLACEHOLDER) || str_contains($body, self::DECOY_PLACEHOLDER)) {
                $problems[] = 'Preview sent an environment variable value to the script microservice.';
            }
            $cachedToken = Cache::get('script-runner-' . $context['runAs']->id);
            if (is_string($cachedToken) && $cachedToken !== '' && str_contains($body, $cachedToken)) {
                $problems[] = 'Preview sent the run-as API token to the script microservice.';
            }
            $decoyToken = Cache::get('script-runner-' . $context['decoyUser']->id);
            if (is_string($decoyToken) && $decoyToken !== '' && str_contains($body, $decoyToken)) {
                $problems[] = 'Preview sent a different script run-as API token to the script microservice.';
            }
        }

        $this->assertSame([], $problems);
    }

    private function makeUserWithPermissions(array $permissions): User
    {
        $user = User::factory()->create([
            'is_administrator' => false,
            'status' => 'ACTIVE',
        ]);
        $user->giveDirectPermission($permissions);
        $user->refresh();

        return $user;
    }

    private function makeScriptContext(string $language = 'php'): array
    {
        $runAs = User::factory()->create(['is_administrator' => false, 'status' => 'ACTIVE']);
        $decoyUser = User::factory()->create(['is_administrator' => false, 'status' => 'ACTIVE']);
        $executor = ScriptExecutor::where('language', $language)->orderBy('id')->first()
            ?? ScriptExecutor::factory()->create(['language' => $language, 'title' => 'Preview ' . $language]);

        EnvironmentVariable::factory()->create([
            'name' => 'PM_PREVIEW_PLACEHOLDER',
            'description' => 'Preview test placeholder',
            'value' => self::PLACEHOLDER,
        ]);
        EnvironmentVariable::factory()->create([
            'name' => 'PM_PREVIEW_DECOY',
            'description' => 'Preview test decoy placeholder',
            'value' => self::DECOY_PLACEHOLDER,
        ]);

        $savedCode = '<?php return ["saved" => true];';
        $script = Script::factory()->create([
            'language' => $language,
            'code' => $savedCode,
            'timeout' => 0,
            'run_as_user_id' => $runAs->id,
            'script_executor_id' => $executor->id,
        ]);
        $decoy = Script::factory()->create([
            'language' => $language,
            'code' => '<?php return ["decoy" => true];',
            'timeout' => 0,
            'run_as_user_id' => $decoyUser->id,
            'script_executor_id' => $executor->id,
        ]);

        return [
            'runAs' => $runAs,
            'decoyUser' => $decoyUser,
            'script' => $script,
            'decoy' => $decoy,
            'savedCode' => $savedCode,
            'nonce' => 'preview-nonce-' . $script->id . '-' . $decoy->id,
            'runAsTokens' => $this->scriptRunnerTokenCount($runAs->id),
            'decoyTokens' => $this->scriptRunnerTokenCount($decoyUser->id),
        ];
    }

    private function useLocalPhpExecutor(): void
    {
        $home = storage_path('app');
        if (!is_dir($home)) {
            mkdir($home, 0777, true);
        }
        config([
            'script-runner-microservice.enabled' => false,
            'script-runners.php.runner' => 'PhpRunner',
            'app.processmaker_scripts_docker' => base_path('tests/Fixtures/script-preview-docker-shim.php'),
            'app.processmaker_scripts_docker_mode' => 'binding',
            'app.processmaker_scripts_docker_host' => '',
            'app.processmaker_scripts_docker_params' => '',
            'app.processmaker_scripts_home' => $home,
        ]);
    }

    private function postPreview(User $user, Script $script, Script $decoy, string $nonce): array
    {
        $captured = [];
        Event::listen(ScriptResponseEvent::class, function (ScriptResponseEvent $event) use (&$captured) {
            $captured[] = [
                'userId' => $event->userId,
                'status' => $event->status,
                'nonce' => $event->nonce,
                'response' => $event->response,
            ];
        });

        $response = $this->actingAs($user, 'api')->postJson(
            '/api/1.0/scripts/' . $script->id . '/preview',
            [
                'data' => '{}',
                'config' => '{}',
                'code' => $this->previewCode(),
                'nonce' => $nonce,
                'script_id' => $decoy->id,
            ]
        );

        return [
            'status' => $response->status(),
            'events' => $captured,
            'stored' => $this->storedExecutionPayloads($user),
        ];
    }

    private function previewCode(): string
    {
        return <<<'PHP'
<?php
return [
    'environment_value' => getenv('PM_PREVIEW_PLACEHOLDER'),
    'decoy_value' => getenv('PM_PREVIEW_DECOY'),
    'api_token' => getenv('API_TOKEN'),
];
PHP;
    }

    private function problemsForBlockedPreview(User $viewer, array $context, array $result): array
    {
        $this->assertTrue($viewer->fresh()->hasPermission('view-scripts'));
        $this->assertFalse($viewer->fresh()->hasPermission('edit-scripts'));
        $this->assertFalse((bool) $viewer->is_administrator);

        $problems = [];
        if ($result['status'] !== 403) {
            $problems[] = 'View-only preview returned HTTP ' . $result['status'] . '.';
        }
        $problems = array_merge($problems, $this->problemsForSecretFreePreview($context, $result));
        if ($context['script']->fresh()->code !== $context['savedCode']) {
            $problems[] = 'Preview changed the saved script code.';
        }

        return $problems;
    }

    private function problemsForSecretFreePreview(array $context, array $result): array
    {
        $problems = [];
        if ($this->scriptRunnerTokenCount($context['runAs']->id) !== $context['runAsTokens']) {
            $problems[] = 'Preview created a script-runner access token for the authorized script run-as user.';
        }
        if ($this->scriptRunnerTokenCount($context['decoyUser']->id) !== $context['decoyTokens']) {
            $problems[] = 'Preview created a script-runner access token for a different script than the one in the route.';
        }
        if (Cache::has('script-runner-' . $context['runAs']->id)) {
            $problems[] = 'Preview cached a run-as API token for the authorized script.';
        }
        if (Cache::has('script-runner-' . $context['decoyUser']->id)) {
            $problems[] = 'Preview cached a run-as API token for a different script than the one in the route.';
        }

        $payloads = [];
        foreach ($result['events'] as $event) {
            $payloads[] = $event['response'];
            if ($event['nonce'] !== $context['nonce'] && $event['nonce'] !== null) {
                $problems[] = 'Preview broadcast a response for a different nonce.';
            }
            if ((int) $event['userId'] !== (int) $context['runAs']->id
                && (int) $event['userId'] !== (int) $this->user->id
                && $event['userId'] !== null) {
                // The caller is the principal who must not receive the payload.
            }
        }
        foreach ($result['stored'] as $stored) {
            $payloads[] = $stored['payload'];
        }

        foreach ($payloads as $payload) {
            $encoded = json_encode($payload);
            if (!is_string($encoded)) {
                continue;
            }
            if (str_contains($encoded, self::PLACEHOLDER)) {
                $problems[] = 'Preview result contained the environment variable placeholder.';
            }
            if (str_contains($encoded, self::DECOY_PLACEHOLDER)) {
                $problems[] = 'Preview result contained the decoy environment variable placeholder.';
            }
            $runAsToken = Cache::get('script-runner-' . $context['runAs']->id);
            if (is_string($runAsToken) && $runAsToken !== '' && str_contains($encoded, $runAsToken)) {
                $problems[] = 'Preview result contained the authorized script run-as API token.';
            }
            $decoyToken = Cache::get('script-runner-' . $context['decoyUser']->id);
            if (is_string($decoyToken) && $decoyToken !== '' && str_contains($encoded, $decoyToken)) {
                $problems[] = 'Preview result contained a different script run-as API token.';
            }
        }

        return array_values(array_unique($problems));
    }

    private function storedExecutionPayloads(User $user): array
    {
        $stored = [];
        $prefix = Cache::getStore()->getPrefix();
        $redis = Redis::connection('cache');
        $keys = $redis->keys($prefix . 'srn.*');
        foreach ($keys as $redisKey) {
            $cacheKey = substr($redisKey, strlen($prefix));
            if (!str_starts_with($cacheKey, 'srn.')) {
                continue;
            }
            $executionKey = substr($cacheKey, strlen('srn.'));
            $payload = Cache::get($cacheKey);
            $stored[] = ['payload' => $payload];
            $retrieved = $this->actingAs($user, 'api')->getJson('/api/1.0/scripts/execution/' . $executionKey);
            if ($retrieved->status() === 200) {
                $stored[] = ['payload' => $retrieved->json()];
            }
        }

        return $stored;
    }

    private function scriptRunnerTokenCount(int $userId): int
    {
        return (int) DB::table('oauth_access_tokens')
            ->where('user_id', $userId)
            ->where('name', 'script-runner')
            ->count();
    }

    private function startRefusingNayraServer(): array
    {
        $port = $this->freePort();
        $router = tempnam(sys_get_temp_dir(), 'nayra-router');
        file_put_contents($router, "<?php\nhttp_response_code(500);\necho 'unavailable';\n");
        $pipes = [];
        $process = proc_open(
            [PHP_BINARY, '-S', '127.0.0.1:' . $port, $router],
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes
        );
        config(['app.nayra_port' => $port]);
        Cache::forever('nayra_ips', ['127.0.0.1']);
        $this->waitForPort($port);

        return ['process' => $process, 'pipes' => $pipes, 'router' => $router];
    }

    private function stopProcess(array $server): void
    {
        if (is_resource($server['process'])) {
            proc_terminate($server['process']);
            foreach ($server['pipes'] as $pipe) {
                if (is_resource($pipe)) {
                    fclose($pipe);
                }
            }
            proc_close($server['process']);
        }
        if (is_string($server['router']) && is_file($server['router'])) {
            @unlink($server['router']);
        }
    }

    private function freePort(): int
    {
        $socket = stream_socket_server('tcp://127.0.0.1:0', $errno, $error);
        $name = stream_socket_get_name($socket, false);
        fclose($socket);

        return (int) substr($name, strrpos($name, ':') + 1);
    }

    private function waitForPort(int $port): void
    {
        $deadline = microtime(true) + 5;
        while (microtime(true) < $deadline) {
            $connection = @fsockopen('127.0.0.1', $port, $errno, $error, 0.2);
            if (is_resource($connection)) {
                fclose($connection);

                return;
            }
            usleep(50000);
        }
    }
}
