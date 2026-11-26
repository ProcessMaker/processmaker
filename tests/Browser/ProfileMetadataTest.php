<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\DataProvider;
use ProcessMaker\Models\User;
use Tests\DuskTestCase;

class ProfileMetadataTest extends DuskTestCase
{
    public static function profilePermissions(): array
    {
        return [
            'personal profile only' => [false, ['edit-personal-profile']],
            'administrator' => [true, ['edit-personal-profile']],
            'delegated user editor' => [false, ['edit-personal-profile', 'edit-users']],
        ];
    }

    #[DataProvider('profilePermissions')]
    public function testSavingProfilePreservesMetadata(bool $administrator, array $permissions): void
    {
        $metadata = [
            'storedFlag' => false,
            'storedCount' => 0,
            'storedLabel' => '',
            'disableRecommendations' => true,
        ];
        $user = User::factory()->create([
            'status' => 'ACTIVE',
            'is_administrator' => $administrator,
            'meta' => $metadata,
        ]);
        $user->giveDirectPermission($permissions);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/profile/edit')
                ->waitFor('#editProfile #firstname')
                ->type('#editProfile #firstname', 'Updated Profile')
                ->press('Save')
                ->waitForText('Your profile was saved.')
                ->refresh()
                ->waitFor('#editProfile #firstname')
                ->assertInputValue('#firstname', 'Updated Profile');
        });

        $savedMetadata = (array) $user->fresh()->meta;
        ksort($metadata);
        ksort($savedMetadata);
        $this->assertSame($metadata, $savedMetadata);
    }
}
