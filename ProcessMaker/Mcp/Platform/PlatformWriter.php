<?php

declare(strict_types=1);

namespace ProcessMaker\Mcp\Platform;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use ProcessMaker\Http\Controllers\Api\ProcessController;
use ProcessMaker\Http\Controllers\Api\ScreenController;
use ProcessMaker\Http\Controllers\Api\ScriptController;
use ProcessMaker\Http\Requests\ProcessUpdateRequest;
use ProcessMaker\Jobs\ImportScreen;
use ProcessMaker\Mcp\Migration\MigrationBpmn;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\Script;
use ProcessMaker\Models\User;
use ProcessMaker\Nayra\Services\FixBpmnSchemaService;
use ProcessMaker\Nayra\Storage\BpmnDocument;

/**
 * Delegates MCP writes to the same PM4 controllers and import jobs used by the REST API.
 */
final class PlatformWriter
{
    public function __construct(
        private readonly ScreenPackageBuilder $screenPackages,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createProcess(array $data): Process
    {
        $payload = [
            'name' => $data['name'],
            'description' => $data['description'] ?? $data['name'],
            'status' => $data['status'] ?? 'ACTIVE',
        ];

        if (!empty($data['process_category_id'])) {
            $payload['process_category_id'] = $data['process_category_id'];
        }

        if (isset($data['bpmn']) && is_string($data['bpmn'])) {
            $payload['bpmn'] = $this->prepareBpmn($data['bpmn']);
        }

        $this->actingUser();
        $request = $this->apiRequest('POST', '/api/1.0/processes', $payload);

        return $this->unwrapModel(
            app(ProcessController::class)->store($request),
            Process::class,
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateProcess(Process $process, array $data): Process
    {
        $payload = array_filter([
            'name' => $data['name'] ?? null,
            'description' => $data['description'] ?? null,
            'status' => $data['status'] ?? null,
            'process_category_id' => $data['process_category_id'] ?? null,
            'properties' => $data['properties'] ?? null,
        ], fn ($value) => $value !== null);

        if ($payload === []) {
            return $process;
        }

        $user = $this->actingUser();

        $request = ProcessUpdateRequest::create(
            '/api/1.0/processes/' . $process->id,
            'PUT',
            $payload,
        );
        $request->setUserResolver(fn (): User => $user);
        $request->setRouteResolver(fn (): Route => $this->routeWithProcess($request, $process));

        $response = app(ProcessController::class)->update($request, $process);

        if (is_array($response) && isset($response['error'])) {
            throw ValidationException::withMessages(['process' => [(string) $response['error']]]);
        }

        return $process->refresh();
    }

    public function updateProcessBpmn(Process $process, string $bpmn): Process
    {
        $prepared = $this->prepareBpmn($bpmn);

        $errors = MigrationBpmn::validate($prepared);
        if ($errors !== []) {
            throw ValidationException::withMessages(['bpmn' => $errors]);
        }

        return $this->persistProcessBpmn($process, $prepared);
    }

    public function persistProcessBpmn(Process $process, string $bpmn): Process
    {
        $process->refresh();

        $request = $this->apiRequest('PUT', '/api/1.0/processes/' . $process->id . '/update-bpmn', [
            'bpmn' => $this->prepareBpmn($bpmn),
            'name' => $process->name,
            'description' => $process->description,
        ]);

        $response = app(ProcessController::class)->updateBpmn($request, $process);

        if ($response instanceof JsonResponse && $response->getStatusCode() >= 400) {
            $this->throwValidationFromResponse($response);
        }

        return $process->refresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createScreen(array $data): Screen
    {
        return $this->createScreenResult($data)->screen;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createScreenResult(array $data): ScreenCreationResult
    {
        $import = $this->importScreen($data);

        if ($import['id'] !== null) {
            $screen = Screen::findOrFail($import['id']);
            if (!empty($data['screen_category_id'])) {
                $screen->screen_category_id = $data['screen_category_id'];
                $screen->saveOrFail();
            }

            return new ScreenCreationResult($screen, 'import_screen', $import['warnings']);
        }

        $warnings = array_merge($import['warnings'], [
            'Screen import failed; created screen via ScreenController::store fallback.',
        ]);

        return new ScreenCreationResult(
            $this->createScreenViaController($data),
            'screen_controller',
            $warnings,
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createScript(array $data): Script
    {
        $user = $this->actingUser();

        $payload = [
            'title' => $data['title'],
            'description' => $data['description'] ?? $data['title'],
            'code' => $data['code'],
            'language' => $data['language'] ?? 'php',
            'run_as_user_id' => $data['run_as_user_id'] ?? $user->id,
            'script_category_id' => $data['script_category_id'] ?? null,
        ];

        $request = $this->apiRequest('POST', '/api/1.0/scripts', array_filter($payload, fn ($v) => $v !== null));

        return $this->unwrapModel(
            app(ScriptController::class)->store($request),
            Script::class,
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{id: int|string|null, warnings: array<int, string>}
     */
    private function importScreen(array $data): array
    {
        try {
            $packageJson = $this->screenPackages->encode($data);
        } catch (\JsonException $e) {
            return [
                'id' => null,
                'warnings' => ['Screen package encoding failed: ' . $e->getMessage()],
            ];
        }

        try {
            $result = (new ImportScreen($packageJson))->handle();
        } catch (\Throwable $e) {
            return [
                'id' => null,
                'warnings' => ['ImportScreen failed: ' . $e->getMessage()],
            ];
        }

        if (!is_array($result)) {
            return [
                'id' => null,
                'warnings' => ['ImportScreen returned an invalid response.'],
            ];
        }

        $screenId = $result['screens']['id'] ?? null;

        if ($screenId === null || $screenId === false) {
            return [
                'id' => null,
                'warnings' => ['ImportScreen did not return a screen ID.'],
            ];
        }

        return ['id' => $screenId, 'warnings' => []];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function createScreenViaController(array $data): Screen
    {
        $config = $data['config'] ?? $data['config_json'] ?? [];

        $payload = [
            'title' => $data['title'],
            'description' => $data['description'] ?? $data['title'],
            'type' => $data['type'] ?? 'FORM',
            'config' => is_array($config) ? $config : [],
            'computed' => $data['computed'] ?? [],
            'watchers' => $data['watchers'] ?? [],
            'custom_css' => $data['custom_css'] ?? '',
            'screen_category_id' => $data['screen_category_id'] ?? null,
        ];

        $request = $this->apiRequest('POST', '/api/1.0/screens', array_filter($payload, fn ($v) => $v !== null));

        return $this->unwrapModel(
            app(ScreenController::class)->store($request),
            Screen::class,
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function apiRequest(string $method, string $uri, array $payload = []): Request
    {
        $request = Request::create($uri, $method, $payload);
        $request->setUserResolver(fn (): User => $this->actingUser());

        return $request;
    }

    private function actingUser(): User
    {
        if (Auth::user() instanceof User) {
            return Auth::user();
        }

        $user = User::firstOrFail();
        Auth::setUser($user);

        return $user;
    }

    private function prepareBpmn(string $bpmn): string
    {
        return BpmnDocument::replaceHtmlEntities(FixBpmnSchemaService::fix($bpmn));
    }

    /**
     * @param  class-string  $modelClass
     */
    private function unwrapModel(mixed $response, string $modelClass): mixed
    {
        if ($response instanceof JsonResponse) {
            $this->throwValidationFromResponse($response);
        }

        if ($response instanceof \Symfony\Component\HttpFoundation\Response && !($response instanceof JsonResponse)) {
            if ($response->getStatusCode() >= 400) {
                $payload = json_decode($response->getContent(), true);
                if (is_array($payload)) {
                    $errors = is_array($payload['errors'] ?? null) ? $payload['errors'] : [];
                    if ($errors === [] && isset($payload['message'])) {
                        $errors = ['message' => [(string) $payload['message']]];
                    }
                    throw ValidationException::withMessages($errors);
                }
            }
        }

        if ($response instanceof JsonResource) {
            $model = $response->resource;
            if ($model instanceof $modelClass) {
                return $model->refresh();
            }
        }

        throw ValidationException::withMessages([
            'platform' => ['Unexpected response from PM4 platform writer.'],
        ]);
    }

    private function throwValidationFromResponse(JsonResponse $response): never
    {
        $payload = $response->getData(true);
        $errors = is_array($payload['errors'] ?? null) ? $payload['errors'] : [];

        if ($errors === [] && isset($payload['message'])) {
            $errors = ['message' => [(string) $payload['message']]];
        }

        throw ValidationException::withMessages($errors);
    }

    private function routeWithProcess(ProcessUpdateRequest $request, Process $process): Route
    {
        $route = new Route('PUT', '/api/1.0/processes/{process}', []);
        $route->bind($request);
        $route->setParameter('process', $process);

        return $route;
    }
}
