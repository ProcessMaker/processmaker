<?php

declare(strict_types=1);

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use ProcessMaker\Http\Controllers\Api\Concerns\AppliesDeployVersionName;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Models\Screen;
use ProcessMaker\Services\DeployCategoryResolver;
use ProcessMaker\Traits\TaskScreenResourceTrait;
use Symfony\Component\Yaml\Yaml;

class DeployScreen extends Controller
{
    use AppliesDeployVersionName;
    use TaskScreenResourceTrait;

    private const EMPTY_PRESET_DATA = '{}';

    public function __construct(private readonly DeployCategoryResolver $categories)
    {
    }

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
        $name = (string) $request->input('name');
        $isUpdate = Screen::where('title', $name)->exists();

        if ($unavailable = $this->ensureDeployVersionHistoryAvailable($isUpdate)) {
            return $unavailable;
        }

        $validator = Validator::make(
            $request->all(),
            $this->deployRulesForUpdate([
                'yml_content' => 'required|string',
                'name' => 'required|string',
            ], $isUpdate),
        );

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        return $this->persistDeployedScreen($request, $name);
    }

    private function persistDeployedScreen(Request $request, string $name): JsonResponse
    {
        $ymlContent = $request->input('yml_content');
        $parsedData = $this->parseDeployYaml($ymlContent);
        if ($parsedData instanceof JsonResponse) {
            return $parsedData;
        }

        $screen = Screen::where('title', $name)->first();
        $created = $screen === null;

        if ($created) {
            $screen = new Screen();
            $screen->title = $name;
            $screen->description = $this->screenDescription($parsedData, $name);
            $screen->type = $parsedData['type'] ?? 'FORM';
        } elseif (array_key_exists('description', $parsedData)) {
            $screen->description = $this->screenDescription($parsedData, $name);
        }

        $categoryName = $parsedData['category'] ?? $parsedData['screen_category_name'] ?? null;
        if ($created || $categoryName !== null) {
            $categoryId = $this->categories->resolveScreenCategoryId(
                is_string($categoryName) ? $categoryName : null
            );
            if ($categoryId !== null) {
                $screen->screen_category_id = (string) $categoryId;
            }
        }

        $this->applyYamlFields($screen, $parsedData, $created);
        $publishedVersionIdBefore = $this->latestPublishedVersionId($screen);
        $screen->save();
        $this->applyDeployVersionName($screen, $request, !$created, $publishedVersionIdBefore);
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
        $config = $this->withEmptyInspector($this->removeInspectorMetadata($screenVersion->config ?? []));
        $data = [
            'config' => $config,
            'computed' => $screenVersion->computed,
            'watchers' => $screenVersion->watchers,
            'custom_css' => $screenVersion->custom_css,
            'preview_presets' => $screenVersion->preview_presets ?? [],
        ];
        $yaml = Yaml::dump($data, 100, 2, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK);

        return response($yaml, 200)
            ->header('Content-Type', 'text/yaml')
            ->header('X-Screen-Id', (string) $screen->id)
            ->header('X-Screen-Name', $screen->title);
    }

    /**
     * The export drops the (large) inspector metadata, but the YAML contract expects every
     * item to carry `inspector: []`, so a pulled screen can be pushed back unchanged.
     *
     * @param array<int, mixed> $config
     * @return array<int, mixed>
     */
    private function withEmptyInspector(array $config): array
    {
        foreach ($config as $index => $page) {
            if (is_array($page) && isset($page['items']) && is_array($page['items'])) {
                $config[$index]['items'] = $this->addEmptyInspector($page['items']);
            }
        }

        return $config;
    }

    /**
     * @param array<int|string, mixed> $items
     * @return array<int|string, mixed>
     */
    private function addEmptyInspector(array $items): array
    {
        foreach ($items as $index => $item) {
            if (!is_array($item)) {
                continue;
            }
            if (isset($item['component']) && !array_key_exists('inspector', $item)) {
                $item['inspector'] = [];
            }
            if (isset($item['items']) && is_array($item['items'])) {
                $item['items'] = $this->addEmptyInspector($item['items']);
            }
            // A FormMultiColumn holds a list of columns, and each column is a list of items.
            $items[$index] = array_is_list($item) && $item !== [] ? $this->addEmptyInspector($item) : $item;
        }

        return $items;
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
        if (array_key_exists('preview_presets', $parsedData)) {
            $parsedData['preview_presets'] = $this->normalizePreviewPresets($parsedData['preview_presets']);
        }

        $defaults = [
            'config' => [],
            'computed' => [],
            'watchers' => [],
            'custom_css' => '',
            'preview_presets' => [],
        ];

        foreach ($defaults as $field => $default) {
            if (array_key_exists($field, $parsedData)) {
                $screen->{$field} = $parsedData[$field];
            } elseif ($created) {
                $screen->{$field} = $default;
            }
        }
    }

    /**
     * Designer stores each preset data value as a JSON string so `{}` survives decode.
     *
     * @return list<array{title: string, data: string}>
     */
    private function normalizePreviewPresets(mixed $presets): array
    {
        if (!is_array($presets)) {
            return [];
        }

        $normalized = [];
        foreach ($presets as $preset) {
            if (!is_array($preset)) {
                continue;
            }
            $title = $preset['title'] ?? '';
            $normalized[] = [
                'title' => is_string($title) ? $title : '',
                'data' => $this->encodePresetData($preset['data'] ?? self::EMPTY_PRESET_DATA),
            ];
        }

        return $normalized;
    }

    private function encodePresetData(mixed $data): string
    {
        if (is_array($data)) {
            // YAML empty mappings parse as []; json_encode would persist them as [].
            $encoded = json_encode(
                $this->emptyMapsToObjects($data),
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );

            return $encoded === false ? self::EMPTY_PRESET_DATA : $encoded;
        }

        if (is_string($data) && $this->isJsonString($data)) {
            return $data;
        }

        return self::EMPTY_PRESET_DATA;
    }

    /**
     * Recursively turn empty arrays into objects so preview data keeps {}.
     * shortcut: empty YAML sequences under a key also become {}, revisit if presets need [] leaves
     */
    private function emptyMapsToObjects(array $data): array|\stdClass
    {
        if ($data === []) {
            return new \stdClass();
        }

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->emptyMapsToObjects($value);
            }
        }

        return $data;
    }

    private function isJsonString(string $data): bool
    {
        try {
            json_decode($data, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return false;
        }

        return true;
    }

    /**
     * @param array<string, mixed> $parsedData
     */
    private function screenDescription(array $parsedData, string $title): string
    {
        $description = $parsedData['description'] ?? '';
        if (is_string($description) && trim($description) !== '') {
            return trim($description);
        }

        return 'Screen: ' . trim($title);
    }
}
