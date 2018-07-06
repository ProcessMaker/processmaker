<?php
namespace Tests\Feature;

use Route;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use ProcessMaker\Model\User;
use ProcessMaker\Model\Role;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProfileTest extends TestCase
{

  /**
   *  Init data user and process
   */
  protected function setUp()
  {
      parent::setUp();
      $user = factory(User::class)->create();
      Auth::login($user);
  }

  public function testProfile()
  {
    Auth::login(User::first());

    $response = $this->get('/admin/profile');

    $response->assertStatus(200);

  }

}
