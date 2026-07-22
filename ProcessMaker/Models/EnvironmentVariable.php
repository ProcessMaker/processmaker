<?php

namespace ProcessMaker\Models;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use ProcessMaker\Enums\ExporterMap;
use ProcessMaker\Traits\Exportable;

/**
 * @OA\Schema(
 *   schema="EnvironmentVariableEditable",
 *   @OA\Property(property="name", type="string"),
 *   @OA\Property(property="description", type="string"),
 *   @OA\Property(property="value", type="string"),
 *   @OA\Property(property="asset_type", type="string", nullable=true),
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
    ];

    protected $hidden = [
        'value',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function (self $environmentVariable) {
            $environmentVariable->normalizeAssetLink();
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
            'value' => [
                'nullable',
                'required_with:asset_type',
            ],
            'name' => ['required', "regex:{$validVariableName}", $unique],
            'asset_type' => [
                'nullable',
                'string',
                Rule::in($allowedAssetTypes),
            ],
        ];
    }

    /**
     * Validate that asset_type + value resolve to an existing asset of that type.
     *
     * @return Model|null The resolved asset when a link is present
     */
    public static function validateAssetLinkConsistency(array $data): ?Model
    {
        $assetType = $data['asset_type'] ?? null;
        $value = $data['value'] ?? null;

        if (!$assetType) {
            return null;
        }

        if ($value === null || $value === '') {
            throw ValidationException::withMessages([
                'value' => [trans('environmentVariables.validation.value.required_with_asset_type')],
            ]);
        }

        if (!class_exists($assetType)) {
            throw ValidationException::withMessages([
                'asset_type' => [__('The selected asset type is not available.')],
            ]);
        }

        if (!in_array($assetType, self::allowedAssetTypes(), true)) {
            throw ValidationException::withMessages([
                'asset_type' => [__('The selected asset type is not allowed.')],
            ]);
        }

        $asset = $assetType::find($value);
        if (!$asset) {
            throw ValidationException::withMessages([
                'value' => [trans('environmentVariables.validation.value.not_found_for_type')],
            ]);
        }

        // Guard against class hierarchy mismatches (e.g. subclass stored under a parent type).
        if (get_class($asset) !== $assetType) {
            throw ValidationException::withMessages([
                'asset_type' => [trans('environmentVariables.validation.asset_type.mismatch')],
            ]);
        }

        return $asset;
    }

    public static function messages()
    {
        return [
            'name.regex' => trans('environmentVariables.validation.name.invalid_variable_name'),
            'value.required_with' => trans('environmentVariables.validation.value.required_with_asset_type'),
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
        if (!$this->hasLinkedAsset() || !class_exists($this->asset_type)) {
            return null;
        }

        if ($this->value === null || $this->value === '') {
            return null;
        }

        return $this->asset_type::find($this->value);
    }

    public function hasLinkedAsset(): bool
    {
        return !empty($this->asset_type);
    }

    protected function normalizeAssetLink(): void
    {
        if ($this->asset_type === '') {
            $this->asset_type = null;
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
