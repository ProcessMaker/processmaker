<?php

namespace Tests\Model;

use ProcessMaker\Models\BundleSetting;
use ProcessMaker\Models\User;
use Tests\TestCase;

class BundleSettingTest extends TestCase
{
    public function testExportSupportsArrayJsonStringAndNullConfigurations()
    {
        $user = User::factory()->create();
        $selectedUser = ['id' => [$user->id]];
        $configurations = [
            'array' => $selectedUser,
            'JSON string' => json_encode($selectedUser),
            'null' => null,
        ];

        foreach ($configurations as $description => $config) {
            $setting = new BundleSetting([
                'setting' => 'users',
                'config' => $config,
            ]);

            $this->assertSame(
                $config === null ? [] : $selectedUser,
                $setting->configAsArray(),
                "Failed to normalize the {$description} configuration.",
            );
            $this->assertTrue(
                $setting->export()->contains(
                    fn ($payload) => ($payload['root'] ?? null) === $user->uuid,
                ),
                "Failed to export the {$description} configuration.",
            );
        }
    }
}
