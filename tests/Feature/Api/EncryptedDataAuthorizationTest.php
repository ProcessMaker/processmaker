<?php

namespace Tests\Feature\Api;

use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use ProcessMaker\Models\EncryptedData;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\GroupMember;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\User;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

/**
 * Authorization for encrypted values must follow the persisted row.
 *
 * checkUserPermission() only inspects the screen id and field name supplied
 * on the request. encryptText() and decryptText() then load or update
 * encrypted_data by the client-supplied uuid. These tests require the row
 * that is read or written to be the row bound to the screen field that was
 * authorized. Re-checking the client-supplied screen field still fails them.
 */
class EncryptedDataAuthorizationTest extends TestCase
{
    use RequestHelper;

    public function test_decrypt_rejects_uuid_created_for_another_screen_with_the_same_field_name(): void
    {
        $victim = User::factory()->create();
        $attacker = User::factory()->create();
        $fieldName = 'form_input_1';
        $victimScreen = $this->screenFor($fieldName, [$victim->id]);
        $attackerScreen = $this->screenFor($fieldName, [$attacker->id]);
        $victimPlaintext = 'victim-screen-value';
        $attackerPlaintext = 'attacker-screen-value';

        $victimUuid = $this->encrypt($victim, $victimScreen, $fieldName, $victimPlaintext);
        $attackerUuid = $this->encrypt($attacker, $attackerScreen, $fieldName, $attackerPlaintext);

        $own = $this->decrypt($attacker, $attackerScreen, $fieldName, $attackerUuid);
        $own->assertOk();
        $this->assertSame($attackerPlaintext, $own->getContent());

        $before = $this->storedRow($victimUuid);
        $cross = $this->decrypt($attacker, $attackerScreen, $fieldName, $victimUuid);

        $this->assertRejected($cross, $victimPlaintext);
        $this->assertRowUnchanged($before);
        $this->assertSame($victimPlaintext, $this->decrypt($victim, $victimScreen, $fieldName, $victimUuid)->getContent());
    }

    public function test_decrypt_rejects_uuid_when_the_authorized_field_name_differs(): void
    {
        $victim = User::factory()->create();
        $attacker = User::factory()->create();
        $victimScreen = $this->screenFor('victim_secret', [$victim->id]);
        $attackerScreen = $this->screenFor('attacker_secret', [$attacker->id]);
        $victimPlaintext = 'victim-field-value';

        $victimUuid = $this->encrypt($victim, $victimScreen, 'victim_secret', $victimPlaintext);
        $attackerUuid = $this->encrypt($attacker, $attackerScreen, 'attacker_secret', 'attacker-field-value');

        $own = $this->decrypt($attacker, $attackerScreen, 'attacker_secret', $attackerUuid);
        $own->assertOk();
        $this->assertSame('attacker-field-value', $own->getContent());

        $before = $this->storedRow($victimUuid);
        $cross = $this->decrypt($attacker, $attackerScreen, 'attacker_secret', $victimUuid);

        $this->assertRejected($cross, $victimPlaintext);
        $this->assertRowUnchanged($before);
    }

    public function test_encrypt_does_not_overwrite_uuid_created_for_another_screen(): void
    {
        $victim = User::factory()->create();
        $attacker = User::factory()->create();
        $fieldName = 'form_input_1';
        $victimScreen = $this->screenFor($fieldName, [$victim->id]);
        $attackerScreen = $this->screenFor($fieldName, [$attacker->id]);
        $victimPlaintext = 'victim-screen-value';

        $victimUuid = $this->encrypt($victim, $victimScreen, $fieldName, $victimPlaintext);
        $this->encrypt($attacker, $attackerScreen, $fieldName, 'attacker-screen-value');
        $before = $this->storedRow($victimUuid);

        $overwrite = $this->encryptResponse($attacker, $attackerScreen, $fieldName, 'attacker-replacement-value', $victimUuid);

        $this->assertRejected($overwrite, 'attacker-replacement-value');
        $this->assertRowUnchanged($before);
        $this->assertSame($victimPlaintext, $this->decrypt($victim, $victimScreen, $fieldName, $victimUuid)->getContent());
    }

    public function test_encrypt_does_not_overwrite_uuid_for_a_different_field_name(): void
    {
        $victim = User::factory()->create();
        $attacker = User::factory()->create();
        $victimScreen = $this->screenFor('victim_secret', [$victim->id]);
        $attackerScreen = $this->screenFor('attacker_secret', [$attacker->id]);
        $victimPlaintext = 'victim-field-value';

        $victimUuid = $this->encrypt($victim, $victimScreen, 'victim_secret', $victimPlaintext);
        $before = $this->storedRow($victimUuid);

        $overwrite = $this->encryptResponse(
            $attacker,
            $attackerScreen,
            'attacker_secret',
            'attacker-replacement-value',
            $victimUuid
        );

        $this->assertRejected($overwrite, 'attacker-replacement-value');
        $this->assertRowUnchanged($before);
        $this->assertSame(
            $victimPlaintext,
            $this->decrypt($victim, $victimScreen, 'victim_secret', $victimUuid)->getContent()
        );
    }

