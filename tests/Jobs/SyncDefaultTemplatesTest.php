<?php

namespace Tests\Jobs;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use ProcessMaker\Jobs\SyncDefaultTemplates;
use Tests\TestCase;

class SyncDefaultTemplatesTest extends TestCase
{
    private string $localTemplatesDir;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->localTemplatesDir = sys_get_temp_dir() . '/pm-process-templates-' . uniqid('', true);
        mkdir($this->localTemplatesDir, 0777, true);

        Config::set('services.github', [
            'base_url' => 'https://fake.test/',
            'api_url' => 'https://api.fake.test/repos/processmaker/',
            'template_repo' => 'repo',
            'template_branch' => 'main',
            'template_categories' => 'all',
            'template_local_path' => $this->localTemplatesDir,
        ]);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->localTemplatesDir);
        parent::tearDown();
    }

    public function test_job_skips_failed_template_fetches_and_logs_warning()
    {
        // No usable local bake so the job hits HTTP for index + template body.
        Config::set('services.github.template_local_path', '/tmp/does-not-exist-process-templates');

        Http::fake([
            'https://api.fake.test/repos/processmaker/repo/git/ref/heads/main' => Http::response([
                'object' => ['sha' => 'abc123'],
            ]),
            'https://fake.test/repo/main/index.json' => Http::response([
                'default' => [
                    [
                        'uuid' => 'template-uuid',
                        'name' => 'Broken Template',
                        'relative_path' => './broken-template.json',
                        'hash' => 'hash-1',
                    ],
                ],
            ]),
            'https://fake.test/repo/main/broken-template.json' => Http::response(null, 500),
        ]);

        Log::spy();

        (new SyncDefaultTemplates())->handle();

        Log::shouldHaveReceived('warning')
            ->withArgs(function ($message) {
                return str_contains($message, 'Skipped template due to failed fetch');
            })
            ->atLeast()
            ->once();
        $this->assertFalse(Storage::disk('local')->exists(SyncDefaultTemplates::REPO_SHA_FILE));
        $hashes = json_decode((string) Storage::disk('local')->get(SyncDefaultTemplates::CONTENT_HASHES_FILE), true) ?: [];
        $this->assertArrayNotHasKey('template-uuid', $hashes);
    }

    public function test_remote_template_failure_does_not_stamp_success_from_stale_local_bake()
    {
        // Local bake exists but is behind GitHub, so remote is preferred.
        file_put_contents($this->localTemplatesDir . '/.templates-sha', "old-local-sha\n");
        file_put_contents($this->localTemplatesDir . '/index.json', json_encode([
            'default' => [
                [
                    'uuid' => 'template-uuid',
                    'name' => 'Stale Template',
                    'relative_path' => './stale.json',
                    'hash' => 'stale-hash',
                ],
            ],
        ]));
        file_put_contents($this->localTemplatesDir . '/stale.json', json_encode([
            'root' => 'root-node',
            'export' => [
                'root-node' => [
                    'attributes' => [
                        'process_category_id' => null,
                    ],
                ],
            ],
        ]));

        Http::fake([
            'https://api.fake.test/repos/processmaker/repo/git/ref/heads/main' => Http::response([
                'object' => ['sha' => 'new-remote-sha'],
            ]),
            'https://fake.test/repo/main/index.json' => Http::response([
                'default' => [
                    [
                        'uuid' => 'template-uuid',
                        'name' => 'Updated Template',
                        'relative_path' => './updated.json',
                        'hash' => 'remote-hash',
                    ],
                ],
            ]),
            // Transient raw-content failure; must not fall back to the stale bake and stamp success.
            'https://fake.test/repo/main/updated.json' => Http::response(null, 500),
        ]);

        Log::spy();

        (new SyncDefaultTemplates())->handle();

        Log::shouldHaveReceived('warning')
            ->withArgs(function ($message) {
                return str_contains($message, 'Skipped template due to failed fetch');
            })
            ->atLeast()
            ->once();
        $this->assertFalse(Storage::disk('local')->exists(SyncDefaultTemplates::REPO_SHA_FILE));
        $hashes = json_decode((string) Storage::disk('local')->get(SyncDefaultTemplates::CONTENT_HASHES_FILE), true);
        $this->assertIsArray($hashes);
        $this->assertArrayNotHasKey('template-uuid', $hashes);
    }

    public function test_remote_index_failure_does_not_fall_back_to_stale_local_bake()
    {
        file_put_contents($this->localTemplatesDir . '/.templates-sha', "old-local-sha\n");
        file_put_contents($this->localTemplatesDir . '/index.json', json_encode([
            'default' => [],
        ]));

        Http::fake([
            'https://api.fake.test/repos/processmaker/repo/git/ref/heads/main' => Http::response([
                'object' => ['sha' => 'new-remote-sha'],
            ]),
            'https://fake.test/repo/main/index.json' => Http::response(null, 500),
        ]);

        Log::spy();

        (new SyncDefaultTemplates())->handle();

        Log::shouldHaveReceived('warning')
            ->withArgs(function ($message) {
                return str_contains($message, 'Failed to fetch template index from GitHub');
            })
            ->atLeast()
            ->once();
        $this->assertFalse(Storage::disk('local')->exists(SyncDefaultTemplates::REPO_SHA_FILE));
    }

    public function test_job_skips_when_repo_sha_unchanged()
    {
        Storage::disk('local')->put(SyncDefaultTemplates::REPO_SHA_FILE, "abc123\n");

        Http::fake([
            'https://api.fake.test/repos/processmaker/repo/git/ref/heads/main' => Http::response([
                'object' => ['sha' => 'abc123'],
            ]),
            'https://fake.test/*' => Http::response('should-not-be-called', 500),
        ]);

        Log::spy();

        (new SyncDefaultTemplates())->handle();

        Log::shouldHaveReceived('info')
            ->withArgs(function ($message) {
                return str_contains($message, 'Repo SHA unchanged');
            })
            ->once();
        Http::assertSentCount(1);
    }

    public function test_job_skips_template_when_content_hash_unchanged()
    {
        Config::set('services.github.template_local_path', '/tmp/does-not-exist-process-templates');

        Storage::disk('local')->put(
            SyncDefaultTemplates::CONTENT_HASHES_FILE,
            json_encode(['template-uuid' => 'same-hash'])
        );

        Http::fake([
            'https://api.fake.test/repos/processmaker/repo/git/ref/heads/main' => Http::response([
                'object' => ['sha' => 'new-sha'],
            ]),
            'https://fake.test/repo/main/index.json' => Http::response([
                'default' => [
                    [
                        'uuid' => 'template-uuid',
                        'name' => 'Unchanged Template',
                        'relative_path' => './unchanged.json',
                        'hash' => 'same-hash',
                    ],
                ],
            ]),
            'https://fake.test/repo/main/unchanged.json' => Http::response(['should' => 'not-fetch'], 500),
        ]);

        (new SyncDefaultTemplates())->handle();

        Http::assertNotSent(function ($request) {
            return str_contains($request->url(), 'unchanged.json');
        });
        $this->assertSame('new-sha', trim(Storage::disk('local')->get(SyncDefaultTemplates::REPO_SHA_FILE)));
    }

    public function test_job_uses_local_bake_when_github_times_out()
    {
        file_put_contents($this->localTemplatesDir . '/.templates-sha', "local-sha\n");
        // Empty catalog: proves we can finish from the bake without GitHub and stamp the local SHA.
        file_put_contents($this->localTemplatesDir . '/index.json', json_encode([
            'default' => [],
        ]));

        Http::fake(function () {
            throw new ConnectionException('Operation timed out after 10001 milliseconds');
        });

        Log::spy();

        (new SyncDefaultTemplates())->handle();

        $this->assertSame('local-sha', trim(Storage::disk('local')->get(SyncDefaultTemplates::REPO_SHA_FILE)));
    }

    public function test_connection_exception_on_template_fetch_is_soft_failure()
    {
        Config::set('services.github.template_local_path', '/tmp/does-not-exist-process-templates');

        Http::fake([
            'https://api.fake.test/repos/processmaker/repo/git/ref/heads/main' => Http::response([
                'object' => ['sha' => 'remote-sha'],
            ]),
            'https://fake.test/repo/main/index.json' => Http::response([
                'default' => [
                    [
                        'uuid' => 'template-uuid',
                        'name' => 'Flaky Template',
                        'relative_path' => './flaky.json',
                        'hash' => 'hash-flaky',
                    ],
                ],
            ]),
            'https://fake.test/repo/main/flaky.json' => function () {
                throw new ConnectionException('Operation timed out after 10001 milliseconds');
            },
        ]);

        Log::spy();

        (new SyncDefaultTemplates())->handle();

        Log::shouldHaveReceived('warning')
            ->withArgs(function ($message) {
                return str_contains($message, 'Skipped template due to failed fetch')
                    && str_contains($message, 'Flaky Template');
            })
            ->atLeast()
            ->once();
        $this->assertFalse(Storage::disk('local')->exists(SyncDefaultTemplates::REPO_SHA_FILE));
    }

    public function test_job_uses_local_bake_when_sha_matches_remote()
    {
        file_put_contents($this->localTemplatesDir . '/.templates-sha', "same-sha\n");
        file_put_contents($this->localTemplatesDir . '/index.json', json_encode([
            'default' => [],
        ]));

        Http::fake([
            'https://api.fake.test/repos/processmaker/repo/git/ref/heads/main' => Http::response([
                'object' => ['sha' => 'same-sha'],
            ]),
            'https://fake.test/*' => Http::response('should-not-fetch-raw-content', 500),
        ]);

        (new SyncDefaultTemplates())->handle();

        $this->assertSame('same-sha', trim(Storage::disk('local')->get(SyncDefaultTemplates::REPO_SHA_FILE)));
        Http::assertNotSent(function ($request) {
            return str_contains($request->url(), 'fake.test/repo/');
        });
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }
}
