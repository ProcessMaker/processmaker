<?php

declare(strict_types=1);

namespace ProcessMaker\Mcp\Tools\Collections;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\IntegerType;
use Illuminate\JsonSchema\Types\StringType;

final class CollectionFields
{
    public static function id(JsonSchema $schema): IntegerType
    {
        return $schema->integer()->description('Collection id.')->required();
    }

    public static function name(JsonSchema $schema, bool $required): StringType
    {
        $field = $schema->string()->description('Unique collection name (alpha_spaces).');

        return $required ? $field->required() : $field;
    }

    public static function description(JsonSchema $schema, bool $required): StringType
    {
        $field = $schema->string()->description('Collection description.');

        return $required ? $field->required() : $field;
    }

    public static function screenId(JsonSchema $schema, string $role, bool $required): IntegerType
    {
        $field = $schema->integer()->description($role . ' screen id.');

        return $required ? $field->required() : $field;
    }
}
