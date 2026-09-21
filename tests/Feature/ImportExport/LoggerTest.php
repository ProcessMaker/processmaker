<?php

namespace Tests\Feature\ImportExport;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use ProcessMaker\Events\ImportLog;
use ProcessMaker\ImportExport\Logger;
use Tests\TestCase;

class LoggerTest extends TestCase
{
    #[DataProvider('messageMethods')]
    public function testMessagesIncludeOperationId($method, $expectedType)
    {
        Event::fake([ImportLog::class]);
        Storage::fake('local');

        $logger = new Logger(123, 'operation-123');
        $logger->{$method}('Test message');

        Event::assertDispatched(ImportLog::class, function (ImportLog $event) use ($expectedType) {
            return $event->userId === 123
                && $event->type === $expectedType
                && $event->message === 'Test message'
                && $event->operationId === 'operation-123';
        });
    }

    public static function messageMethods(): array
    {
        return [
            'log' => ['log', 'log'],
            'warning' => ['warn', 'warn'],
            'error' => ['error', 'error'],
            'status' => ['status', 'status'],
        ];
    }

    public function testOperationIdRemainsOptional()
    {
        Event::fake([ImportLog::class]);
        Storage::fake('local');

        (new Logger(123))->status('Test message');

        Event::assertDispatched(ImportLog::class, function (ImportLog $event) {
            return $event->operationId === null;
        });
    }
}
