<?php

namespace Tests\Feature\Api;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\GroupMember;
use ProcessMaker\Models\Permission;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\User;
use Spatie\MediaLibrary\Conversions\Jobs\PerformConversionsJob;
use Spatie\MediaLibrary\ResponsiveImages\Jobs\GenerateResponsiveImagesJob;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

/**
 * Spec for DELETE /api/1.0/processes/{process}/media.
 *
 * These tests fail while a user who can view processes, but cannot edit the
 * process, can remove carousel and launchpad images. They pass once deletion
 * is authorized with ProcessPolicy::edit for the process in the URL, including
 * that policy's imported-block lock, and the deleted row is a media item of
 * that same process.
 *
 * Untested sibling: DELETE process_launchpad/{process}/embed removes an embed
 * row under view-process-catalog and is a different route and permission.
 * ProcessController::deleteEmbed is not registered on a route.
 */
class ProcessMediaImageDeleteAuthorizationTest extends TestCase
{
    use RequestHelper;

    public $withPermissions = true;

    private const LEGACY_CAROUSEL_COLLECTION = 'images_carousel';

    protected function withUserSetup()
    {
        $this->user->is_administrator = false;
        $this->user->save();
    }

    /**
     * The PM Blocks package is optional. Load a stand-in and its table so
     * ProcessPolicy::edit can see an imported lock.
     */
    public function setupPmBlockSupport(): void
    {
        $className = 'ProcessMaker\\Package\\PackagePmBlocks\\Models\\PmBlock';
        if (!class_exists($className)) {
            require_once base_path('tests/Stubs/PmBlock.php');
        }

        $schema = Schema::connection('processmaker');
        if ($schema->hasTable('pm_blocks')) {
            return;
        }

        $connection = DB::connection('processmaker');
        if ($connection->transactionLevel() > 0) {
            $connection->commit();
        }

        $schema->create('pm_blocks', function ($table) {
            $table->increments('id');
            $table->unsignedInteger('editing_process_id')->nullable();
            $table->boolean('is_imported_locked')->default(false);
        });

        $connection->beginTransaction();
    }

    public function testViewPermissionCannotDeleteLegacyCarouselImage(): void
    {
        $this->user = $this->userWithPermissions(['view-processes']);
        $process = Process::factory()->create();
        $media = $this->addCarouselImage($process, self::LEGACY_CAROUSEL_COLLECTION);

        $uuid = $this->uuidFromMediaList($process);
        $this->assertSame($media->uuid, $uuid);

        $response = $this->deleteMedia($process, $uuid, $process->id);

        $response->assertForbidden();
        $this->assertMediaRemains($media);
    }

    public function testViewPermissionCannotDeleteLaunchpadCarouselImage(): void
    {
        $this->user = $this->userWithPermissions(['view-processes']);
        $process = Process::factory()->create();
        $collection = $process->uuid . '_images_carousel';
        $media = $this->addCarouselImage($process, $collection);

        $uuid = $this->uuidFromMediaList($process);

        $response = $this->deleteMedia($process, $uuid, $process->id);

        $response->assertForbidden();
        $this->assertSame($media->uuid, $uuid);
        $this->assertMediaRemains($media);
    }

    public function testGroupViewPermissionCannotDeleteCarouselImage(): void
    {
        $this->user = User::factory()->create(['is_administrator' => false]);
        $group = Group::factory()->create();
        GroupMember::factory()->create([
            'member_id' => $this->user->id,
            'member_type' => User::class,
            'group_id' => $group->id,
        ]);
        $group->permissions()->attach(Permission::byName('view-processes'));
        $this->user->invalidatePermissionCache();

        $process = Process::factory()->create();
        $media = $this->addCarouselImage($process, $process->uuid . '_images_carousel');

        $response = $this->deleteMedia($process, $this->uuidFromMediaList($process), $process->id);

        $response->assertForbidden();
        $this->assertMediaRemains($media);
    }

