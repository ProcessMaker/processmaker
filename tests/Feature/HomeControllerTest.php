<?php

namespace Tests\Feature;

use ProcessMaker\Models\Group;
use ProcessMaker\Models\User;
use ProcessMaker\Package\PackageDynamicUI\Models\DynamicUI;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class HomeControllerTest extends TestCase
{
    use RequestHelper;

    /**
     * Sample deep-link URL used as the "originally requested" page for the
     * redirect-to-intended tests.
     */
    private const INTENDED_DEEP_LINK = '/designer/scripts/123/edit';

    protected function setUp(): void
    {
        parent::setUp();
        // skip if package-dynamic-ui is not installed
        if (!hasPackage('package-dynamic-ui')) {
            $this->markTestSkipped('package-dynamic-ui is not installed');
        }
    }

    /** @test */
    public function testRedirectsToLoginWhenNotAuthenticated()
    {
        $response = $this->get('/');
        $response->assertRedirect('/login');
    }

    /** @test */
    public function testRedirectsToCustomDashboardWhenUserHasDashboard()
    {
        $user = User::factory()->create();

        // Create a custom dashboard for the user
        DynamicUI::create([
            'type' => 'DASHBOARD',
            'assignable_id' => $user->id,
            'assignable_type' => User::class,
            'homepage' => '/custom-dashboard',
        ]);

        $response = $this->actingAs($user)->get('/');
        $response->assertRedirect('/custom-dashboard');
    }

    /** @test */
    public function testRedirectsToCustomDashboardWhenGroupHasDashboard()
    {
        $user = User::factory()->create();
        $group = Group::factory()->create();
        $user->groups()->attach($group->id);

        // Create a custom dashboard for the group
        DynamicUI::create([
            'type' => 'DASHBOARD',
            'assignable_id' => $group->id,
            'assignable_type' => Group::class,
            'homepage' => '/group-dashboard',
        ]);

        $response = $this->actingAs($user)->get('/');
        $response->assertRedirect('/group-dashboard');
    }

    /** @test */
    public function testRedirectsToTasksOnMobileWithoutCustomDashboard()
    {
        $user = User::factory()->create();

        // Mock MobileHelper to return true
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.0.3 Mobile/15E148 Safari/604.1';
        $_COOKIE['isMobile'] = 'true';

        $response = $this->actingAs($user)->get('/');
        $response->assertRedirect('/tasks');

        unset($_SERVER['HTTP_USER_AGENT']);
        unset($_COOKIE['isMobile']);
    }

    /** @test */
    public function testRedirectsToInboxOnDesktopWithoutCustomDashboard()
    {
        $user = User::factory()->create();

        // Mock MobileHelper to return false
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.114 Safari/537.36';
        $_COOKIE['isMobile'] = 'false';

        $response = $this->actingAs($user)->get('/');
        $response->assertRedirect('/inbox');

        unset($_SERVER['HTTP_USER_AGENT']);
        unset($_COOKIE['isMobile']);
    }

    /** @test */
    public function testRedirectsToUserUrlRedirect()
    {
        $user = User::factory()->create();

        // Create a custom dashboard for the user
        DynamicUI::create([
            'type' => 'URL',
            'assignable_id' => $user->id,
            'assignable_type' => User::class,
            'homepage' => 'https://processmaker.com/home',
        ]);

        $response = $this->actingAs($user)->get('/');
        $response->assertRedirect('https://processmaker.com/home');
    }

    /** @test */
    public function testRedirectsToGroupUrlRedirect()
    {
        $user = User::factory()->create();
        $group = Group::factory()->create();
        $user->groups()->attach($group->id);

        // Create a custom dashboard for the group
        DynamicUI::create([
            'type' => 'URL',
            'assignable_id' => $group->id,
            'assignable_type' => Group::class,
            'homepage' => 'https://processmaker.com/home',
        ]);

        $response = $this->actingAs($user)->get('/');
        $response->assertRedirect('https://processmaker.com/home');
    }

    /** @test */
    public function testRedirectToIntendedHonorsCookie()
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)
            ->withCookie('processmaker_intended', self::INTENDED_DEEP_LINK)
            ->get(route('redirect_to_intended'));
        $response->assertRedirect(self::INTENDED_DEEP_LINK);
    }

    /**
     * Regression test for the SAML "land on home instead of intended URL"
     * bug: when the user comes back from the SSO callback through the
     * /redirect-to-intended recovery hop with a valid `processmaker_intended`
     * cookie, the cookie wins -- the configured Dynamic UI home page does
     * not preempt it.
     *
     * @test
     */
    public function testRedirectToIntendedCookieTakesPrecedenceOverDynamicUiHome()
    {
        $user = User::factory()->create();

        DynamicUI::create([
            'type' => 'URL',
            'assignable_id' => $user->id,
            'assignable_type' => User::class,
            'homepage' => 'https://processmaker.com/configured-landing',
        ]);

        $response = $this->actingAs($user)
            ->withCookie('processmaker_intended', self::INTENDED_DEEP_LINK)
            ->get(route('redirect_to_intended'));

        $response->assertRedirect(self::INTENDED_DEEP_LINK);
    }

    /**
     * The intended-URL cookie is single-use: once we've consumed it we must
     * tell the browser to drop it, otherwise it would keep deflecting every
     * subsequent SSO callback to the same stale URL.
     *
     * @test
     */
    public function testRedirectToIntendedClearsCookieAfterUse()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->withCookie('processmaker_intended', self::INTENDED_DEEP_LINK)
            ->get(route('redirect_to_intended'));

        $response->assertRedirect(self::INTENDED_DEEP_LINK);
        $response->assertCookieExpired('processmaker_intended');
    }

    /**
     * When there's no intended URL at all we still need to send the user somewhere.
     * With no dashboard configured, DynamicUI::getHomePage() falls through to route('home')
     * which HomeController::index() will then re-route to /inbox (or /tasks on mobile) on the next request.
     *
     * @test
     */
    public function testRedirectToIntendedFallsBackToHomeWhenNoCookieAndNoDashboard()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('redirect_to_intended'));

        $response->assertRedirect(route('home'));
    }
}
