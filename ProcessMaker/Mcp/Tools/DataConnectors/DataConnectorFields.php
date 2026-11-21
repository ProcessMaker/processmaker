<?php

declare(strict_types=1);

namespace ProcessMaker\Mcp\Tools\DataConnectors;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\IntegerType;
use Illuminate\JsonSchema\Types\ObjectType;
use Illuminate\JsonSchema\Types\StringType;

final class DataConnectorFields
{
    public static function id(JsonSchema $schema): IntegerType
    {
        return $schema->integer()->description('Data Connector id.')->required();
    }

    public static function name(JsonSchema $schema, bool $required): StringType
    {
        $field = $schema->string()->description('Unique connector name (alpha_spaces).');

        return $required ? $field->required() : $field;
    }

    public static function description(JsonSchema $schema, bool $required): StringType
    {
        $field = $schema->string()->description('Connector description.');

        return $required ? $field->required() : $field;
    }

    public static function type(JsonSchema $schema, bool $required): StringType
    {
        $field = $schema->string()->enum(['REST', 'SOAP'])->description('Connector type. Prefer REST.');

        return $required ? $field->required() : $field;
    }

    public static function authtype(JsonSchema $schema, bool $required): StringType
    {
        $field = $schema->string()->description('Auth type. REST: NONE, BASIC, OAUTH2_BEARER, OAUTH2_PASSWORD, OAUTH2_AUTHORIZATION_CODE.');

        return $required ? $field->required() : $field;
    }

    public static function category(JsonSchema $schema, bool $required): StringType
    {
        $field = $schema->string()->description('data_source_category_id from list-data-connector-categories.');

        return $required ? $field->required() : $field;
    }

    public static function endpoints(JsonSchema $schema): ObjectType
    {
        return $schema->object()->description('Map of endpoints keyed by purpose. Each value needs purpose, method, url.');
    }

    public static function credentials(JsonSchema $schema): ObjectType
    {
        return $schema->object()->description('Auth credentials object (never returned by get/list).');
    }
}
