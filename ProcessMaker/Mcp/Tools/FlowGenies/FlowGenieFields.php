<?php

declare(strict_types=1);

namespace ProcessMaker\Mcp\Tools\FlowGenies;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\IntegerType;
use Illuminate\JsonSchema\Types\ObjectType;
use Illuminate\JsonSchema\Types\StringType;

final class FlowGenieFields
{
    public static function id(JsonSchema $schema): IntegerType
    {
        return $schema->integer()->description('FlowGenie id.')->required();
    }

    public static function name(JsonSchema $schema, bool $required): StringType
    {
        $field = $schema->string()->description('Unique FlowGenie name (alpha_spaces).');

        return $required ? $field->required() : $field;
    }

    public static function description(JsonSchema $schema, bool $required): StringType
    {
        $field = $schema->string()->description('FlowGenie description (max 300).');

        return $required ? $field->required() : $field;
    }

    public static function category(JsonSchema $schema, bool $required): StringType
    {
        $field = $schema->string()->description('flow_genie_category_id from list-flow-genie-categories (comma-separated ids OK).');

        return $required ? $field->required() : $field;
    }

    public static function config(JsonSchema $schema, bool $required = false): ObjectType
    {
        $field = $schema->object()->description(
            'Agent config. Key fields: instructions (system prompt), model, provider, output_type '
            . '(Text (default)|JSON|Table), mcp_servers, tools, dynamic_tools, model_settings, guardrails, '
            . 'collections, max_turns. Legacy aliases prompt → instructions, response_format → output_type.'
        );

        return $required ? $field->required() : $field;
    }
}
