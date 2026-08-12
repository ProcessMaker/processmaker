<?php

declare(strict_types=1);

namespace Tests\Unit\ProcessMaker;

use Illuminate\Container\Container;
use Mockery;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\RetryProcessRequest;
use ReflectionClass;
use Tests\TestCase;

class RetryProcessRequestTest extends TestCase
{
    private function getPrivateProperty(object $object, string $property): mixed
    {
        $reflection = new ReflectionClass($object);
        $propertyReflection = $reflection->getProperty($property);
        $propertyReflection->setAccessible(true);

        return $propertyReflection->getValue($object);
    }

    private function setPrivateProperty(object $object, string $property, mixed $value): void
    {
        $reflection = new ReflectionClass($object);
        $propertyReflection = $reflection->getProperty($property);
        $propertyReflection->setAccessible(true);
        $propertyReflection->setValue($object, $value);
    }

    private function createRetryProcessRequest(): RetryProcessRequest
    {
        return RetryProcessRequest::for(ProcessRequest::factory()->create());
    }

    private function invokeDetermineTaskTypes(RetryProcessRequest $retry, bool $all = false): void
    {
        $reflection = new ReflectionClass($retry);
        $method = $reflection->getMethod('determineTaskTypes');
        $method->setAccessible(true);
        $method->invoke($retry, $all);
    }

    private function withRunningInConsole(bool $runningInConsole, callable $callback): mixed
    {
        $originalApp = $this->app;
        $mock = Mockery::mock($originalApp)->makePartial();
        $mock->shouldReceive('runningInConsole')->andReturn($runningInConsole);
        $this->app = $mock;
        Container::setInstance($mock);

        try {
            return $callback();
        } finally {
            $this->app = $originalApp;
            Container::setInstance($originalApp);
        }
    }

    public function test_output_does_not_leak_between_instances(): void
    {
        $first = $this->createRetryProcessRequest();
        $second = $this->createRetryProcessRequest();

        $this->setPrivateProperty($first, 'output', ['Retrying ScriptTask (node_1) for Request::1']);

        $this->assertSame(['Retrying ScriptTask (node_1) for Request::1'], $first->getOutput());
        $this->assertSame([], $second->getOutput());
    }

    public function test_task_types_do_not_leak_between_instances(): void
    {
        $first = $this->createRetryProcessRequest();
        $second = $this->createRetryProcessRequest();

        $this->setPrivateProperty($first, 'taskTypes', ['scriptTask', 'serviceTask', 'task']);
        $this->setPrivateProperty($second, 'taskTypes', ['scriptTask']);

        $this->assertSame(['scriptTask', 'serviceTask', 'task'], $this->getPrivateProperty($first, 'taskTypes'));
        $this->assertSame(['scriptTask'], $this->getPrivateProperty($second, 'taskTypes'));
    }

    public function test_determine_task_types_can_include_all_types_when_requested(): void
    {
        $retry = $this->createRetryProcessRequest();

        $this->invokeDetermineTaskTypes($retry, true);

        $this->assertSame(
            ['scriptTask', 'serviceTask', 'task'],
            $this->getPrivateProperty($retry, 'taskTypes')
        );
    }

    public function test_task_types_include_only_script_tasks_in_web_context(): void
    {
        $retry = $this->createRetryProcessRequest();

        $this->withRunningInConsole(false, function () use ($retry) {
            $this->invokeDetermineTaskTypes($retry);
        });

        $this->assertSame(['scriptTask'], $this->getPrivateProperty($retry, 'taskTypes'));
    }

    public function test_has_non_retriable_tasks_does_not_leak_task_types_to_other_instances(): void
    {
        $first = $this->createRetryProcessRequest();
        $second = $this->createRetryProcessRequest();

        $this->setPrivateProperty($second, 'taskTypes', ['scriptTask']);

        $first->hasNonRetriableTasks();

        $this->assertSame(['scriptTask'], $this->getPrivateProperty($second, 'taskTypes'));
    }
}
