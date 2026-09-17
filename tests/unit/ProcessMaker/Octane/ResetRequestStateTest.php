<?php

declare(strict_types=1);

namespace Tests\Unit\ProcessMaker\Octane;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Laravel\Octane\Events\RequestTerminated;
use Lavary\Menu\Facade as Menu;
use Lavary\Menu\Menu as MenuContract;
use ProcessMaker\Events\RedirectToEvent;
use ProcessMaker\Http\Middleware\GenerateMenus;
use ProcessMaker\Listeners\HandleRedirectListener;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\User;
use ProcessMaker\Octane\ResetRequestState;
use ProcessMaker\Providers\ProcessMakerServiceProvider;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class ResetRequestStateTest extends TestCase
{
    public function test_it_clears_request_timing_before_the_next_request(): void
    {
        DB::select('SELECT 1');

        $this->assertGreaterThan(0, ProcessMakerServiceProvider::getQueryTime());

        $listener = new ResetRequestState();
        $listener->handle();

        $this->assertSame(0.0, ProcessMakerServiceProvider::getQueryTime());
    }

    public function test_it_prevents_redirect_state_from_leaking_into_the_next_request(): void
    {
        Event::fake([RedirectToEvent::class]);

        $redirectListener = new RedirectStateProbe();
        $redirectListener->queue(ProcessRequest::factory()->create());

        $listener = new ResetRequestState();
        $listener->handle();

        HandleRedirectListener::sendRedirectToEvent();

        Event::assertNotDispatched(RedirectToEvent::class);
    }

    public function test_octane_request_termination_automatically_resets_request_state(): void
    {
        Event::fake([RedirectToEvent::class]);

        $redirectListener = new RedirectStateProbe();
        $redirectListener->queue(ProcessRequest::factory()->create());

        event(new RequestTerminated(
            $this->app,
            $this->app,
            Request::create('/first-request'),
            new Response()
        ));

        HandleRedirectListener::sendRedirectToEvent();

        Event::assertNotDispatched(RedirectToEvent::class);
    }

    public function test_generate_menus_does_not_leak_admin_items_to_sso_user_after_octane_reset(): void
    {
        $admin = User::factory()->create(['is_administrator' => true]);
        $ssoUser = User::factory()->create(['is_administrator' => false]);

        Auth::login($admin);
        $this->runGenerateMenus();

        $this->assertTrue($this->menuHasItemTitle('sidebar_admin', __('Users')));

        $listener = new ResetRequestState();
        $listener->handle();
        $this->app->forgetInstance(MenuContract::class);

        Auth::login($ssoUser);
        $this->runGenerateMenus();

        $this->assertFalse($this->menuHasItemTitle('topnav', __('Admin')));
        $this->assertFalse($this->menuHasItemTitle('sidebar_admin', __('Users')));
    }

    private function runGenerateMenus(): void
    {
        $middleware = app(GenerateMenus::class);
        $middleware->handle(Request::create('/'), fn () => response('ok'));
    }

    private function menuHasItemTitle(string $menuName, string $title): bool
    {
        $builder = Menu::get($menuName);

        if ($builder === null) {
            return false;
        }

        foreach ($builder->all() as $item) {
            if ($this->itemHasTitle($item, $title)) {
                return true;
            }
        }

        return false;
    }

    private function itemHasTitle($item, string $title): bool
    {
        if ($item->title === $title) {
            return true;
        }

        foreach ($item->children() as $child) {
            if ($this->itemHasTitle($child, $title)) {
                return true;
            }
        }

        return false;
    }
}

final class RedirectStateProbe extends HandleRedirectListener
{
    public function queue(ProcessRequest $processRequest): void
    {
        $this->setRedirectTo($processRequest, 'processUpdated');
    }
}
