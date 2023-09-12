<?php

namespace ProcessMaker\Helpers;

use Illuminate\Foundation\Testing\WithFaker;
use ProcessMaker\Helpers\MobileHelper;
use Tests\TestCase;

class MobileHelperTest extends TestCase
{
    use WithFaker;

    public function getUserAgent($mobile = true)
    {
        if ($mobile) {
            $agent = [
                'Mozilla/5.0 (Android 13; Mobile; rv:109.0) Gecko/117.0 Firefox/117.0',
                'Mozilla/5.0 (Macintosh; Intel Mac OS X 13.5; rv:109.0) Gecko/20100101 Firefox/117.0',
            ];
        } else {
            $agent = [
                'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:109.0) Gecko/20100101 Firefox/117.0',
                'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:109.0) Gecko/20100101 Firefox/117.0',
            ];
        }

        return $this->faker->randomElement($agent);
    }

    public function testIsMobileWithoutCookie()
    {
        $result = MobileHelper::isMobile($this->getUserAgent());
        $this->assertFalse($result);
    }
}
