<?php

namespace Tests\Feature\Console;

use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use Tests\TestCase;

class DuplicateProcessRequestTest extends TestCase
{
    public function testItDuplicatesARequestAndItsTokens(): void
    {
        $request = ProcessRequest::factory()->create([
            'data' => ['customer' => 'Acme'],
        ]);
        $tokens = ProcessRequestToken::factory()->count(2)->create([
            'process_id' => $request->process_id,
            'process_request_id' => $request->id,
        ]);

        $this->artisan("processmaker:duplicate-request {$request->id} 2")
            ->expectsOutputToContain('Created 2 request duplicate(s):')
            ->assertExitCode(0);

        $duplicates = ProcessRequest::query()
            ->where('process_id', $request->process_id)
            ->where('id', '!=', $request->id)
            ->get();

        $this->assertCount(2, $duplicates);

        foreach ($duplicates as $duplicate) {
            $this->assertSame('Acme', $duplicate->data['customer']);
            $this->assertCount(2, $duplicate->tokens);
            $this->assertNotSame($request->id, $duplicate->id);
            $this->assertNotSame($request->uuid, $duplicate->uuid);
            $this->assertSame(
                $tokens->pluck('element_id')->sort()->values()->all(),
                $duplicate->tokens->pluck('element_id')->sort()->values()->all()
            );
        }
    }

    public function testItFailsWhenTheRequestDoesNotExist(): void
    {
        $this->artisan('processmaker:duplicate-request 999999 1')
            ->expectsOutput('The specified request does not exist.')
            ->assertExitCode(1);
    }

    public function testItFailsWhenTheNumberOfCopiesIsInvalid(): void
    {
        $request = ProcessRequest::factory()->create();

        $this->artisan("processmaker:duplicate-request {$request->id} 0")
            ->expectsOutput('The number of copies must be a positive integer.')
            ->assertExitCode(1);
    }
}