    public function testEditPermissionDeletesUnlockedProcessCarouselImage(): void
    {
        $this->user = $this->userWithPermissions(['edit-processes']);
        $process = Process::factory()->create();
        $media = $this->addCarouselImage($process, $process->uuid . '_images_carousel');

        $response = $this->deleteMedia($process, $media->uuid, $process->id);

        $response->assertNoContent();
        $this->assertMediaDeleted($media);
    }

    public function testEditPermissionCannotDeleteLockedProcessCarouselImage(): void
    {
        $this->user = $this->userWithPermissions(['view-processes', 'edit-processes']);
        $process = Process::factory()->create();
        $media = $this->addCarouselImage($process, $process->uuid . '_images_carousel');
        $this->lockProcess($process);
        $this->assertFalse($this->user->can('edit', $process));

        $response = $this->deleteMedia($process, $media->uuid, $process->id);

        $response->assertForbidden();
        $this->assertMediaRemains($media);
    }

    public function testAuthorizedProcessDoesNotDeleteAnotherProcessImage(): void
    {
        $this->user = $this->userWithPermissions(['view-processes', 'edit-processes']);
        $urlProcess = Process::factory()->create();
        $otherProcess = Process::factory()->create();
        $urlMedia = $this->addCarouselImage(
            $urlProcess,
            $urlProcess->uuid . '_images_carousel'
        );
        $otherMedia = $this->addCarouselImage(
            $otherProcess,
            $otherProcess->uuid . '_images_carousel'
        );

        // The body names the other process and its image. Only the process in
        // the URL may be edited, and only its own media may be removed.
        $this->deleteMedia($urlProcess, $otherMedia->uuid, $otherProcess->id);

        $this->assertMediaRemains($urlMedia);
        $this->assertMediaRemains($otherMedia);
    }

    private function userWithPermissions(array $permissions): User
    {
        $user = User::factory()->create(['is_administrator' => false]);
        $user->giveDirectPermission($permissions);

        return $user->refresh();
    }

    private function addCarouselImage(Process $process, string $collection)
    {
        Bus::fake([
            PerformConversionsJob::class,
            GenerateResponsiveImagesJob::class,
        ]);

        $source = __DIR__ . '/images/640x480.png';
        $copy = tempnam(sys_get_temp_dir(), 'carousel');
        copy($source, $copy);
        $uploadedFile = new UploadedFile($copy, 'carousel.png', 'image/png', null, true);
        $process->addMedia($uploadedFile)->toMediaCollection($collection);

        $media = $process->getFirstMedia($collection);
        $this->assertNotNull($media);
        Storage::disk($media->disk)->assertExists($media->getPathRelativeToRoot());

        return $media;
    }

    private function uuidFromMediaList(Process $process): string
    {
        $response = $this->apiCall('GET', route('api.processes.media', $process));
        $response->assertOk();

        $uuid = $response->json('data.0.media.0.uuid');
        $this->assertNotEmpty($uuid);

        return $uuid;
    }

    private function deleteMedia(Process $process, string $uuid, int $bodyProcessId)
    {
        return $this->apiCall('DELETE', route('api.processes.delete-media', $process), [
            'uuid' => $uuid,
            'id' => $bodyProcessId,
        ]);
    }

    private function lockProcess(Process $process): void
    {
        $className = 'ProcessMaker\\Package\\PackagePmBlocks\\Models\\PmBlock';
        $className::query()->create([
            'editing_process_id' => $process->id,
            'is_imported_locked' => true,
        ]);
        $process->unsetRelation('pmBlock');
    }

    private function assertMediaRemains($media): void
    {
        $this->assertDatabaseHas('media', ['id' => $media->id]);
        Storage::disk($media->disk)->assertExists($media->getPathRelativeToRoot());
    }

    private function assertMediaDeleted($media): void
    {
        $this->assertDatabaseMissing('media', ['id' => $media->id]);
        Storage::disk($media->disk)->assertMissing($media->getPathRelativeToRoot());
    }
}
