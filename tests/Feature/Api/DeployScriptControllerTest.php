<?php

namespace Tests\Feature\Api;

use ProcessMaker\Models\Permission;
use ProcessMaker\Models\Script;
use ProcessMaker\Models\ScriptExecutor;
use ProcessMaker\Models\ScriptVersion;
use ProcessMaker\Package\Versions\Models\VersionHistory;
use ProcessMaker\Services\ScriptExecutorResolver;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class DeployScriptControllerTest extends TestCase
{
    use RequestHelper;

    private const API_ENDPOINT = '/package-skeleton/deploy-script';

    private const CANONICAL_ENDPOINT = '/deploy-script';

    private const GET_ENDPOINT = '/package-skeleton/script-php';

    public function testDeployScriptRequiresAuthentication(): void
    {
        $this->postJson('/api/1.0' . self::API_ENDPOINT, [
            'code' => '<?php return [];',
            'name' => 'sample-sync-script',
        ])->assertStatus(401);
    }

    public function testDeployScriptValidatesCodeRequired(): void
    {
        $this->apiCall('POST', self::API_ENDPOINT, [
            'name' => 'sample-sync-script',
        ])
            ->assertStatus(422)
            ->assertJson(['success' => false])
            ->assertJsonValidationErrors(['code']);
    }

    public function testDeployScriptCreatesWhenTitleDoesNotExist(): void
    {
        $this->ensureExecutor();
        $code = "<?php\nreturn ['ok' => true];\n";
        $name = 'sample-sync-script';

        $response = $this->apiCall('POST', self::API_ENDPOINT, [
            'code' => $code,
            'name' => $name,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'action' => 'created',
                'name' => $name,
            ]);

        $script = Script::where('title', $name)->first();
        $this->assertNotNull($script);
        $this->assertSame(rtrim($code), rtrim((string) $script->code));
        $this->assertSame($this->user->id, $script->run_as_user_id);
    }

    public function testCanonicalDeployScriptRouteCreatesScript(): void
    {
        $this->ensureExecutor();

        $this->apiCall('POST', self::CANONICAL_ENDPOINT, [
            'code' => '<?php return ["canonical" => true];',
            'name' => 'canonical-sync-script',
        ])
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'action' => 'created',
                'name' => 'canonical-sync-script',
            ]);
    }

    public function testDeployScriptUpdatesExistingScriptByTitle(): void
    {
        $existing = Script::factory()->create([
            'title' => 'sample-update-script',
            'code' => '<?php return ["old" => true];',
            'run_as_user_id' => $this->user->id,
        ]);

        $newCode = "<?php\nreturn ['mapped' => true];\n";

        $this->apiCall('POST', self::API_ENDPOINT, [
            'code' => $newCode,
            'name' => 'sample-update-script',
            'version_name' => 'Refresh script payload',
        ])
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'action' => 'updated',
                'script_id' => $existing->id,
            ]);

        $existing->refresh();
        $this->assertSame(rtrim($newCode), rtrim((string) $existing->code));
    }

    public function testDeployScriptUpdateRequiresVersionName(): void
    {
        $this->ensureExecutor();
        $name = 'version-name-script';

        $this->apiCall('POST', self::API_ENDPOINT, [
            'code' => '<?php return ["v" => 1];',
            'name' => $name,
        ])->assertOk();

        $this->apiCall('POST', self::API_ENDPOINT, [
            'code' => '<?php return ["v" => 2];',
            'name' => $name,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['version_name']);
    }

    public function testDeployScriptUpdateStoresVersionNameWhenCodeUnchangedAndDraftExists(): void
    {
        if (!class_exists(VersionHistory::class)) {
            $this->markTestSkipped('package-versions is not installed.');
        }

        $this->ensureExecutor();
        $name = 'unchanged-deploy-with-draft';
        $code = '<?php return ["v" => 1];';
        $summary = 'Redeploy unchanged payload';

        $this->apiCall('POST', self::API_ENDPOINT, [
            'code' => $code,
            'name' => $name,
        ])->assertOk();

        $script = Script::where('title', $name)->firstOrFail();
        $publishedVersionId = (int) $script->versions()->published()->max('id');
        $script->saveDraft();

        $this->apiCall('POST', self::API_ENDPOINT, [
            'code' => $code,
            'name' => $name,
            'version_name' => $summary,
        ])
            ->assertOk()
            ->assertJson(['action' => 'updated']);

        $this->assertSame(
            $publishedVersionId,
            (int) $script->versions()->published()->max('id')
        );

        $history = VersionHistory::query()
            ->where('versionable_id', $publishedVersionId)
            ->where('versionable_type', ScriptVersion::class)
            ->orderByDesc('id')
            ->first();

        $this->assertNotNull($history);
        $this->assertSame($summary, $history->subject);
    }

    public function testDeployScriptUpdateStoresVersionName(): void
    {
        if (!class_exists(VersionHistory::class)) {
            $this->markTestSkipped('package-versions is not installed.');
        }

        $this->ensureExecutor();
        $name = 'version-name-script-stored';
        $summary = 'Bump return payload';

        $this->apiCall('POST', self::API_ENDPOINT, [
            'code' => '<?php return 1;',
            'name' => $name,
        ])->assertOk();

        $this->apiCall('POST', self::API_ENDPOINT, [
            'code' => '<?php return 2;',
            'name' => $name,
            'version_name' => $summary,
        ])->assertOk();

        $script = Script::where('title', $name)->firstOrFail();
        $version = $script->versions()->orderByDesc('id')->first();
        $this->assertInstanceOf(ScriptVersion::class, $version);

        $history = VersionHistory::query()
            ->where('versionable_id', $version->id)
            ->where('versionable_type', ScriptVersion::class)
            ->orderByDesc('id')
            ->first();

        $this->assertNotNull($history);
        $this->assertSame($summary, $history->subject);
    }

    public function testGetScriptPhpByNameReturnsPhpCode(): void
    {
        $code = "<?php\nreturn ['joined' => true];\n";
        $script = Script::factory()->create([
            'title' => 'sample-get-script',
            'code' => $code,
            'run_as_user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->get('/api/1.0' . self::GET_ENDPOINT . '?name=sample-get-script');

        $response->assertStatus(200);
        $this->assertStringContainsString('text/plain', (string) $response->headers->get('Content-Type'));
        $this->assertSame((string) $script->id, $response->headers->get('X-Script-Id'));
        $this->assertSame($code, $response->getContent());
    }

    public function testDeployScriptPrefersPhpExecutorOnCreate(): void
    {
        if (!ScriptExecutor::query()->where('language', 'php')->exists()) {
            ScriptExecutor::factory()->create(['language' => 'php', 'title' => 'PHP Executor']);
        }

        $executor = app(ScriptExecutorResolver::class)->resolveForNewScript();
        $this->assertNotNull($executor);
        $this->assertSame('php', $executor->language);

        $this->apiCall('POST', self::API_ENDPOINT, [
            'code' => '<?php return [];',
            'name' => 'new-php-script',
            'description' => 'Merged migration script.',
        ])
            ->assertStatus(200)
            ->assertJson(['action' => 'created']);

        $script = Script::where('title', 'new-php-script')->first();
        $this->assertSame($executor->id, $script->script_executor_id);
        $this->assertSame('Merged migration script.', $script->description);
    }

    public function testDeployScriptCreatesWithRequestedExecutorId(): void
    {
        $first = $this->phpExecutor('First PHP Executor');
        $second = $this->phpExecutor('Second PHP Executor');
        $this->assertNotSame($first->id, $second->id);

        $this->apiCall('POST', self::API_ENDPOINT, [
            'code' => '<?php return [];',
            'name' => 'executor-by-id-script',
            'script_executor_id' => $second->id,
        ])->assertOk()->assertJson(['action' => 'created']);

        $this->assertSame($second->id, Script::where('title', 'executor-by-id-script')->value('script_executor_id'));
    }

    public function testDeployScriptCreatesWithRequestedExecutorTitle(): void
    {
        $this->phpExecutor('First PHP Executor');
        $target = $this->phpExecutor('Target PHP Executor');

        $this->apiCall('POST', self::API_ENDPOINT, [
            'code' => '<?php return [];',
            'name' => 'executor-by-title-script',
            'script_executor_title' => 'Target PHP Executor',
        ])->assertOk();

        $this->assertSame($target->id, Script::where('title', 'executor-by-title-script')->value('script_executor_id'));
    }

    public function testDeployScriptUpdateCanMoveScriptToRequestedExecutor(): void
    {
        $this->phpExecutor('First PHP Executor');
        $target = $this->phpExecutor('Target PHP Executor');
        $script = Script::factory()->create(['title' => 'executor-update-script', 'language' => 'php']);
        $this->assertNotSame($target->id, $script->script_executor_id);

        $this->apiCall('POST', self::API_ENDPOINT, [
            'code' => '<?php return [];',
            'name' => 'executor-update-script',
            'script_executor_id' => $target->id,
            'version_name' => 'Move to the plugin executor',
        ])->assertOk()->assertJson(['action' => 'updated']);

        $this->assertSame($target->id, $script->refresh()->script_executor_id);
    }

    public function testDeployScriptUpdateKeepsExecutorWhenNoneIsRequested(): void
    {
        $this->phpExecutor('First PHP Executor');
        $target = $this->phpExecutor('Target PHP Executor');
        $script = Script::factory()->create([
            'title' => 'executor-kept-script',
            'language' => 'php',
            'script_executor_id' => $target->id,
        ]);

        $this->apiCall('POST', self::API_ENDPOINT, [
            'code' => '<?php return [1];',
            'name' => 'executor-kept-script',
            'version_name' => 'Change the code only',
        ])->assertOk();

        $this->assertSame($target->id, $script->refresh()->script_executor_id);
    }

    public function testDeployScriptRejectsUnknownExecutor(): void
    {
        $this->apiCall('POST', self::API_ENDPOINT, [
            'code' => '<?php return [];',
            'name' => 'executor-unknown-script',
            'script_executor_id' => 999999,
        ])
            ->assertStatus(422)
            ->assertJson(['success' => false])
            ->assertJsonValidationErrors(['script_executor_id']);

        $this->assertNull(Script::where('title', 'executor-unknown-script')->first());
    }

    public function testDeployScriptRejectsExecutorOfAnotherLanguage(): void
    {
        $lua = ScriptExecutor::factory()->create(['language' => 'lua', 'title' => 'Lua Executor']);

        $this->apiCall('POST', self::API_ENDPOINT, [
            'code' => '<?php return [];',
            'name' => 'executor-wrong-language-script',
            'script_executor_title' => $lua->title,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['script_executor_title']);

        $this->assertNull(Script::where('title', 'executor-wrong-language-script')->first());
    }

    public function testNonAdminWithoutEditScriptsCannotChooseExecutor(): void
    {
        $default = $this->phpExecutor('Default PHP Executor');
        $this->actAsNonAdmin([]);

        $this->apiCall('POST', self::API_ENDPOINT, [
            'code' => '<?php return [];',
            'name' => 'executor-forbidden-create',
            'script_executor_id' => $default->id,
        ])->assertStatus(403)->assertJson(['success' => false]);

        $this->assertNull(Script::where('title', 'executor-forbidden-create')->first());
    }

    public function testNonAdminCannotMoveScriptToNonDefaultExecutor(): void
    {
        $this->phpExecutor('Default PHP Executor');
        $admin = $this->phpExecutor('Admin PHP Executor');
        $script = Script::factory()->create(['title' => 'executor-forbidden-update', 'language' => 'php']);
        $before = $script->script_executor_id;
        $this->actAsNonAdmin(['edit-scripts']);

        $this->apiCall('POST', self::API_ENDPOINT, [
            'code' => '<?php return [];',
            'name' => 'executor-forbidden-update',
            'script_executor_id' => $admin->id,
            'version_name' => 'Move to the admin executor',
        ])->assertStatus(403)->assertJson(['success' => false]);

        $this->assertSame($before, $script->refresh()->script_executor_id);
    }

    public function testNonAdminCannotUseCustomExecutor(): void
    {
        $default = $this->phpExecutor('Default PHP Executor');
        $default->update(['type' => 'custom']);
        $this->actAsNonAdmin(['edit-scripts']);

        $this->apiCall('POST', self::API_ENDPOINT, [
            'code' => '<?php return [];',
            'name' => 'executor-forbidden-custom',
            'script_executor_id' => $default->id,
        ])->assertStatus(403);

        $this->assertNull(Script::where('title', 'executor-forbidden-custom')->first());
    }

    public function testNonAdminWithEditScriptsCanUseDefaultExecutor(): void
    {
        $default = ScriptExecutor::query()->where('language', 'php')->orderBy('id')->first()
            ?? $this->phpExecutor('Default PHP Executor');
        $this->actAsNonAdmin(['edit-scripts']);

        $this->apiCall('POST', self::API_ENDPOINT, [
            'code' => '<?php return [];',
            'name' => 'executor-allowed-default',
            'script_executor_id' => $default->id,
        ])->assertOk()->assertJson(['action' => 'created']);

        $this->assertSame($default->id, Script::where('title', 'executor-allowed-default')->value('script_executor_id'));
    }

    public function testNonAdminWithoutExecutorKeepsCurrentBehavior(): void
    {
        $this->ensureExecutor();
        $this->actAsNonAdmin([]);

        $this->apiCall('POST', self::API_ENDPOINT, [
            'code' => '<?php return [];',
            'name' => 'executor-none-requested',
        ])->assertOk()->assertJson(['action' => 'created']);
    }

    /**
     * Make the authenticated user a regular user holding only the given permissions.
     */
    private function actAsNonAdmin(array $permissions): void
    {
        $this->user->is_administrator = false;
        $this->user->save();
        $this->initializePermissions();

        foreach ($permissions as $permission) {
            $this->user->permissions()->attach(Permission::byName($permission)->id);
        }
        $this->user->refresh();
        $this->flushSession();
    }

    private function phpExecutor(string $title): ScriptExecutor
    {
        return ScriptExecutor::factory()->create(['language' => 'php', 'title' => $title]);
    }

    private function ensureExecutor(): ScriptExecutor
    {
        return ScriptExecutor::query()->first() ?? ScriptExecutor::factory()->create();
    }
}
