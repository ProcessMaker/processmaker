<?php
namespace Tests\Feature;

use Route;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use ProcessMaker\Model\User;

class FlashMessageTest extends TestCase
{
    /*one for success_message*/
    public function testSuccessMessage()
    {
      Auth::login(User::first());
      // Create a fake route that flashes a message with a successful alert
      Route::get('/_tests/alert_success_test', function () {
        // Flash a message
          request()->session()->flash('alert', [
            // Success will be true, failure will be false
            'success' => true,
            'message' => 'Test Successful Message'
          ]);
          return view('layouts.layout');
      })->middleware('web');
      $response = $this->get('/_tests/alert_success_test');
      // Now verify that we see our div alert as well as our success message
      // First check for the div
      $response->assertSee('<div id="app-alert" class="alert alert-success alert-dismissible fade show" role="alert">');
      // Now check to ensure our message text is also there
      $response->assertSee('<strong>Test Successful Message</strong>');
    }

    /**
     * Tests to ensure that if we do NOT flash an alert to session, no alert is shown
     */
    public function testNoFlashNoSuccessAlert()
    {
      Auth::login(User::first());
      // But we need to ensure we're loading a different route that doesn't reflash
      Route::get('/_tests/alert_success_clear', function () {
         return view('layouts.layout');
      })->middleware('web');
      $response = $this->get('/_tests/alert_success_clear');
      $response->assertDontSee('<div id="app-alert" class="alert alert-success alert-dismissible fade show" role="alert">');
    }

    public function testErrorMessage()
    {
      // Login
      Auth::login(User::first());
      // Create a fake route that flashes a message with a error alert
      Route::get('/_tests/alert_failure_test', function () {
        // Flash a message
          request()->session()->flash('alert', [
            // Success will be true, failure will be false
            'success' => false,
            'message' => 'Test Error Message'
          ]);
          return view('layouts.layout');
      })->middleware('web');
      $response = $this->get('/_tests/alert_failure_test');
      // Now verify that we see our div alert as well as our success message
      // First check for the div
      $response->assertSee('<div id="app-alert" class="alert alert-danger alert-dismissible fade show" role="alert">');
      // Now check to ensure our message text is also there
      $response->assertSee('<strong>Test Error Message</strong>');
    }

    /**
     * Tests to ensure that if we do NOT flash an alert to session, no FAILURE alert is shown
     */
    public function testNoFlashNoFailureAlert()
    {
      // Login
      Auth::login(User::first());
      // But we need to ensure we're loading a different route that doesn't reflash
      Route::get('/_tests/alert_failure_clear', function () {
         return view('layouts.layout');
      })->middleware('web');
      $response = $this->get('/_tests/alert_failure_clear');
      $response->assertDontSee('<div id="app-alert" class="alert alert-failure alert-dismissible fade show" role="alert">');
    }
}
