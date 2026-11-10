<?php

namespace Tests\Feature\Http;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use ProcessMaker\Models\Media;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\User;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

/**
 * Private request documents must not be readable from the public storage URL,
 * and an uploaded SVG must not be returned as active content.
 *
 * These assertions describe the fixed behavior. They fail while the
 * unauthenticated public-disk route still serves those files inline.
 */
class PrivateStorageDisclosureTest extends TestCase
{
    private const ACTIVE_CONTENT_TYPES = [
        'image/svg+xml',
        'image/svg',
        'text/html',
        'application/xhtml+xml',
    ];

    public function testGuestCannotReadFileApiDocumentThroughStorageRoute(): void
    {
        $secret = 'pmDocMarker-file-api';
        $uploaded = $this->uploadThroughFileApi('quarterly-contract.txt', $secret);

        $response = $this->guestGet($this->storageUrl($uploaded['id'], $uploaded['file_name']));

        $this->assertPrivateBytesHidden($response, $secret);
    }

    public function testGuestCannotReadRequestFileThroughStorageRoute(): void
    {
        $secret = 'pmDocMarker-request-file';
        $uploaded = $this->uploadThroughRequestFiles('benefits-form.txt', $secret);

        $response = $this->guestGet($this->storageUrl($uploaded['id'], $uploaded['file_name']));

        $this->assertPrivateBytesHidden($response, $secret);
    }

    public function testOtherAuthenticatedUserCannotReadRequestFileThroughStorageRoute(): void
    {
        $secret = 'pmDocMarker-other-user';
        $uploaded = $this->uploadThroughRequestFiles('payroll.txt', $secret);
        $stranger = User::factory()->create();

        $response = $this->actingAs($stranger, 'web')
            ->get($this->storageUrl($uploaded['id'], $uploaded['file_name']));

        $this->assertPrivateBytesHidden($response, $secret);
    }

    public function testAuthorizedUserCannotReadADifferentMediaObjectThroughStorageRoute(): void
    {
        $owner = User::factory()->create();
        $alpha = $this->uploadThroughRequestFiles('alpha.txt', 'pmDocMarker-alpha', $owner);
        $beta = $this->uploadThroughRequestFiles('beta.txt', 'pmDocMarker-beta');

        $response = $this->actingAs($owner, 'web')
            ->get($this->storageUrl($beta['id'], $beta['file_name']));

        $this->assertPrivateBytesHidden($response, 'pmDocMarker-beta');
        $this->assertNotSame(
            $alpha['id'],
            $beta['id'],
            'The two documents must be different media rows so authorization of one cannot cover the other.'
        );
    }

    public function testAuthorizedUserCannotReadADifferentFileUnderTheSameMediaId(): void
    {
        $owner = User::factory()->create();
        $uploaded = $this->uploadThroughRequestFiles('visible.txt', 'pmDocMarker-visible', $owner);
        $siblingName = 'sibling-secret.txt';
        $siblingSecret = 'pmDocMarker-sibling';
        Storage::disk('public')->put($uploaded['id'] . '/' . $siblingName, $siblingSecret);

        try {
            $response = $this->actingAs($owner, 'web')
                ->get($this->storageUrl($uploaded['id'], $siblingName));

            $this->assertPrivateBytesHidden($response, $siblingSecret);
        } finally {
            Storage::disk('public')->delete($uploaded['id'] . '/' . $siblingName);
        }
    }

    public function testUploadedSvgIsNotServedAsActiveContent(): void
    {
        $marker = 'pmSvgProbeMarker';
        $svg = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<svg xmlns="http://www.w3.org/2000/svg">'
            . '<script type="text/javascript">window.' . $marker . ' = 1</script>'
            . '</svg>';

        $admin = User::factory()->create(['is_administrator' => true]);
        $owner = User::factory()->create();
        $processRequest = ProcessRequest::factory()->create(['user_id' => $owner->id]);

        $fileApi = $this->postFile(
            $admin,
            '/api/1.0/files?model=' . urlencode(ProcessRequest::class) . '&model_id=' . $processRequest->id,
            'payload.svg',
            $svg,
            'image/svg+xml'
        );
        $requestApi = $this->postFile(
            $owner,
            '/api/1.0/requests/' . $processRequest->id . '/files',
            'request-payload.svg',
            $svg,
            'image/svg+xml'
        );

        $uploads = [
            'file api' => [$fileApi, 'payload.svg'],
            'request files api' => [$requestApi, 'request-payload.svg'],
        ];
        foreach ($uploads as $label => [$response, $fileName]) {
            if (in_array($response->getStatusCode(), [401, 403, 422], true)) {
                $this->assertStringNotContainsString($marker, $this->responseBody($response));
                continue;
            }

            $response->assertStatus(200);
            $uploaded = $this->mediaFromCreateResponse($response, $fileName);
            $stored = Storage::disk('public')->get($uploaded['id'] . '/' . $uploaded['file_name']);
            $this->assertStringContainsString($marker, (string) $stored);

            $guest = $this->guestGet($this->storageUrl($uploaded['id'], $uploaded['file_name']));
            $this->assertNotServedAsActiveContent($guest, $marker, $label . ' storage route');

            $webInline = $this->actingAs($admin, 'web')
                ->get('/files/' . $uploaded['id'] . '/contents?inline=1');
            $this->assertNotServedAsActiveContent($webInline, $marker, $label . ' web inline download');

            $apiInline = $this->actingAs($admin, 'api')
                ->get('/api/1.0/files/' . $uploaded['id'] . '/contents?inline=1');
            $this->assertNotServedAsActiveContent($apiInline, $marker, $label . ' api inline download');
        }
    }

