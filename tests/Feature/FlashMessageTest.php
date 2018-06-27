<?php
namespace Tests\Feature;

use Router;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use ProcessMaker\Model\User;
use ProcessMaker\Model\Role;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class FlashMessageTest extends TestCase
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


    /*one for success_message*/
    public function testSuccessMessage()
    {
      // Create a fake route that flashes a message with a successful alert
      Router::get('/_tests/alert_success_test', function () {
        // Flash a message
          request()->session()->flash('_alert', ['type'=>'success','message'=>'Test Successful Message']);
          return view('layouts.layout');
      })->middleware('web');
      $response = $this->get('/_tests/alert_success_test');
      // Now verify that we see our div alert as well as our success message
      // First check for the div
      $response->assertSee('Test Successful Message');
    }

    /**
     * Tests to ensure that if we do NOT flash an alert to session, no alert is shown
     */
    public function testNoFlashNoSuccessAlert()
    {
      // But we need to ensure we're loading a different route that doesn't reflash
      Router::get('/_tests/alert_success_clear', function () {
         return view('layouts.layout');
      })->middleware('web');
      $response = $this->get('/_tests/alert_success_clear');
      $response->assertDontSee('Test Successful Message');
    }

    public function testErrorMessage()
    {
      // Create a fake route that flashes a message with a error alert
      Router::get('/_tests/alert_failure_test', function () {
        // Flash a message
        request()->session()->flash('_alert', ['type'=>'danger','message'=>'Test Error Message']);          
          return view('layouts.layout');
      })->middleware('web');
      $response = $this->get('/_tests/alert_failure_test');
      // Now verify that we see our div alert as well as our success message
      // First check for the div
      $response->assertSee('Test Error Message');
    }

    /**
     * Tests to ensure that if we do NOT flash an alert to session, no FAILURE alert is shown
     */
    public function testNoFlashNoFailureAlert()
    {
      // But we need to ensure we're loading a different route that doesn't reflash
      Router::get('/_tests/alert_failure_clear', function () {
         return view('layouts.layout');
      })->middleware('web');
      $response = $this->get('/_tests/alert_failure_clear');
      $response->assertDontSee('Test Error Message');
    }

}
