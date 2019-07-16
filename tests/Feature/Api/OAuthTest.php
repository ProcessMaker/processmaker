<?php

namespace Tests\Feature;

use Tests\TestCase;
use Tests\Feature\Shared\RequestHelper;
use Illuminate\Http\Response;

class OAuthTest extends TestCase
{
    use RequestHelper;

    public $json = null;

    public function withUserSetup()
    {
        $response = $this->actingAs($this->user, 'api')
                         ->json('POST', '/oauth/clients', []);

        $this->assertEquals('The name field is required.', $response->json()['errors']['name'][0]);
        $this->assertEquals('The types field is required.', $response->json()['errors']['types'][0]);
        
        $response = $this->actingAs($this->user, 'api')
                         ->json('POST', '/oauth/clients', [ 'name' => 'foo', 'types' => []]);

        $this->assertEquals('The types must have at least 1 items.', $response->json()['errors']['types'][0]);
        
        $response = $this->actingAs($this->user, 'api')
                         ->json('POST', '/oauth/clients', [ 'name' => 'foo', 'types' => ['authorization_code_grant']]);

        $this->assertEquals('The redirect field is required.', $response->json()['errors']['redirect'][0]);
        
        $response = $this->actingAs($this->user, 'api')
                         ->json('POST', '/oauth/clients', ['name' => 'test', 'redirect' => 'http://test.com', 'types' => ['authorization_code_grant']]);
        
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
        $this->assertFalse($this->json['password_client']);
        $this->assertFalse($this->json['personal_access_client']);
        $response = $this->actingAs($this->user, 'api')
                    ->json(
                        'PUT',
                        '/oauth/clients/' . $this->json['id'],
                        [
                            'name' => 'test123',
                            'redirect' => 'http://test.com/foo',
                            'types' => ['authorization_code_grant', 'password_client', 'personal_access_client']
                        ]
                    );
        $response->assertStatus(200);

        $response = $this->actingAs($this->user, 'api')
                         ->json('GET', '/oauth/clients');

        $json = $response->json();
        $this->assertEquals($this->json['id'], $json[0]['id']);
        $this->assertEquals('test123', $json[0]['name']);
        $this->assertEquals('http://test.com/foo', $json[0]['redirect']);
        $this->assertFalse($json[0]['revoked']);
        $this->assertTrue($json[0]['password_client']);
        $this->assertTrue($json[0]['personal_access_client']);
    }

    /**
     * Test that the client can be deleted (soft delete)
     *
     * @return void
     */
    public function testDelete()
    {
        $this->actingAs($this->user, 'api')
             ->json('POST', '/oauth/clients', [
                 'name' => 'other',
                 'redirect' => 'http://other.net',
                 'types' => ['authorization_code_grant']
            ]);
        
        $response = $this->actingAs($this->user, 'api')
                         ->json('GET', '/oauth/clients');

        $this->assertCount(2, $response->json());

        $response = $this->actingAs($this->user, 'api')
                    ->json(
                        'DELETE',
                        '/oauth/clients/' . $this->json['id']
                    );
        $response->assertStatus(Response::HTTP_NO_CONTENT);
        
        $response= $this->actingAs($this->user, 'api')
                        ->json('GET', '/oauth/clients');

        $json = $response->json();
        $this->assertCount(1, $json);
        $this->assertEquals('other', $json[0]['name']);
        $this->assertEquals('http://other.net', $json[0]['redirect']);
    }
}
