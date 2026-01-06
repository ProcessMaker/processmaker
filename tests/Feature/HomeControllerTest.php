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

    protected function setUp(): void
    {
        parent::setUp();
        // skip if package-dynamic-ui is not installed
        if (!hasPackage('package-dynamic-ui')) {
            $this->markTestSkipped('package-dynamic-ui is not installed');
        }
    }

    /** @test */
    public function testRedirectsToLoginWhenNotAuthenticated(): void
    {
        $response = $this->get('/');
        $response->assertRedirect('/login');
    }

    /** @test */
    public function testRedirectsToCustomDashboardWhenUserHasDashboard(): void
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
    public function testRedirectsToCustomDashboardWhenGroupHasDashboard(): void
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
    public function testRedirectsToTasksOnMobileWithoutCustomDashboard(): void
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
    public function testRedirectsToInboxOnDesktopWithoutCustomDashboard(): void
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
    public function testRedirectsToUserUrlRedirect(): void
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
    public function testRedirectsToGroupUrlRedirect(): void
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
}
