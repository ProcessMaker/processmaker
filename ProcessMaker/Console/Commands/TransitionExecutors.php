<?php

namespace ProcessMaker\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;
use ProcessMaker\Enums\ScriptExecutorType;
use ProcessMaker\Models\ScriptExecutor;
use ProcessMaker\Services\ScriptMicroserviceService;
use WebSocket\Client;

class TransitionExecutors extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'processmaker:transition-executors 
                            {--uuid=* : The script executor UUID}
                            {--T|timeout= : The timeout for the broadcasting wait}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Transition non-default script executor(s) to the Script Microservice';

    public function __construct(private ScriptMicroserviceService $scriptMicroserviceService)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * Always talks to the microservice, even when SCRIPT_MICROSERVICE_ENABLED is false.
     */
    public function handle(): int
    {
        $key = config('script-runner-microservice.broadcasting.app_key');
        $host = config('script-runner-microservice.broadcasting.host');

        $url = sprintf(
            'wss://%s/app/%s?protocol=7&client=php&version=1.0',
            $host,
            $key
        );

        // Get the optional timeout
        $timeout = $this->option('timeout') ?? 300;

        if ((int)$timeout <= 60) {
            $this->error('Timeout must be a greater or equal to 60');

            return 1;
        }

        // Get the optional uuid
        $uuid = $this->option('uuid');

        $executors = $this->getExecutors($uuid);

        if ($executors->isEmpty()) {
            $this->warn('No script executors found to transition.');

            return 0;
        }

        $client = new Client($url);
        $client->setTimeout($timeout);

        $index = 0;
        $total = $executors->count();

        if ($total > 0) {
            do {
                $this->info("Transitioning executor {$executors[$index]->uuid} ({$executors[$index]->language}) to the microservice...");

                try {
                    $response = $this->scriptMicroserviceService->updateCustomExecutor($executors[$index]);
                    Log::debug('Response', ['response' => $response]);
                    $status = strtolower((string)($response['status'] ?? ''));

                    // Send subscription message (Pusher protocol example)
                    $client->send(json_encode([
                        'event' => 'pusher:subscribe',
                        'data' => [
                            'channel' => "build-image-" . $executors[$index]->uuid
                        ]
                    ]));

                    $running = true;
                    $error = false;
                    // Listen for messages
                    while ($running) {
                        $message = json_decode($client->receive(), true);
                        $data = $message['data'] ?? '';
                        switch ($message['event']) {
                            case 'build-image':
                                $this->line($data);
                                break;
                            case 'build-finished':
                                $this->info("Build finished for executor {$executors[$index]->uuid} - {$data}");
                                $running = false;
                                break;
                            case 'build-error':
                                $this->error("Error occurred while building image for executor {$executors[$index]->uuid} - {$data}");
                                $error = true;
                                $running = false;
                                break;
                        }
                    }

                    if (!$error) {
                        $this->info("Executor {$executors[$index]->uuid} transitioned successfully." . PHP_EOL);
                    }

                } catch (RequestException $e) {
                    $this->error("Request failed for executor {$executors[$index]->uuid}");
                    $this->line(PHP_EOL);
                    $this->line($e->response?->body() ?: $e->getMessage());
                } catch (\Throwable $e) {
                    $this->error("Transition failed for executor {$executors[$index]->uuid}");
                    $this->line(PHP_EOL);
                    $this->line($e->getMessage());
                }

                $client->close();
                $index++;

            } while ($index < $total);
        }


        return 0;
    }

    private function getExecutors($uuid): Collection
    {
        $query = ScriptExecutor::query()
            ->where("is_system", 0)
            ->where(function ($query) {
                $query
                    ->whereNotIn("title", [
                        "PHP Executor",
                        "Node Executor",
                        "Python Executor",
                        "C# Executor",
                        "Java Executor"
                    ])
                    ->orWhereNotIn("description", [
                        "Default PHP Executor",
                        "Default Javascript/Node Executor",
                        "Default Python Executor",
                        "Default C# Executor",
                        "Default Java Executor"
                    ]);
            })
            ->whereNotIn("language", ["php-nayra", "lua", "javascript-ssr", "sql"])
            ->whereNotNull("config")
            ->where(function ($query) {
                $query
                    ->where("type", ScriptExecutorType::Custom)
                    ->orWhereNull("type");
            });

        if (is_array($uuid) && !empty($uuid)) {
            $query->whereIn("uuid", $uuid);
        } else if (is_string($uuid)) {
            $query->where("uuid", $uuid);
        }

        return $query->get();
    }
}
