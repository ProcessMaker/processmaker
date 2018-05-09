<?php
namespace Tests\Feature\Middleware;

use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use ProcessMaker\Http\Middleware\GenerateApiToken;
use ProcessMaker\Model\User;
use Tests\TestCase;
use Router;

class GenerateApiTokenMiddlewareTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Test to ensure an API token is generated for the user to utilize in
     * web requests.
     */
    public function testApiTokenGenerated()
    {
        $user = factory(User::class)->create();
        // Set our user
        Auth::login($user);
        Router::get('/_tests/test', function() {
            return 'completed';
        })->middleware([
            StartSession::class,
            GenerateApiToken::class,
        ]);

        $response = $this->get('_tests/test');
        $response->assertStatus(200);
        $response->assertSessionHas('apiToken');
        // Check for access token in tables
        $this->assertDatabaseHas('oauth_access_tokens', [
            'user_id' => $user->id
        ]);
        $response->assertSee('completed');
    }

}