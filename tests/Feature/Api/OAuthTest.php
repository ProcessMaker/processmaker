<?php

namespace Tests\Feature;

use Tests\TestCase;
// use Illuminate\Foundation\Testing\WithFaker;
// use ProcessMaker\Models\User;
use Tests\Feature\Shared\RequestHelper;

class OAuthTest extends TestCase
{
    use RequestHelper;

    public $json = null;

    public function withUserSetup()
    {
        $response = $this->actingAs($this->user, 'api')
                         ->json('POST', '/oauth/clients', ['name' => 'test', 'redirect' => 'http://test.com']);
        $response->assertStatus(201);
        $this->json = $response->json();
    }

    /**
     * Test to create and list oauth clients
     *
     * @return void
     */
    public function testCreateAndList()
    {
        $response = $this->actingAs($this->user, 'api')
                         ->json('GET', '/oauth/clients');

        $response->assertStatus(200);
        $json = $response->json();
        $this->assertEquals('test', $json[0]['name']);
        $this->assertEquals('http://test.com', $json[0]['redirect']);
        $this->assertFalse($json[0]['revoked']);
    }

    /**
     * Test editing an oauth client
     *
     * @return void
     */
    public function testEdit()
    {
        $response = $this->actingAs($this->user, 'api')
                    ->json(
                        'PUT',
                        '/oauth/clients/' . $this->json['id'],
                        ['name' => 'test123', 'redirect' => 'http://test.com/foo']
                    );
        $response->assertStatus(200);

        $response = $this->actingAs($this->user, 'api')
                         ->json('GET', '/oauth/clients');

        $json = $response->json();
        $this->assertEquals($this->json['id'], $json[0]['id']);
        $this->assertEquals('test123', $json[0]['name']);
        $this->assertEquals('http://test.com/foo', $json[0]['redirect']);
        $this->assertFalse($json[0]['revoked']);

    }

    /**
     * Test that the client can be deleted (soft delete)
     *
     * @return void
     */
    public function testDelete()
    {
        $this->actingAs($this->user, 'api')
             ->json('POST', '/oauth/clients', ['name' => 'other', 'redirect' => 'http://other.net']);
        
        $response = $this->actingAs($this->user, 'api')
                         ->json('GET', '/oauth/clients');

        $this->assertCount(2, $response->json());

        $response = $this->actingAs($this->user, 'api')
                    ->json(
                        'DELETE',
                        '/oauth/clients/' . $this->json['id']
                    );
        $response->assertStatus(200);
        
        $response= $this->actingAs($this->user, 'api')
                        ->json('GET', '/oauth/clients');

        $json = $response->json();
        $this->assertCount(1, $json);
        $this->assertEquals('other', $json[0]['name']);
        $this->assertEquals('http://other.net', $json[0]['redirect']);
    }
}
