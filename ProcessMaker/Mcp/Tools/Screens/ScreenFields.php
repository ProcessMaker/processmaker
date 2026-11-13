<?php

declare(strict_types=1);

namespace ProcessMaker\Mcp\Tools\Screens;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\ArrayType;
use Illuminate\JsonSchema\Types\IntegerType;
use Illuminate\JsonSchema\Types\StringType;

final class ScreenFields
{
    public const TITLE = 'Screen title. Letters, numbers, spaces, dashes, underscores, dots, and apostrophes.';

    public const DESCRIPTION = 'Screen description shown in the designer.';

    public const TYPE = 'Screen type. Common values are FORM and DISPLAY.';

    public const CATEGORY = 'Screen category id, or comma-separated ids.';

    public const CONFIG = 'Screen builder config. An array of page objects. Each control in page.items must include inspector (use [] when unknown); pages should include items ([]) even when empty.';

    public const PREVIEW_PRESETS = 'Named Screen Builder Preview payloads. Array of { title, data }. data MUST be a JSON string (not a nested object) so empty {} survive PHP json_decode. Required on create and Vue refactor: cover empty/start, happy path, and each major UI branch.';

    public static function id(JsonSchema $schema): IntegerType
    {
        return $schema->integer()
            ->description('Screen id.')
            ->required();
    }

    public static function title(JsonSchema $schema, bool $required): StringType
    {
        $field = $schema->string()->description(self::TITLE);

        return $required ? $field->required() : $field;
    }

    public static function description(JsonSchema $schema, bool $required): StringType
    {
        $field = $schema->string()->description(self::DESCRIPTION);

        return $required ? $field->required() : $field;
    }

    public static function type(JsonSchema $schema, bool $required): StringType
    {
        $field = $schema->string()->description(self::TYPE);

        return $required ? $field->required() : $field;
    }

    public static function category(JsonSchema $schema, bool $required): StringType
    {
        $field = $schema->string()->description(self::CATEGORY);

        return $required ? $field->required() : $field;
    }

    public static function config(JsonSchema $schema): ArrayType
    {
        return $schema->array()
            ->items($schema->object())
            ->description(self::CONFIG);
    }

    public static function previewPresets(JsonSchema $schema): ArrayType
    {
        return $schema->array()
            ->items($schema->object())
            ->description(self::PREVIEW_PRESETS);
    }
}
