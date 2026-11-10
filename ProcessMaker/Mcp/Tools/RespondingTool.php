<?php

declare(strict_types=1);

namespace ProcessMaker\Mcp\Tools;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tool;
use Throwable;

abstract class RespondingTool extends Tool
{
    /**
     * @param callable(): array<string, mixed> $action
     */
    protected function respond(callable $action): Response|ResponseFactory
    {
        try {
            return Response::structured($action());
        } catch (Throwable $exception) {
            return $this->errorResponse($exception);
        }
    }

    private function errorResponse(Throwable $exception): Response
    {
        $message = match (true) {
            $exception instanceof ValidationException => $this->validationMessage($exception),
            $exception instanceof AuthorizationException => $exception->getMessage(),
            $exception instanceof ModelNotFoundException => $exception->getMessage() !== ''
                ? $exception->getMessage()
                : 'Resource not found.',
            default => throw $exception,
        };

        return Response::error($message);
    }

    private function validationMessage(ValidationException $exception): string
    {
        $messages = collect($exception->errors())->flatten()->unique()->implode(' ');

        return $messages !== '' ? $messages : $exception->getMessage();
    }
}
