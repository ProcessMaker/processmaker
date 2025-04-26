<?php

namespace ProcessMaker\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Str;

class Api2TypescriptCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:api-typescript
                            {output : Output directory for TypeScript files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Convert OpenAPI JSON specification to TypeScript interfaces, API classes, and composables for Vue applications';

    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    protected array $openapi;

    /**
     * Create a new command instance.
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @return void
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $openapiFile = url('/docs?api-docs.json'); //$this->argument('input');
        $outputDirectory = $this->argument('output');

        // update the api-docs.json file
        Artisan::call('l5-swagger:generate');

        // Parse JSON file
        $openapi = $this->parseJsonFile();
        if (!$openapi) {
            $this->error("Failed to parse OpenAPI JSON file.");
            return 1;
        }
        $this->openapi = $openapi;

        // Create output directory if it doesn't exist
        if (!$this->files->exists($outputDirectory)) {
            $this->files->makeDirectory($outputDirectory, 0755, true);
        }

        // Create subdirectories
        $typesDir = "$outputDirectory/types";
        $apiDir = "$outputDirectory/api";
        $composablesDir = "$outputDirectory/composables";

        foreach ([$typesDir, $apiDir, $composablesDir] as $dir) {
            if (!$this->files->exists($dir)) {
                $this->files->makeDirectory($dir, 0755, true);
            }
        }

        // Extract API info
        $apiTitle = $openapi['info']['title'] ?? 'API';
        $apiDescription = $openapi['info']['description'] ?? 'API Description';
        $apiVersion = $openapi['info']['version'] ?? '1.0.0';

        // Extract tag names
        $tags = [];
        foreach ($openapi['paths'] as $path => $methods) {
            foreach ($methods as $method => $details) {
                if (isset($details['tags']) && is_array($details['tags'])) {
                    foreach ($details['tags'] as $tag) {
                        $tags[$tag] = true;
                    }
                }
            }
        }
        $tags = array_keys($tags);

        $this->generateTypesForTag($openapi, 'types', $typesDir);
        // Process each tag as a separate API client
        foreach ($tags as $tag) {
            $this->generateApiClassForTag($openapi, $tag, $apiDir);
            $this->generateComposableForTag($tag, $composablesDir);
        }

        // Generate index files
        $this->generateIndexFiles($tags, $apiDir, $composablesDir, $typesDir, $outputDirectory);

        $this->info("TypeScript SDK generated successfully in $outputDirectory");
        return 0;
    }

    /**
     * Parse a JSON file
     */
    protected function parseJsonFile()
    {
        $content = file_get_contents(storage_path('api-docs/api-docs.json'));
        $data = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error("JSON parse error: " . json_last_error_msg());
            return null;
        }

        return $data;
    }

    /**
     * Map OpenAPI type to TypeScript type
     */
    protected function mapSwaggerTypeToTypescript($type)
    {
        switch ($type) {
            case 'integer':
                return 'number';
            case 'number':
                return 'number';
            case 'boolean':
                return 'boolean';
            case 'array':
                return 'any[]';
            case 'object':
                return 'Record<string, any>';
            default:
                return 'string';
        }
    }

    /**
     * Generate TypeScript interfaces for schemas related to a tag
     */
    protected function generateTypesForTag($openapi, $tag, $outputDir)
    {
        $tagLower = strtolower($tag);
        $interfaces = [];
        $queryParamInterfaces = [];

        // Get all schemas from components
        $schemas = $openapi['components']['schemas'] ?? [];

        // Process paths to find related schemas and generate query param interfaces
        foreach ($openapi['paths'] as $path => $methods) {
            foreach ($methods as $method => $details) {
                // Skip if not related to current tag
                // if (!isset($details['tags']) || !in_array($tag, $details['tags'])) {
                //     continue;
                // }

                // Generate query param interfaces for GET methods
                if (isset($details['parameters'])) {
                    $queryParamInterface = $this->generateQueryParamInterface($details['parameters'], $tagLower, $details['operationId']);
                    if ($queryParamInterface) {
                        $queryParamInterfaces[] = $queryParamInterface;
                    }
                }

                // Check request body schema reference
                if (isset($details['requestBody']['content']['application/json']['schema']['$ref'])) {
                    $schemaRef = $details['requestBody']['content']['application/json']['schema']['$ref'];
                    $this->collectSchemaFromRef($schemaRef, $schemas);
                }

                // Generate response type for operationId
                $responseSchema = $details['responses']['200']['content']['application/json']['schema'] ??
                    $details['responses']['201']['content']['application/json']['schema'] ?? null;
                if ($responseSchema && isset($responseSchema['properties'])) {
                    $queryParamInterfaces[] = $this->generateResponseType($details['operationId'], $responseSchema, $tagLower);
                }

                // Check response schema references
                foreach ($details['responses'] ?? [] as $response) {
                    if (isset($response['content']['application/json']['schema']['$ref'])) {
                        $schemaRef = $response['content']['application/json']['schema']['$ref'];
                        $this->collectSchemaFromRef($schemaRef, $schemas);
                    }

                    // Check for arrays with items having refs
                    if (isset($response['content']['application/json']['schema']['properties']['data']['items']['$ref'])) {
                        $schemaRef = $response['content']['application/json']['schema']['properties']['data']['items']['$ref'];
                        $this->collectSchemaFromRef($schemaRef, $schemas);
                    }
                }
            }
        }

        // Generate TypeScript interfaces from schemas
        foreach ($schemas as $name => $schema) {
            $interfaces[] = $this->generateInterface($name, $schema);
        }

        // Write interfaces to file
        $data = [
            'interfaces' => array_filter($interfaces),
            'queryParamInterfaces' => array_filter($queryParamInterfaces),
        ];

        $content = Blade::render($this->getStub('types'), $data);
        $this->files->put("$outputDir/types.ts", $content);
    }

    /**
     * Generate TypeScript API class for a tag
     */
    protected function generateApiClassForTag($openapi, $tag, $outputDir)
    {
        $tagLower = lcfirst(Str::camel($tag));
        $className = "ProcessMaker" . ucfirst($tagLower) . "Api";

        // Import interfaces
        $imports = [
            // ucfirst($tagLower),
            // fix the editable name
            // "Editable" . ucfirst($tagLower),
            // "PaginatedResponse"
        ];

        // Add query param interfaces to imports
        foreach ($openapi['paths'] as $path => $methods) {
            foreach ($methods as $method => $details) {
                if (!isset($details['tags']) || !in_array($tag, $details['tags'])) {
                    continue;
                }

                $operationId = $details['operationId'] ?? '';
                if ($operationId) {
                    if (isset($details['parameters'])) {
                        // Import the query param interface
                        $interfaceName = ucfirst($this->camelCase($operationId)) . "QueryParams";
                        $interfaceCode = $this->generateQueryParamInterface($details['parameters'], $tagLower, $details['operationId']);
                        if ($interfaceCode) {
                            $imports[] = $interfaceName;
                        }
                    }

                    // Import the paginated response type and its type
                    $reference = $this->getResponseReference($operationId, $details['responses']);
                    if ($reference) {
                        $imports[] = $reference;
                    }
                }
            }
        }

        // Generate methods for each path
        $methods = [];
        foreach ($openapi['paths'] as $path => $pathMethods) {
            foreach ($pathMethods as $method => $details) {
                // Skip if not related to current tag
                if (!isset($details['tags']) || !in_array($tag, $details['tags'])) {
                    continue;
                }

                $operationId = $details['operationId'] ?? '';
                if ($operationId) {
                    $methods[] = $this->generateMethod($method, $path, $details, $imports);
                }
            }
        }

        $data = [
            'tagLower' => $tagLower,
            'className' => $className,
            'imports' => array_unique($imports),
            'methods' => $methods,
            'helper' => $this,
        ];

        // Generate the api.ts file
        $content = Blade::render($this->getStub('api'), $data);
        $this->files->put("$outputDir/$tagLower.api.ts", $content);

        // Generate the api.spec.ts file
        $content = Blade::render($this->getStub('api-spec'), $data);
        $this->files->put("$outputDir/$tagLower.api.spec.ts", $content);
    }

    public function mockResponse(array $method)
    {
        return $method['responseExample'];
    }

    public function mockParamsArray(array $method, bool $camelCase): array
    {
        $params = [];
        // Add path parameters
        foreach ($method['pathParams'] as $param) {
            $key = Str::camel($param['name']);
            $params[$key] = $this->mockValue($param);
        }
        // Add request body parameter for POST/PUT methods
        if (!empty($method['requestBody'])) {
            if (isset($method['requestBody']['content']['multipart/form-data']['schema'])) {
                $params['$body'] = $this->mockFromSchema($method['requestBody']['content']['multipart/form-data']['schema']);
            } elseif (isset($method['requestBody']['content']['application/json']['schema'])) {
                $schema = $method['requestBody']['content']['application/json']['schema'];
                $params['$body'] = $this->mockFromSchema($schema);
            } else {
                throw new Exception("Failed to mock request body for " . $method['operationId']);
            }
        } elseif ($method['httpMethod'] === 'post' || $method['httpMethod'] === 'put' || $method['httpMethod'] === 'patch') {
            $params['$body'] = (object) [];
        }
        $queryParams = [];
        foreach ($method['queryParams'] as $param) {
            $key = $camelCase ? Str::camel($param['name']) : $param['name'];
            if (isset($param['$ref'])) {
                $queryParams[$key] = $this->mockValue($this->getSchemaByRef($param['$ref']));
            } else {
                $queryParams[$key] = $this->mockValue($param);
            }
        }
        if (!empty($queryParams)) {
            $params['$queryParams'] = $queryParams;
        }
        return $params;
    }

    public function mockParams(array $method)
    {
        $params = $this->mockParamsArray($method, true);
        foreach ($params as $key => $value) {
            $params[$key] = $this->json($value, 6);
        }
        return implode(', ', $params);
    }

    private function getSchemaByRef(string $ref)
    {
        $refs = str_replace('/', '.', substr($ref, 2));
        return Arr::get($this->openapi, $refs, null);
    }

    public function mockUrl(array $method)
    {
        $params = $this->mockParamsArray($method, false);
        // replace ${param} with the value
        $path = $method['apiPath'];
        foreach ($params as $key => $value) {
            if (is_array($value) || is_object($value)) {
                continue;
            }
            $path = str_replace('${' . $key . '}', urlencode($value), $path);
        }
        // Add the query params to the path from $queryParams
        if (isset($params['$queryParams'])) {
            $first = true;
            foreach ($params['$queryParams'] as $key => $value) {
                if (is_array($value)) {
                    $value = implode(',', $value);
                }
                $path .= ($first ? '?' : '&') . urlencode($key) . '=' . urlencode($value);
                $first = false;
            }
        }
        $arguments = "'{$path}'";
        if (isset($params['$body'])) {
            $arguments .= ", " . $this->json($params['$body'], 6);
        }
        return $arguments;
    }

    public function json($value, int $leftMargin = 0)
    {
        if ($leftMargin > 0) {
            $json = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
            // change the indent to 2 spaces
            $json = preg_replace_callback(
                '/^(?: {4})+/m',
                function($m) {
                    return str_repeat(' ', 2 * (strlen($m[0]) / 4));
                },
                $json
            );
            // add the left margin
            $json = str_replace("\n", "\n" . str_repeat(' ', $leftMargin), $json);
            return $json;
        }
        return json_encode($value, JSON_UNESCAPED_SLASHES);
    }

    private function getResponseReference(string $operationId, array $responses)
    {
        $responseSchema = $responses['200']['content']['application/json']['schema'] ??
            $responses['201']['content']['application/json']['schema'] ??
            $responses['202']['content']['application/json']['schema'] ??
            $responses['203']['content']['application/json']['schema'] ??
            $responses['204']['content']['application/json']['schema'] ?? null;

        if (!$responseSchema) {
            return null;
        }
        if ($responseSchema && isset($responseSchema['properties'])) {
            $interfaceName = ucfirst($this->camelCase($operationId)) . "Response";
            return $interfaceName;
        } elseif ($responseSchema) {
            return $this->getSchemaNameFromRef($responseSchema['$ref']);
        }

        throw new \Exception("Failed to find response schema for $operationId");
    }

    private function getResponseExample(string $operationId, array $responses)
    {
        $responseSchema = $responses['200']['content']['application/json']['schema'] ??
            $responses['201']['content']['application/json']['schema'] ??
            $responses['202']['content']['application/json']['schema'] ?? null;

        if (empty($responseSchema)) {
            return (object) [];
        }

        return $this->mockFromSchema($responseSchema);
    }

    private function mockFromSchema(array $responseSchema)
    {
        $response = [];
        if (isset($responseSchema['properties'])) {
            foreach ($responseSchema['properties'] as $key => $value) {
                if (empty($value)) {
                    throw new Exception("Empty value for " . $key);
                }
                $response[$key] = $this->mockValue($value);
            }
        } elseif (isset($responseSchema['$ref'])) {
            return $this->mockFromSchema($this->getSchemaByRef($responseSchema['$ref']));
        }
        return (object) $response;
    }

    private function mockValue(array $value)
    {
        if (!isset($value['type']) && isset($value['schema'])) {
            $value = $value['schema'];
        }
        if (!isset($value['type']) && isset($value['allOf'])) {
            return $this->mockValueFromObject($value);
        }
        if (!isset($value['type']) && isset($value['$ref'])) {
            return $this->mockValueFromObject($value);
        }
        if (!isset($value['type'])) {
            throw new \Exception("Failed to mock value for " . json_encode($value));
        }
        switch ($value['type']) {
            case 'string':
                switch ($value['format'] ?? null) {
                    case 'id':
                        return '1';
                    case 'date-time':
                        return '2025-04-23T00:00:00Z';
                    default:
                        return 'foo';
                }
            case 'integer':
            case 'number':
                    return 1;
            case 'boolean':
                return true;
            case 'array':
                if (isset($value['items']['$ref'])) {
                    return [$this->mockValue($this->getSchemaByRef($value['items']['$ref']))];
                    $ref = explode('/', $value['items']['$ref']);
                    $ref = end($ref);
                    return [$this->mockValue($this->openapi['components']['schemas'][$ref])];
                }
                return [1, 2, 3];
            case 'object':
                return $this->mockValueFromObject($value);
            default:
                return $value;
        }
    }

    private function mockValueFromObject(array $value)
    {
        $withProperties = $this->findItemWithKeyInArray($value['allOf'] ?? [], 'properties');
        $withRef = $this->findItemWithKeyInArray($value['allOf'] ?? [], '$ref');
        if (isset($value['allOf']) && $withProperties) {
            return $this->mockFromSchema($withProperties);
        } elseif (isset($value['allOf']) && $withRef) {
            $ref = explode('/', $withRef['$ref']);
            $ref = end($ref);
            return $this->mockValue($this->openapi['components']['schemas'][$ref]);
        } elseif (isset($value['properties'])) {
            return $this->mockFromSchema($value);
        }
        return (object) ['foo' => $value];
    }

    private function findItemWithKeyInArray(array $array, string $key)
    {
        foreach ($array as $item) {
            if (isset($item[$key])) {
                return $item;
            }
        }
        return null;
    }

    private function findResponseSchema(string $operationId, array $responseSchema, array $imports)
    {
        $responseProperties = $responseSchema['properties'] ?? null;
        if ($responseProperties) {
            $refs = $this->findRefs($responseProperties);
            foreach ($refs as $ref) {
                $imports[] = $this->getSchemaNameFromRef($ref);
            }
        } elseif (isset($responseSchema['$ref'])) {
            $imports[] = $this->getSchemaNameFromRef($responseSchema['$ref']);
        } else {
            throw new \Exception("Failed to find response schema for $operationId");
        }

        return $imports;
    }

    private function findRefs(array $schema)
    {
        $refs = [];
        foreach ($schema as $key => $value) {
            if (isset($value['$ref'])) {
                $refs[] = $value['$ref'];
            }
            if (is_array($value)) {
                $refs = array_merge($refs, $this->findRefs($value));
            }
        }

        return $refs;
    }

    /**
     * Generate a TypeScript method for an API endpoint
     */
    protected function generateMethod($httpMethod, $path, $details, &$imports)
    {
        $operationId = $details['operationId'];
        $methodName = $this->camelCase($operationId);
        $summary = $details['summary'] ?? '';
        $parameters = $details['parameters'] ?? [];
        $requestBody = $details['requestBody'] ?? null;
        $responses = $details['responses'] ?? [];

        $parameters = $this->filterValidParameters($parameters);
        // Determine method signature based on parameters and request body
        $paramList = [];
        $pathParams = [];
        $queryParams = [];

        // Process path parameters
        foreach ($parameters as $param) {
            if ($param['in'] === 'path') {
                $pathParams[] = $param;
                $paramType = $this->mapSwaggerTypeToTypescript($param['schema']['type'] ?? 'string');
                $paramList[] = $this->camelCase($param['name']) . ": " . $paramType;
            } elseif ($param['in'] === 'query') {
                $queryParams[] = $param;
            }
        }

        // Add request body parameter for POST/PUT methods
        if (($httpMethod === 'post' || $httpMethod === 'put' || $httpMethod === 'patch')) {
            if (isset($requestBody['content']['application/json']['schema']['$ref'])) {
                $schemaRef = $requestBody['content']['application/json']['schema']['$ref'];
                $schemaName = $this->getSchemaNameFromRef($schemaRef);
                $imports[] = $schemaName;
                $paramList[] = "data: " . $schemaName;
            } else {
                $paramList[] = "data: Record<string, unknown>";
            }
        }

        // Handle GET methods with query parameters
        if (!empty($queryParams)) {
            $queryParamTypeName = ucfirst($this->camelCase($operationId)) . "QueryParams";
            $paramList[] = "params?: " . $queryParamTypeName;
        }

        // Determine return type
        /*$returnType = 'void';
        foreach ($responses as $code => $response) {
            if ("$code"[0] === '2') {  // 2xx response
                if (isset($response['content']['application/json']['schema'])) {
                    $schema = $response['content']['application/json']['schema'];

                    if (isset($schema['$ref'])) {
                        $returnType = $this->getSchemaNameFromRef($schema['$ref']);
                    } elseif (isset($schema['type']) && $schema['type'] === 'object' && isset($schema['properties']['data']['type']) && $schema['properties']['data']['type'] === 'array') {
                        // Handle paginated response
                        if (isset($schema['properties']['data']['items']['$ref'])) {
                            $itemType = $this->getSchemaNameFromRef($schema['properties']['data']['items']['$ref']);
                            $returnType = "PaginatedResponse<" . $itemType . ">";
                        } else {
                            $returnType = "PaginatedResponse<any>";
                        }
                    } else {
                        $returnType = "any";
                    }
                }
                break;
            }
        }*/

        $returnType = $this->getResponseReference($operationId, $responses);
        $responseExample = $this->getResponseExample($operationId, $responses);


        // Build path with parameters
        $apiPath = $path;
        foreach ($pathParams as $param) {
            $apiPath = str_replace('{' . $param['name'] . '}', '${' . $this->camelCase($param['name']) . '}', $apiPath);
        }

        return [
            'methodName' => $methodName,
            'summary' => $summary,
            'httpMethod' => $httpMethod,
            'paramList' => $paramList,
            'returnType' => $returnType,
            'responseExample' => $responseExample,
            'apiPath' => $apiPath,
            'queryParams' => $queryParams,
            'pathParams' => $pathParams,
            'requestBody' => $requestBody,
        ];
    }

    private function filterValidParameters(array $parameters)
    {
        // add in=query to the parameters if it is not set
        foreach ($parameters as $i => $param) {
            if (isset($param['ref']) || isset($param['$ref'])) {
                $ref = $param['ref'] ?? $param['$ref'];
                $refParts = explode('/', $ref);
                $refName = end($refParts);
                $parameters[$i]['in'] = 'query';
                $parameters[$i]['name'] = $refName;
            } elseif (!isset($param['in'])) {
                $parameters[$i]['in'] = 'query';
                dd($parameters[$i]);
            }
        }
        return $parameters;
        return array_filter($parameters, function ($param) {
            return isset($param['in']);
        });
    }

    /**
     * Generate a TypeScript composable for a tag
     */
    protected function generateComposableForTag($tag, $outputDir)
    {
        $tagLower = Str::camel($tag);
        $className = "ProcessMaker" . ucfirst($tagLower) . "Api";
        $hookName = "useProcessMaker" . ucfirst($tagLower);

        $data = [
            'tagLower' => $tagLower,
            'className' => $className,
            'hookName' => $hookName
        ];

        $content = Blade::render($this->getStub('composable'), $data);
        $this->files->put("$outputDir/" . $hookName . ".ts", $content);
    }

    /**
     * Generate index files for the SDK
     */
    protected function generateIndexFiles($tags, $apiDir, $composablesDir, $typesDir, $outputDir)
    {
        // API index
        $apiClasses = [];
        foreach ($tags as $tag) {
            $tagLower = Str::camel($tag);
            $className = "ProcessMaker" . ucfirst($tagLower) . "Api";
            $apiClasses[] = [
                'className' => $className,
                'tagLower' => $tagLower
            ];
        }

        $data = ['apiClasses' => $apiClasses];
        $content = Blade::render($this->getStub('api-index'), $data);
        $this->files->put("$apiDir/index.ts", $content);

        // Composables index
        $hooks = [];
        foreach ($tags as $tag) {
            $tagLower = Str::camel($tag);
            $hookName = "useProcessMaker" . ucfirst($tagLower);
            $hooks[] = $hookName;
        }

        $data = ['hooks' => $hooks];
        $content = Blade::render($this->getStub('composables-index'), $data);
        $this->files->put("$composablesDir/index.ts", $content);

        // Main SDK index
        $data = ['tags' => $tags];
        $content = Blade::render($this->getStub('main-index'), $data);
        $this->files->put("$outputDir/index.ts", $content);

        // API Response type
        $content = Blade::render($this->getStub('api-response'), []);
        $this->files->put("$typesDir/api.ts", $content);

        // README
        $data = [
            'tags' => $tags,
            'firstTag' => !empty($tags) ? $tags[0] : null
        ];
        $content = Blade::render($this->getStub('readme'), $data);
        $this->files->put("$outputDir/README.md", $content);
    }

    /**
     * Generate interface from OpenAPI schema
     */
    protected function generateInterface($name, $schema)
    {
        $properties = $schema['properties'] ?? [];

        $interfaceName = $this->formatInterfaceName($name);

        if (empty($properties)) {
            return "export interface $interfaceName {\n  [key: string]: any;\n}";
        }

        $interface = "export interface $interfaceName {\n";

        foreach ($properties as $propName => $propDetails) {
            $interface .= "  " . $propName;

            // Check if property is required
            $required = $schema['required'] ?? [];
            if (!in_array($propName, $required)) {
                $interface .= "?";
            }

            $interface .= ": " . $this->getTypescriptType($propDetails) . ";\n";
        }

        $interface .= "}";

        return $interface;
    }

    /**
     * Generate query param interface for GET endpoints
     */
    protected function generateQueryParamInterface($parameters, $tagLower, $operationId)
    {
        $parameters = $this->filterValidParameters($parameters);

        $queryParams = array_filter($parameters, function ($param) {
            return $param['in'] === 'query';
        });

        if (empty($queryParams)) {
            return "";
        }

        $interfaceName = ucfirst($this->camelCase($operationId)) . "QueryParams";

        $interface = "export interface " . $interfaceName . " {\n";

        foreach ($queryParams as $param) {
            $refType = $this->getTypeOf($param);
            $interface .= "  " . $this->camelCase($param['name']) . "?: " . $this->mapSwaggerTypeToTypescript($refType) . ";\n";
        }

        $interface .= "}";

        return $interface;
    }

    private function getTypeOf(array $definition)
    {
        if (isset($definition['schema']['type'])) {
            return $definition['schema']['type'];
        }
        if (isset($definition['$ref'])) {
            return $this->getTypeOf($this->getSchemaByRef($definition['$ref']));
        }

        throw new Exception("Failed to get type of " . json_encode($definition));
    }

    private function generateResponseType($operationId, $responseSchema, $outputDir)
    {
        $interfaceName = ucfirst($this->camelCase($operationId)) . "Response";

        $code = "export interface $interfaceName {\n";

        foreach ($responseSchema['properties'] as $propName => $propDetails) {
            $code .= "  " . $propName . ": " . $this->getTypescriptType($propDetails) . ";\n";
        }

        $code .= "}";

        return $code;
    }

    /**
     * Format interface name to PascalCase
     */
    protected function formatInterfaceName($name)
    {
        $name = str_replace('#/components/schemas/', '', $name);

        // Convert snake_case to PascalCase
        return Str::studly($name);
    }

    /**
     * Convert swagger type to TypeScript type
     */
    public function getTypescriptType($property)
    {
        if ($property === 'true' || $property === true) {
            return 'any';
        }
        if (isset($property['$ref'])) {
            $schema = $this->getSchemaByRef($property['$ref']);
            if (isset($schema['schema'])) {
                return $this->getTypescriptType($schema['schema']);
            }
            return $this->getSchemaNameFromRef($property['$ref']);
        }
        if (!isset($property['type']) && isset($property['schema']['type'])) {
            return $this->getTypescriptType($property['schema']);
        }
        if (!isset($property['type'])) {
            throw new Exception("Failed to get type of " . json_encode($property));
        }
        $type = $property['type'];

        switch ($type) {
            case 'integer':
                return 'number';
            case 'number':
                return 'number';
            case 'boolean':
                return 'boolean';
            case 'array':
                if (isset($property['items']['$ref'])) {
                    $itemType = $this->getSchemaNameFromRef($property['items']['$ref']);
                    return $itemType . '[]';
                } else {
                    $itemType = $this->getTypescriptType($property['items'] ?? ['type' => 'string']);
                    return $itemType . '[]';
                }
            case 'object':
                if (isset($property['additionalProperties'])) {
                    $valueType = $this->getTypescriptType($property['additionalProperties']);
                    return "Record<string, $valueType>";
                }
                return 'Record<string, any>';
            default:
                return 'string';
        }
    }

    /**
     * Get schema name from reference
     */
    protected function getSchemaNameFromRef($ref)
    {
        $parts = explode('/', $ref);
        return $this->formatInterfaceName(end($parts));
    }

    /**
     * Collect schema from reference for later processing
     */
    protected function collectSchemaFromRef($ref, &$schemas)
    {
        return;
        // $schemaName = $this->getSchemaNameFromRef($ref);
        // if ($schemaName !== 'UpdateUserGroups') {
        //     return;
        // }
        // dump($schemaName, $ref);
    }

    /**
     * Get parameter value with appropriate conversion for TypeScript
     */
    protected function getParamValueConversion($paramName, $param)
    {
        $type = $param['schema']['type'] ?? 'string';

        if ($type === 'integer' || $type === 'number') {
            return "params.$paramName.toString()";
        }

        return "params.$paramName";
    }

    /**
     * Convert string to camelCase
     */
    protected function camelCase($str)
    {
        return Str::camel($str);
    }

    /**
     * Get stub template content
     */
    protected function getStub($type)
    {
        return $this->files->get(resource_path("stubs/api2typescript/{$type}.blade.php"));
    }
}
