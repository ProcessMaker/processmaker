<?php

declare(strict_types=1);

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Models\Screen;
use ProcessMaker\Traits\TaskScreenResourceTrait;
use Symfony\Component\Yaml\Yaml;

class DeployScreen extends Controller
{
    use TaskScreenResourceTrait;

    /**
     * A whitelist of attributes that should not be
     * sanitized by our SanitizeInput middleware.
     *
     * @var array
     */
    public $doNotSanitize = [
        'yml_content',
    ];

    public function deployScreen(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'yml_content' => 'required|string',
            'name' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $ymlContent = $request->input('yml_content');
        $name = $request->input('name');

        $parsedData = $this->parseDeployYaml($ymlContent);
        if ($parsedData instanceof JsonResponse) {
            return $parsedData;
        }

        $screen = Screen::where('title', $name)->first();
        $created = $screen === null;

        if ($created) {
            $screen = new Screen();
            $screen->title = $name;
            $screen->description = $parsedData['description'] ?? '';
            $screen->type = $parsedData['type'] ?? 'FORM';
        }

        $this->applyYamlFields($screen, $parsedData, $created);
        $screen->save();
        $screen->refresh();

        return response()->json([
            'success' => true,
            'action' => $created ? 'created' : 'updated',
            'screen_id' => $screen->id,
            'name' => $screen->title,
            'message' => $created
                ? 'Screen created from YAML.'
                : 'Screen updated from YAML.',
        ]);
    }

    /**
     * @return \Illuminate\Http\Response|JsonResponse
     */
    public function getScreenYaml(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $name = $request->input('name');
        $resolved = $this->resolvePublishedScreenByTitle($name);
        if ($resolved instanceof JsonResponse) {
            return $resolved;
        }

        [$screen, $screenVersion] = $resolved;
        $config = $this->removeInspectorMetadata($screenVersion->config ?? []);
        $data = [
            'config' => $config,
            'computed' => $screenVersion->computed,
            'watchers' => $screenVersion->watchers,
            'custom_css' => $screenVersion->custom_css,
        ];
        $yaml = Yaml::dump($data, 100, 2, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK);

        return response($yaml, 200)
            ->header('Content-Type', 'text/yaml')
            ->header('X-Screen-Id', (string) $screen->id)
            ->header('X-Screen-Name', $screen->title);
    }

    /**
     * @return array{0: Screen, 1: object}|JsonResponse
     */
    private function resolvePublishedScreenByTitle(string $name): array|JsonResponse
    {
        $screen = Screen::where('title', $name)->first();
        if ($screen === null) {
            return response()->json([
                'success' => false,
                'message' => 'Screen not found',
                'name' => $name,
            ], 404);
        }

        $screenVersion = $screen->getPublishedVersion([]);
        if ($screenVersion === null) {
            return response()->json([
                'success' => false,
                'message' => 'Screen does not have a published version',
                'name' => $name,
                'screen_id' => $screen->id,
            ], 404);
        }

        return [$screen, $screenVersion];
    }

    /**
     * @return array<string, mixed>|JsonResponse
     */
    private function parseDeployYaml(string $ymlContent): array|JsonResponse
    {
        try {
            $parsedData = Yaml::parse($ymlContent) ?? [];
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid YAML content',
                'error' => $e->getMessage(),
            ], 400);
        }

        if (!is_array($parsedData)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid YAML content',
                'error' => 'YAML must parse to a mapping of screen fields',
            ], 400);
        }

        return $parsedData;
    }

    private function applyYamlFields(Screen $screen, array $parsedData, bool $created): void
    {
        $defaults = [
            'config' => [],
            'computed' => [],
            'watchers' => [],
            'custom_css' => '',
        ];

        foreach ($defaults as $field => $default) {
            if (array_key_exists($field, $parsedData)) {
                $screen->{$field} = $parsedData[$field];
            } elseif ($created) {
                $screen->{$field} = $default;
            }
        }
    }
}
