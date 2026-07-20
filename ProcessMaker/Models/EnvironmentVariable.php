<?php

namespace ProcessMaker\Models;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use ProcessMaker\Enums\ExporterMap;
use ProcessMaker\Traits\Exportable;

/**
 * @OA\Schema(
 *   schema="EnvironmentVariableEditable",
 *   @OA\Property(property="name", type="string"),
 *   @OA\Property(property="description", type="string"),
 *   @OA\Property(property="value", type="string"),
 *   @OA\Property(property="asset_type", type="string", nullable=true),
 *   @OA\Property(property="asset_uuid", type="string", format="uuid", nullable=true),
 * ),
 * @OA\Schema(
 *   schema="EnvironmentVariable",
 *   allOf={
 *     @OA\Schema(ref="#/components/schemas/EnvironmentVariableEditable"),
 *     @OA\Schema(
 *       @OA\Property(property="id", type="integer", format="id"),
 *       @OA\Property(property="created_at", type="string", format="date-time"),
 *       @OA\Property(property="updated_at", type="string", format="date-time"),
 *     ),
 *   },
 * )
 */
class EnvironmentVariable extends ProcessMakerModel
{
    use Exportable;

    protected $connection = 'processmaker';

    protected $fillable = [
        'name',
        'description',
        'value',
        'asset_type',
        'asset_uuid',
    ];

    protected $hidden = [
        'value',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function (self $environmentVariable) {
            $environmentVariable->normalizeAssetLink();
            $environmentVariable->syncLinkedAssetValue();
        });
    }

    /**
     * Store the encrypted version of the variable value here
     */
    public function setValueAttribute($value)
    {
        $this->attributes['value'] = encrypt($value);
    }

    /**
     * Fetch the plain text version of the value
     */
    public function getValueAttribute()
    {
        try {
            return decrypt($this->attributes['value']);
        } catch (Exception $e) {
            Log::error(
                'Can not decrypt environment variable: ' .
                ($this->attributes['name'] ?? 'unknown') .
                "\n" . $e->getMessage() .
                "\n" . $e->getTraceAsString(),
                ['attributes' => $this->attributes]
            );

            return null;
        }
    }

    public static function rules($existing = null)
    {
        $unique = Rule::unique('environment_variables')->ignore($existing);
        $validVariableName = '/^[a-zA-Z][a-zA-Z_$0-9]*$/';
        $allowedAssetTypes = self::allowedAssetTypes();

        return [
            'description' => 'required',
            'value' => 'nullable',
            'name' => ['required', "regex:{$validVariableName}", $unique],
            'asset_type' => [
                'nullable',
                'string',
                Rule::in($allowedAssetTypes),
                'required_with:asset_uuid',
            ],
            'asset_uuid' => [
                'nullable',
                'uuid',
                'required_with:asset_type',
            ],
        ];
    }

    /**
     * Validate that a linked asset exists when asset_type and asset_uuid are provided.
     */
    public static function validateLinkedAssetExists(array $data): void
    {
        $assetType = $data['asset_type'] ?? null;
        $assetUuid = $data['asset_uuid'] ?? null;

        if (!$assetType && !$assetUuid) {
            return;
        }

        if (!$assetType || !$assetUuid) {
            return;
        }

        if (!class_exists($assetType)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'asset_type' => [__('The selected asset type is not available.')],
            ]);
        }

        if (!$assetType::where('uuid', $assetUuid)->exists()) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'asset_uuid' => [__('The selected asset could not be found.')],
            ]);
        }
    }

    public static function messages()
    {
        return [
            'name.regex' => trans('environmentVariables.validation.name.invalid_variable_name'),
            'asset_type.required_with' => trans('environmentVariables.validation.asset_type.required_with'),
            'asset_uuid.required_with' => trans('environmentVariables.validation.asset_uuid.required_with'),
        ];
    }

    /**
     * Exportable model classes that may be linked to an environment variable.
     */
    public static function allowedAssetTypes(): array
    {
        return collect(ExporterMap::TYPES)
            ->map(fn (array $type) => $type[0])
            ->filter(fn (string $class) => class_exists($class))
            ->values()
            ->all();
    }

    public function resolveLinkedAsset(): ?Model
    {
        if (!$this->asset_type || !$this->asset_uuid || !class_exists($this->asset_type)) {
            return null;
        }

        return $this->asset_type::where('uuid', $this->asset_uuid)->first();
    }

    public function hasLinkedAsset(): bool
    {
        return !empty($this->asset_type) && !empty($this->asset_uuid);
    }

    protected function normalizeAssetLink(): void
    {
        if ($this->asset_type === '') {
            $this->asset_type = null;
        }

        if ($this->asset_uuid === '') {
            $this->asset_uuid = null;
        }
    }

    /**
     * Keep value in sync with the linked asset's numeric ID on this instance.
     */
    public function syncLinkedAssetValue(): void
    {
        if (!$this->hasLinkedAsset()) {
            return;
        }

        $asset = $this->resolveLinkedAsset();
        if ($asset) {
            $this->value = (string) $asset->id;
        }
    }

    public static function getMetricsApiEndpoint()
    {
        $variable = self::where('name', 'METRICS_API_ENDPOINT')->first();

        if ($variable) {
            return $variable->value;
        }

        return '/api/1.0/processes/{process}/metrics';
    }
}
