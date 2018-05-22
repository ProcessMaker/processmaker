<?php
namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class FlashMessageTest extends TestCase
{
    /*one for success_message*/
    public function testSuccessMessage()
    {
      $response = $this->get('/');
      $response->assertStatus(302);
      /*set a flash session*/
      $response = $this->withSession(['success_message' => 'testMessage'])->get('/');
      /*load page and check for session text*/
      $response->assertSeeText('testMessage');
      /*reload the page again and make sure its gone*/
      $response = $this->get('/');
      $response->assertDontSeeText('testMessage');
    }
    public function testErrorMessage()
    {
      $response = $this->get('/');
      $response->assertStatus(302);
      /*set a flash session*/
      $response = $this->withSession(['error_message' => 'testMessage'])->get('/');
      /*load page and check for session text*/
      $response->assertSeeText('testMessage');
      /*reload the page again and make sure its gone*/
      $response = $this->get('/');
      $response->assertDontSeeText('testMessage');
    }
}
