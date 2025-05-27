<?php

namespace ProcessMaker\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Mcp\Server\Server;
use Mcp\Server\ServerRunner;
use Mcp\Types\CallToolResult;
use Mcp\Types\GetPromptRequestParams;
use Mcp\Types\GetPromptResult;
use Mcp\Types\ListPromptsResult;
use Mcp\Types\ListToolsResult;
use Mcp\Types\Prompt;
use Mcp\Types\PromptArgument;
use Mcp\Types\PromptMessage;
use Mcp\Types\Role;
use Mcp\Types\TextContent;
use Mcp\Types\Tool;
use Mcp\Types\ToolInputProperties;
use Mcp\Types\ToolInputSchema;
use ProcessMaker\Facades\WorkflowManager;
use ProcessMaker\ImportExport\Exporter;
use ProcessMaker\Models\Process;
use ReflectionClass;

class McpServerCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mcp:server';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run the MCP server';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        \Log::info("Running mcp server");
        // Create a server instance
        $server = new Server('example-server', Log::channel('mcp'));

        // Register tools handlers
        $server->registerHandler('tools/list', function ($params) {
            $tools = [];
            $reflection = new ReflectionClass($this);
            foreach ($reflection->getMethods() as $method) {
                if ($method->isStatic()) {
                    continue;
                }
                $starts = Str::startsWith($method->getName(), 'register');
                if (!$starts) {
                    continue;
                }
                $ends = Str::endsWith($method->getName(), 'Tool');
                if (!$ends) {
                    continue;
                }
                Log::channel('mcp')->info('register tool: ' . $method->getName());
                $tools[] = $this->{$method->getName()}();
            }
            return new ListToolsResult(tools: $tools);
        });

        $server->registerHandler('tools/call', function ($params) {
            $functionName = 'handle' . Str::camel($params->name) . 'Tool';
            if (method_exists($this, $functionName)) {
                $args = $params->jsonSerialize()['arguments'];
                if (empty($args) || !is_array($args)) {
                    $args = [];
                }
                Log::channel('mcp')->info(serialize($args));
                return new CallToolResult([
                    new TextContent(
                        text: $this->{$functionName}(...$args)
                    )
                ]);
            }
            throw new \InvalidArgumentException("Unknown tool: {$params->name}");
        });

        // Register prompt handlers
        $server->registerHandler('prompts/list', function ($params) {
            $prompt = new Prompt(
                name: 'example-prompt',
                description: 'An example prompt template',
                arguments: [
                    new PromptArgument(
                        name: 'arg1',
                        description: 'Example argument',
                        required: true
                    )
                ]
            );
            return new ListPromptsResult([$prompt]);
        });

        $server->registerHandler('prompts/get', function (GetPromptRequestParams $params) {
            $name = $params->name;
            $arguments = $params->arguments;

            if ($name !== 'example-prompt') {
                throw new \InvalidArgumentException("Unknown prompt: {$name}");
            }

            // Get argument value safely
            $argValue = $arguments ? $arguments->arg1 : 'none';

            $prompt = new Prompt(
                name: 'example-prompt',
                description: 'An example prompt template',
                arguments: [
                    new PromptArgument(
                        name: 'arg1',
                        description: 'Example argument',
                        required: true
                    )
                ]
            );

            return new GetPromptResult(
                messages: [
                    new PromptMessage(
                        role: Role::USER,
                        content: new TextContent(
                            text: "Example prompt text with argument: $argValue"
                        )
                    )
                ],
                description: 'Example prompt'
            );
        });

        try {
            // Create initialization options and run server
            $initOptions = $server->createInitializationOptions();
            $runner = new ServerRunner($server, $initOptions, Log::channel('mcp'));
            Log::info("Running mcp server");
            $runner->run();
        } catch (\Throwable $e) {
            $this->error("An error occurred: " . $e->getMessage());
            Log::channel('mcp')->error("Server run failed", ['exception' => $e]);
            return 1;
        }

        return 0;
    }

    private function registerGetTimeTool()
    {
        return new Tool(
            name: 'get_time',
            inputSchema: new ToolInputSchema(
                properties: new ToolInputProperties([]),
                required: []
            ),
            description: 'Get the current server time and timezone'
        );
    }

    private function handleGetTimeTool($params)
    {
        return sprintf(
            "Current time: %s\nTimezone: %s\nversion: 3",
            now()->format('Y-m-d H:i:s'),
            config('app.timezone')
        );
    }

    private function registerGetProcessesTool()
    {
        return new Tool(
            name: 'get_processes',
            inputSchema: new ToolInputSchema(properties: new ToolInputProperties([]), required: []),
        );
    }

    private function handleGetProcessesTool()
    {
        $processes = Process::select('id', 'name')
            ->where('status', 'ACTIVE')
            ->nonSystem()
            ->get()->toArray();

        return json_encode($processes);
    }

    private function registerRunProcessesTool()
    {
        return new Tool(
            name: 'run_processes',
            inputSchema: new ToolInputSchema(
                properties: ToolInputProperties::fromArray([
                'process_id' => [
                    'type' => 'string',
                    'description' => 'The ID of the process to run',
                ]
            ]), required: ['process_id']),
        );
    }

    private function handleRunProcessesTool($process_id)
    {
        try {
            $process = Process::find($process_id);
            $startEventId = $process->start_events[0]['id'];
            $data = WorkflowManager::runProcess($process, $startEventId, []);
            $requestId = $data['_request']['id'];
            $requestUrl = route('requests.show', $requestId);
            return sprintf('Request started: %s\nRequest URL: %s', $requestId, $requestUrl);
        } catch (\Throwable $e) {
            Log::channel('mcp')->error("Error running process: " . $e->getMessage());
            return json_encode([
                'error' => $e->getMessage()
            ]);
        }
    }

    private function registerGetProcessBpmnTool()
    {
        return new Tool(
            name: 'get_process_bpmn',
            inputSchema: new ToolInputSchema(
                properties: ToolInputProperties::fromArray([
                'process_id' => [
                    'type' => 'string',
                    'description' => 'The ID of the process to get the BPMN',
                ]
            ]), required: ['process_id']),
        );
    }

    private function handleGetProcessBpmnTool($process_id)
    {
        $process = Process::find($process_id);
        $bpmn = $process->bpmn;
        return $bpmn;
    }

    // export process to json
    private function registerExportProcessTool()
    {
        return new Tool(
            name: 'export_process',
            inputSchema: new ToolInputSchema(
                properties: ToolInputProperties::fromArray([
                'process_id' => [
                    'type' => 'string',
                    'description' => 'The ID of the process to get the BPMN',
                ],
                'relative_path' => [
                    'type' => 'string',
                    'description' => 'The relative path to the process',
                ]
            ]), required: ['process_id', 'relative_path']),
        );
    }

    private function handleExportProcessTool($process_id, $relative_path)
    {
        $process = Process::find($process_id);
        // Create exporter instance
        $exporter = new Exporter();

        // Export process
        $exporter->exportProcess($process);

        // Get export payload
        $data = $exporter->payload();

        $content = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        // save to file
        $path = base_path($relative_path);
        file_put_contents($path, $content);

        return "Saved to " . $path;
    }
}
