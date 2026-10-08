<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use Illuminate\Support\Facades\Queue;
use Laravel\Scout\Jobs\MakeSearchable;
use Laravel\Scout\Jobs\RemoveFromSearch;
use ProcessMaker\Cache\Settings\SettingCacheFactory;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\Setting;
use Tests\TestCase;

class IndexedSearchScoutSyncTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['scout.queue' => true]);
    }

    private function setIndexedSearch(bool $enabled): void
    {
        $setting = Setting::updateOrCreate(
            ['key' => 'indexed-search'],
            ['config' => ['enabled' => $enabled]]
        );

        $settingCache = SettingCacheFactory::getSettingsCache();
        $settingKey = $settingCache->createKey(['key' => 'indexed-search']);
        $settingCache->set($settingKey, $setting->fresh());
    }

    public function test_was_searchable_hooks_follow_indexed_search_setting(): void
    {
        $this->setIndexedSearch(false);
        $request = ProcessRequest::factory()->create();
        $token = ProcessRequestToken::factory()->create([
            'process_request_id' => $request->id,
            'element_type' => 'task',
        ]);

        $this->assertFalse($request->shouldBeSearchable());
        $this->assertFalse($request->wasSearchableBeforeUpdate());
        $this->assertFalse($request->wasSearchableBeforeDelete());
        $this->assertFalse($token->shouldBeSearchable());
        $this->assertFalse($token->wasSearchableBeforeUpdate());
        $this->assertFalse($token->wasSearchableBeforeDelete());

        $this->setIndexedSearch(true);

        $this->assertTrue($request->shouldBeSearchable());
        $this->assertTrue($request->wasSearchableBeforeUpdate());
        $this->assertTrue($request->wasSearchableBeforeDelete());
        $this->assertTrue($token->fresh()->shouldBeSearchable());
        $this->assertTrue($token->fresh()->wasSearchableBeforeUpdate());
        $this->assertTrue($token->fresh()->wasSearchableBeforeDelete());
    }

    public function test_save_does_not_dispatch_remove_from_search_when_indexed_search_is_disabled(): void
    {
        $this->setIndexedSearch(false);
        $request = ProcessRequest::factory()->create();
        $token = ProcessRequestToken::factory()->create([
            'process_request_id' => $request->id,
            'element_type' => 'task',
        ]);

        Queue::fake();

        $request->name = 'Updated request';
        $request->save();
        $token->element_name = 'Updated token';
        $token->save();

        Queue::assertNotPushed(RemoveFromSearch::class);
        Queue::assertNotPushed(MakeSearchable::class);
    }

    public function test_save_indexes_when_indexed_search_is_enabled(): void
    {
        $this->setIndexedSearch(true);
        $request = ProcessRequest::factory()->create();
        $token = ProcessRequestToken::factory()->create([
            'process_request_id' => $request->id,
            'element_type' => 'task',
        ]);

        Queue::fake();

        $request->name = 'Searchable request';
        $request->save();
        $token->element_name = 'Searchable token';
        $token->save();

        Queue::assertPushed(MakeSearchable::class);
        Queue::assertNotPushed(RemoveFromSearch::class);
    }

    public function test_delete_still_removes_from_search_when_indexed_search_is_enabled(): void
    {
        $this->setIndexedSearch(true);
        $request = ProcessRequest::factory()->create();

        Queue::fake();
        $request->delete();

        Queue::assertPushed(RemoveFromSearch::class);
    }

    public function test_delete_does_not_dispatch_remove_from_search_when_indexed_search_is_disabled(): void
    {
        $this->setIndexedSearch(false);
        $request = ProcessRequest::factory()->create();

        Queue::fake();
        $request->delete();

        Queue::assertNotPushed(RemoveFromSearch::class);
    }
}