    public function testRequestDocumentIsNotExposedByThePublicStorageLink(): void
    {
        $secret = 'pmDocMarker-public-link';
        $uploaded = $this->uploadThroughRequestFiles('linked-contract.txt', $secret);

        $link = public_path('storage');
        if (!is_link($link)) {
            Artisan::call('storage:link');
        }
        $this->assertTrue(is_link($link), 'The installer creates public/storage via storage:link.');

        $this->assertFileDoesNotExist($link . '/' . $uploaded['id'] . '/' . $uploaded['file_name']);
    }

    private function uploadThroughFileApi(string $name, string $contents): array
    {
        $admin = User::factory()->create(['is_administrator' => true]);
        $owner = User::factory()->create();
        $processRequest = ProcessRequest::factory()->create(['user_id' => $owner->id]);
        $response = $this->postFile(
            $admin,
            '/api/1.0/files?model=' . urlencode(ProcessRequest::class) . '&model_id=' . $processRequest->id,
            $name,
            $contents
        );
        $response->assertStatus(200);

        return $this->mediaFromCreateResponse($response, $name);
    }

    private function uploadThroughRequestFiles(string $name, string $contents, ?User $owner = null): array
    {
        $owner = $owner ?: User::factory()->create();
        $processRequest = ProcessRequest::factory()->create(['user_id' => $owner->id]);
        $response = $this->postFile(
            $owner,
            '/api/1.0/requests/' . $processRequest->id . '/files',
            $name,
            $contents
        );
        $response->assertStatus(200);
        $uploaded = $this->mediaFromCreateResponse($response, $name);
        $uploaded['owner'] = $owner;

        return $uploaded;
    }

    private function postFile(User $user, string $url, string $name, string $contents, ?string $mime = null)
    {
        $file = UploadedFile::fake()->createWithContent($name, $contents, $mime);

        return $this->actingAs($user, 'api')->post($url, [
            'file' => $file,
            'data_name' => $name,
        ]);
    }

    /**
     * @return array{id: int, file_name: string, disk: string}
     */
    private function mediaFromCreateResponse($response, string $fallbackName): array
    {
        $body = $response->json();
        $id = $body['id'] ?? $body['fileUploadId'] ?? null;
        $this->assertNotNull($id, 'Create response did not return a media id.');

        $media = Media::findOrFail($id);
        $fileName = $body['file_name'] ?? $media->file_name;
        $this->assertNotSame('', (string) $fileName);
        $this->assertSame($fallbackName, $media->file_name);

        return [
            'id' => (int) $media->id,
            'file_name' => $media->file_name,
            'disk' => $media->disk,
        ];
    }

    private function storageUrl(int $id, string $fileName): string
    {
        return '/storage/' . $id . '/' . $fileName;
    }

    private function guestGet(string $url)
    {
        Auth::guard('web')->logout();
        Auth::forgetGuards();

        return $this->get($url);
    }

    private function assertPrivateBytesHidden($response, string $secret): void
    {
        $body = $this->responseBody($response);
        $this->assertContains(
            $response->getStatusCode(),
            [302, 401, 403, 404],
            'Private document was not refused. HTTP ' . $response->getStatusCode()
        );
        $this->assertStringNotContainsString($secret, $body);
    }

    private function assertNotServedAsActiveContent($response, string $marker, string $label): void
    {
        $body = $this->responseBody($response);
        $type = strtolower((string) $response->headers->get('content-type'));
        $disposition = strtolower((string) $response->headers->get('content-disposition'));
        $activeType = $this->isActiveContentType($type);
        $inline = !str_contains($disposition, 'attachment');
        $served = $response->getStatusCode() === 200 && str_contains($body, $marker);

        $this->assertFalse(
            $served && $activeType && $inline,
            $label . ' returned script-bearing SVG inline as active content ('
            . $type . ', ' . $disposition . ').'
        );

        if ($served) {
            $this->assertStringContainsString('attachment', $disposition, $label . ' must download, not render, SVG bytes.');
            $this->assertFalse($activeType, $label . ' must not use an active content type, got ' . $type . '.');
        }
    }

    private function isActiveContentType(string $type): bool
    {
        foreach (self::ACTIVE_CONTENT_TYPES as $active) {
            if (str_contains($type, $active)) {
                return true;
            }
        }

        return false;
    }

    private function responseBody($response): string
    {
        $base = $response->baseResponse;
        if ($base instanceof BinaryFileResponse || $base instanceof StreamedResponse) {
            return (string) $response->streamedContent();
        }

        return (string) $response->getContent();
    }
}
