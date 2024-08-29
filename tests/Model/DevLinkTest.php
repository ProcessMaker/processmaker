<?php

namespace Tests\Model;

use ProcessMaker\Models\DevLink;
use Tests\TestCase;

class DevLinkTest extends TestCase
{
    public function testGetClientUrl()
    {
        $devLink = DevLink::factory()->create([
            'url' => 'https://remote-instance.test',
        ]);

        $expectedQueryString = http_build_query([
            'devlink_id' => $devLink->id,
            'redirect_url' => route('devlink.index'),
        ]);

        $this->assertEquals(
            'https://remote-instance.test/admin/devlink/oauth-client?' . $expectedQueryString,
            $devLink->getClientUrl()
        );
    }

    public function testGetOauthRedirectUrl()
    {
        $devLink = DevLink::factory()->create([
            'url' => 'https://remote-instance.test',
            'client_id' => 123,
        ]);

        $actualUrl = $devLink->getOauthRedirectUrl();

        // Refresh devlink to get state created by getOauthRedirectUrl
        $devLink->refresh();
        $state = $devLink->state;

        $expectedQueryString = http_build_query([
            'client_id' => 123,
            'redirect_url' => route('devlink.index'),
            'resource_type' => 'code',
            'state' => $state,
        ]);

        $this->assertEquals(
            $devLink->url . '/oauth/authorize?' . $expectedQueryString,
            $actualUrl,
        );
    }
}
