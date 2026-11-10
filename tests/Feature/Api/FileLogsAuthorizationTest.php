<?php

namespace Tests\Feature\Api;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use ProcessMaker\Models\Media;
use ProcessMaker\Models\MediaLog;
use ProcessMaker\Models\User;
use Tests\TestCase;

/**
 * Spec for GET /api/1.0/files/{file}/logs.
 *
 * These tests fail while a caller who is denied file metadata can still read
 * that file's access log. They pass once the log route uses the same view
 * check as the file metadata route, on the same media row the response uses.
 */
class FileLogsAuthorizationTest extends TestCase
{
    public $withPermissions = true;

    public function testGuestCannotReadFileAccessLogs(): void
    {
        $scenario = $this->scenario();

        $response = $this->getJson($this->logsUrl($scenario['forbidden']->id, $scenario['allowed']->id));

        $response->assertUnauthorized();
        $this->assertIdentityAbsent($response, $scenario['downloader']);
        $this->assertSame($scenario['snapshot'], $this->snapshot());
    }

    public function testUserWhoCannotViewAFileCannotReadItsAccessLog(): void
    {
        $scenario = $this->scenario();
        $reader = $scenario['reader'];
        $forbidden = $scenario['forbidden'];

        $metadata = $this->actingAs($reader, 'api')
            ->getJson('/api/1.0/files/' . $forbidden->id);
        $metadata->assertForbidden();

        $download = $this->actingAs($reader, 'api')
            ->getJson('/api/1.0/files/' . $forbidden->id . '/contents');
        $download->assertForbidden();

        $before = $this->snapshot();
        $response = $this->actingAs($reader, 'api')
            ->getJson($this->logsUrl($forbidden->id, $scenario['allowed']->id));

        $this->assertSame(
            $metadata->status(),
            $response->status(),
            'File access logs were returned for a file the caller cannot view: ' . $response->getContent()
        );
        $response->assertForbidden();
        $this->assertIdentityAbsent($response, $scenario['downloader']);
        $this->assertIdentityAbsent($response, $scenario['owner']);
        $this->assertSame($before, $this->snapshot());
        Storage::disk('public')->assertExists($forbidden->id . '/' . $forbidden->file_name);
    }

    public function testAccessLogIsForTheFileThatWasAuthorized(): void
    {
        $scenario = $this->scenario();
        $reader = $scenario['reader'];
        $allowed = $scenario['allowed'];

        $metadata = $this->actingAs($reader, 'api')
            ->getJson('/api/1.0/files/' . $allowed->id);
        $metadata->assertOk();

        $before = $this->snapshot();
        $response = $this->actingAs($reader, 'api')
            ->getJson($this->logsUrl($allowed->id, $scenario['forbidden']->id));

        $response->assertOk();
        $response->assertJsonPath('data.0.media_id', $allowed->id);
        $response->assertJsonPath('data.0.event_type', 'FilesDownloaded');
        $response->assertJsonPath('data.0.user.username', $reader->username);
        $response->assertJsonPath('data.0.user.firstname', $reader->firstname);
        $response->assertJsonPath('data.0.user.lastname', $reader->lastname);
        $this->assertNotEmpty($response->json('data.0.created_at'));
        $this->assertCount(1, $response->json('data'));
        $this->assertIdentityAbsent($response, $scenario['downloader']);
        $this->assertIdentityAbsent($response, $scenario['owner']);
        $this->assertSame($before, $this->snapshot());
    }

    public function testViewFilesPermissionCanReadAnotherUsersFileAccessLog(): void
    {
        $scenario = $this->scenario();
        $auditor = User::factory()->create([
            'username' => 'logauditor',
            'firstname' => 'Ava',
            'lastname' => 'Audit',
            'is_administrator' => false,
            'status' => 'ACTIVE',
        ]);
        $auditor->giveDirectPermission('view-files');

        $response = $this->actingAs($auditor, 'api')
            ->getJson('/api/1.0/files/' . $scenario['forbidden']->id . '/logs');

        $response->assertOk();
        $response->assertJsonPath('data.0.media_id', $scenario['forbidden']->id);
        $response->assertJsonPath('data.0.user.username', $scenario['downloader']->username);
        $response->assertJsonPath('data.0.user.firstname', $scenario['downloader']->firstname);
        $response->assertJsonPath('data.0.user.lastname', $scenario['downloader']->lastname);
        $this->assertNotEmpty($response->json('data.0.created_at'));
    }

    /**
     * @return array{
     *     owner: User,
     *     downloader: User,
     *     reader: User,
     *     forbidden: Media,
     *     allowed: Media,
     *     snapshot: array
     * }
     */
    private function scenario(): array
    {
        Storage::fake('public');

        $owner = User::factory()->create([
            'username' => 'logowner',
            'firstname' => 'Nia',
            'lastname' => 'Owens',
            'is_administrator' => false,
            'status' => 'ACTIVE',
        ]);
        $downloader = User::factory()->create([
            'username' => 'logdownloader',
            'firstname' => 'Drew',
            'lastname' => 'Keller',
            'is_administrator' => false,
            'status' => 'ACTIVE',
        ]);
        $reader = User::factory()->create([
            'username' => 'logreader',
            'firstname' => 'Eli',
            'lastname' => 'Reader',
            'is_administrator' => false,
            'status' => 'ACTIVE',
        ]);

        $forbidden = $owner->addMedia(UploadedFile::fake()->create('secret.txt', 1))
            ->toMediaCollection('local');
        $allowed = $reader->addMedia(UploadedFile::fake()->create('mine.txt', 1))
            ->toMediaCollection('local');

        MediaLog::create([
            'media_id' => $forbidden->id,
            'user_id' => $downloader->id,
            'event_type' => 'FilesDownloaded',
        ]);
        MediaLog::create([
            'media_id' => $allowed->id,
            'user_id' => $reader->id,
            'event_type' => 'FilesDownloaded',
        ]);

        return [
            'owner' => $owner,
            'downloader' => $downloader,
            'reader' => $reader,
            'forbidden' => $forbidden->fresh(),
            'allowed' => $allowed->fresh(),
            'snapshot' => $this->snapshot(),
        ];
    }

    private function logsUrl(int $pathFileId, int $otherFileId): string
    {
        $query = http_build_query([
            'id' => $otherFileId,
            'file' => $otherFileId,
            'file_id' => $otherFileId,
            'media_id' => $otherFileId,
        ]);

        return '/api/1.0/files/' . $pathFileId . '/logs?' . $query;
    }

    private function assertIdentityAbsent(TestResponse $response, User $user): void
    {
        $body = $response->getContent();
        $this->assertStringNotContainsString($user->username, $body);
        $this->assertStringNotContainsString($user->firstname, $body);
        $this->assertStringNotContainsString($user->lastname, $body);
    }

    private function snapshot(): array
    {
        return [
            'media' => Media::query()->orderBy('id')->pluck('id')->all(),
            'logs' => MediaLog::query()->orderBy('id')->get(['id', 'media_id', 'user_id', 'event_type'])->toArray(),
            'files' => Storage::disk('public')->allFiles(),
        ];
    }
}