    public function test_group_member_cannot_decrypt_another_screens_uuid(): void
    {
        $victim = User::factory()->create();
        $attacker = User::factory()->create();
        $group = Group::factory()->create();
        GroupMember::factory()->create([
            'group_id' => $group->id,
            'member_id' => $attacker->id,
            'member_type' => User::class,
        ]);

        $fieldName = 'form_input_1';
        $victimScreen = $this->screenFor($fieldName, [$victim->id]);
        $attackerScreen = $this->screenFor($fieldName, [], [$group->id]);
        $victimPlaintext = 'victim-group-screen-value';

        $victimUuid = $this->encrypt($victim, $victimScreen, $fieldName, $victimPlaintext);
        $attackerUuid = $this->encrypt($attacker, $attackerScreen, $fieldName, 'attacker-group-value');

        $own = $this->decrypt($attacker, $attackerScreen, $fieldName, $attackerUuid);
        $own->assertOk();
        $this->assertSame('attacker-group-value', $own->getContent());

        $before = $this->storedRow($victimUuid);
        $cross = $this->decrypt($attacker, $attackerScreen, $fieldName, $victimUuid);

        $this->assertRejected($cross, $victimPlaintext);
        $this->assertRowUnchanged($before);
    }

    public function test_assignee_can_decrypt_and_update_uuid_for_the_same_screen_field(): void
    {
        $owner = User::factory()->create();
        $coworker = User::factory()->create();
        $fieldName = 'form_input_1';
        $screen = $this->screenFor($fieldName, [$owner->id, $coworker->id]);

        $uuid = $this->encrypt($owner, $screen, $fieldName, 'original-shared-value');

        $coworkerRead = $this->decrypt($coworker, $screen, $fieldName, $uuid);
        $coworkerRead->assertOk();
        $this->assertSame('original-shared-value', $coworkerRead->getContent());

        $updated = $this->encryptResponse($owner, $screen, $fieldName, 'updated-shared-value', $uuid);
        $updated->assertOk();
        $this->assertSame($uuid, trim($updated->getContent(), "\" \n"));

        $reread = $this->decrypt($coworker, $screen, $fieldName, $uuid);
        $reread->assertOk();
        $this->assertSame('updated-shared-value', $reread->getContent());
    }

    public function test_unassigned_user_cannot_decrypt_with_the_victim_screen_and_field(): void
    {
        $victim = User::factory()->create();
        $stranger = User::factory()->create();
        $screen = $this->screenFor('form_input_1', [$victim->id]);
        $plaintext = 'victim-only-value';
        $uuid = $this->encrypt($victim, $screen, 'form_input_1', $plaintext);
        $before = $this->storedRow($uuid);

        $response = $this->decrypt($stranger, $screen, 'form_input_1', $uuid);

        $this->assertRejected($response, $plaintext);
        $this->assertRowUnchanged($before);
    }

    private function screenFor(string $fieldName, array $userIds, array $groupIds = []): Screen
    {
        return Screen::factory()->create([
            'config' => [[
                'name' => 'Encrypted page',
                'items' => [[
                    'component' => 'FormInput',
                    'config' => [
                        'name' => $fieldName,
                        'label' => $fieldName,
                        'encryptedConfig' => [
                            'encrypted' => true,
                            'assignments' => [
                                'users' => array_values($userIds),
                                'groups' => array_values($groupIds),
                            ],
                        ],
                    ],
                ]],
            ]],
        ]);
    }

    private function encrypt(User $user, Screen $screen, string $fieldName, string $plainText, ?string $uuid = null): string
    {
        $response = $this->encryptResponse($user, $screen, $fieldName, $plainText, $uuid);
        $response->assertOk();
        $uuid = trim($response->getContent(), "\" \n");
        $this->assertTrue(Str::isUuid($uuid), $response->getContent());

        return $uuid;
    }

    private function encryptResponse(
        User $user,
        Screen $screen,
        string $fieldName,
        string $plainText,
        ?string $uuid = null
    ): TestResponse {
        $payload = [
            'field_name' => $fieldName,
            'plain_text' => $plainText,
            'screen_id' => $screen->id,
        ];
        if ($uuid !== null) {
            $payload['uuid'] = $uuid;
        }

        return $this->callAs($user, 'POST', 'api.encrypted_data.encrypt_text', $payload);
    }

    private function decrypt(User $user, Screen $screen, string $fieldName, string $uuid): TestResponse
    {
        return $this->callAs($user, 'POST', 'api.encrypted_data.decrypt_text', [
            'uuid' => $uuid,
            'field_name' => $fieldName,
            'screen_id' => $screen->id,
        ]);
    }

    private function callAs(User $user, string $method, string $routeName, array $data): TestResponse
    {
        $url = preg_replace('/^.*\/api\//i', '', route($routeName));

        return $this->actingAs($user, 'api')->json($method, '/api/' . $url, $data);
    }

    private function storedRow(string $uuid): EncryptedData
    {
        return EncryptedData::where('uuid', $uuid)->firstOrFail();
    }

    private function assertRowUnchanged(EncryptedData $before): void
    {
        $after = $this->storedRow($before->uuid);
        $this->assertSame($before->field_name, $after->field_name);
        $this->assertSame($before->iv, $after->iv);
        $this->assertSame($before->data, $after->data);
    }

    private function assertRejected(TestResponse $response, string $secret): void
    {
        $this->assertGreaterThanOrEqual(400, $response->getStatusCode(), $response->getContent());
        $this->assertLessThan(500, $response->getStatusCode(), $response->getContent());
        $this->assertStringNotContainsString($secret, $response->getContent());
    }
}
