<?php

namespace Tests\Feature\Api\V1_1;

use ProcessMaker\Models\Clipboard;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class ClipboardControllerTest extends TestCase
{
    use RequestHelper;

    public function test_get_by_user_creates_empty_clipboard_for_new_user(): void
    {
        Clipboard::where('user_id', $this->user->id)->delete();

        $response = $this->apiCall('GET', '/api/1.1/clipboard/get_by_user');

        $response->assertStatus(200)
            ->assertJson([
                'user_id' => $this->user->id,
                'type' => 'FORM',
                'config' => [],
            ]);

        $this->assertDatabaseHas('clipboards', [
            'user_id' => $this->user->id,
            'type' => 'FORM',
        ]);
        $this->assertSame(1, Clipboard::where('user_id', $this->user->id)->count());
    }

    public function test_get_by_user_returns_existing_clipboard_without_creating_duplicate(): void
    {
        Clipboard::where('user_id', $this->user->id)->delete();

        $clipboard = Clipboard::factory()->create([
            'user_id' => $this->user->id,
            'config' => [
                [
                    'component' => 'FormInput',
                    'config' => ['label' => 'Existing Clipboard Item'],
                ],
            ],
            'type' => 'FORM',
        ]);

        $response = $this->apiCall('GET', '/api/1.1/clipboard/get_by_user');

        $response->assertStatus(200)
            ->assertJson([
                'id' => $clipboard->id,
                'user_id' => $this->user->id,
                'type' => 'FORM',
                'config' => $clipboard->config,
            ]);

        $this->assertSame(1, Clipboard::where('user_id', $this->user->id)->count());
    }
}
