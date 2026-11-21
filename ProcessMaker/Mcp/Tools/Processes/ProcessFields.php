<?php

declare(strict_types=1);

namespace ProcessMaker\Mcp\Tools\Processes;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\BooleanType;
use Illuminate\JsonSchema\Types\IntegerType;
use Illuminate\JsonSchema\Types\StringType;

final class ProcessFields
{
    public const NAME = 'Process name. Letters, numbers, spaces, dashes, underscores, dots, and apostrophes. Must be unique.';

    public const DESCRIPTION = 'Process description shown in the designer and catalogue.';

    public const STATUS = 'Process status: ACTIVE, INACTIVE, or ARCHIVED.';

    public const CATEGORY = 'Process category id, or comma-separated ids.';

    public const BPMN = 'Full BPMN 2.0 XML for the process. For MCP create/update use numeric pm:screenRef/pm:scriptRef ids. Portable ${screen:title}/${script:title}/${process:name} resolve when publishing via core deploy-process (POST /api/1.0/deploy-process).';

    public const ALTERNATIVE = 'Alternative version line: A (default) or B. Use with get-process / update-process / validate-process.';

    public const CREATE_ALTERNATIVE_B = 'If true, creates Alternative B (clone of A) before update when package-ab-testing is installed. No-op when B already exists.';

    public static function id(JsonSchema $schema): IntegerType
    {
        return $schema->integer()
            ->description('Process id.')
            ->required();
    }

    public static function name(JsonSchema $schema, bool $required): StringType
    {
        $field = $schema->string()->description(self::NAME);

        return $required ? $field->required() : $field;
    }

    public static function description(JsonSchema $schema, bool $required): StringType
    {
        $field = $schema->string()->description(self::DESCRIPTION);

        return $required ? $field->required() : $field;
    }

    public static function status(JsonSchema $schema): StringType
    {
        return $schema->string()->description(self::STATUS);
    }

    public static function category(JsonSchema $schema, bool $required): StringType
    {
        $field = $schema->string()->description(self::CATEGORY);

        return $required ? $field->required() : $field;
    }

    public static function bpmn(JsonSchema $schema, bool $required): StringType
    {
        $field = $schema->string()->description(self::BPMN);

        return $required ? $field->required() : $field;
    }

    public static function alternative(JsonSchema $schema, bool $required = false): StringType
    {
        $field = $schema->string()
            ->description(self::ALTERNATIVE)
            ->enum(['A', 'B']);

        return $required ? $field->required() : $field;
    }

    public static function createAlternativeB(JsonSchema $schema): BooleanType
    {
        return $schema->boolean()->description(self::CREATE_ALTERNATIVE_B);
    }
}
