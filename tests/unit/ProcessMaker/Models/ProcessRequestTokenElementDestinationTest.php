<?php

namespace Tests\Unit\ProcessMaker\Models;

use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\User;
use ReflectionClass;
use Tests\TestCase;

class ProcessRequestTokenElementDestinationTest extends TestCase
{
    /**
     * Invoke a private method on an object.
     */
    private function invokePrivateMethod(object $object, string $methodName, array $args = []): mixed
    {
        $reflection = new ReflectionClass($object);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $args);
    }

    /**
     * Test getElementDestinationMustacheContext returns context with APP_URL, _request, _user and process data.
     */
    public function testGetElementDestinationMustacheContextReturnsExpectedKeys(): void
    {
        $user = User::factory()->create(['status' => 'ACTIVE']);
        $process = Process::factory()->create();
        $request = ProcessRequest::factory()->create([
            'process_id' => $process->id,
            'user_id' => $user->id,
            'data' => ['processVar' => 'value123'],
        ]);

        $token = ProcessRequestToken::factory()->create([
            'process_id' => $process->id,
            'process_request_id' => $request->id,
            'user_id' => $user->id,
            'element_id' => 'end_1',
            'element_type' => 'end_event',
        ]);

        $context = $this->invokePrivateMethod($token, 'getElementDestinationMustacheContext');

        $this->assertIsArray($context);
        $this->assertArrayHasKey('APP_URL', $context);
        $this->assertSame(config('app.url'), $context['APP_URL']);
        $this->assertArrayHasKey('_request', $context);
        $this->assertIsArray($context['_request']);
        $this->assertArrayHasKey('id', $context['_request']);
        $this->assertSame((string) $request->id, (string) $context['_request']['id']);
        $this->assertArrayHasKey('case_number', $context['_request']);
        $this->assertArrayHasKey('_user', $context);
        $this->assertIsArray($context['_user']);
        $this->assertArrayHasKey('id', $context['_user']);
        $this->assertArrayHasKey('processVar', $context);
        $this->assertSame('value123', $context['processVar']);
    }

    /**
     * Test resolveElementDestinationUrl resolves Mustache placeholders APP_URL and _request.id.
     */
    public function testResolveElementDestinationUrlResolvesMustache(): void
    {
        $user = User::factory()->create(['status' => 'ACTIVE']);
        $process = Process::factory()->create();
        $request = ProcessRequest::factory()->create([
            'process_id' => $process->id,
            'user_id' => $user->id,
            'data' => [],
        ]);

        $token = ProcessRequestToken::factory()->create([
            'process_id' => $process->id,
            'process_request_id' => $request->id,
            'user_id' => $user->id,
            'element_id' => 'end_1',
            'element_type' => 'end_event',
        ]);

        $urlTemplate = '{{APP_URL}}/path/{{_request.id}}';
        $resolved = $this->invokePrivateMethod($token, 'resolveElementDestinationUrl', [$urlTemplate]);

        $expectedUrl = config('app.url') . '/path/' . $request->id;
        $this->assertSame($expectedUrl, $resolved);
    }

    /**
     * Test resolveElementDestinationUrl resolves process variable in URL.
     */
    public function testResolveElementDestinationUrlResolvesProcessVariable(): void
    {
        $user = User::factory()->create(['status' => 'ACTIVE']);
        $process = Process::factory()->create();
        $request = ProcessRequest::factory()->create([
            'process_id' => $process->id,
            'user_id' => $user->id,
            'data' => ['segment' => 'admin'],
        ]);

        $token = ProcessRequestToken::factory()->create([
            'process_id' => $process->id,
            'process_request_id' => $request->id,
            'user_id' => $user->id,
            'element_id' => 'end_1',
            'element_type' => 'end_event',
        ]);

        $urlTemplate = '{{APP_URL}}/{{segment}}/users';
        $resolved = $this->invokePrivateMethod($token, 'resolveElementDestinationUrl', [$urlTemplate]);

        $this->assertSame(config('app.url') . '/admin/users', $resolved);
    }

    /**
     * Test resolveElementDestinationUrl decodes HTML entities in template.
     */
    public function testResolveElementDestinationUrlDecodesHtmlEntities(): void
    {
        $user = User::factory()->create(['status' => 'ACTIVE']);
        $process = Process::factory()->create();
        $request = ProcessRequest::factory()->create([
            'process_id' => $process->id,
            'user_id' => $user->id,
            'data' => [],
        ]);

        $token = ProcessRequestToken::factory()->create([
            'process_id' => $process->id,
            'process_request_id' => $request->id,
            'user_id' => $user->id,
            'element_id' => 'end_1',
            'element_type' => 'end_event',
        ]);

        $urlWithEntities = '&#104;&#116;&#116;&#112;&#115;&#58;//example.com/{{_request.id}}';
        $resolved = $this->invokePrivateMethod($token, 'resolveElementDestinationUrl', [$urlWithEntities]);

        $this->assertStringContainsString((string) $request->id, $resolved);
        $this->assertStringContainsString('https://example.com/', $resolved);
    }
}
