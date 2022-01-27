<?php

namespace ProcessMaker\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use ProcessMaker\Jobs\RunScriptTask;
use ProcessMaker\Jobs\RunServiceTask;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\ProcessRequestLock;
use ProcessMaker\Models\ScheduledTask;
use ProcessMaker\Models\Script;

class GarbageCollector extends Command
{
    private $MAX_SCRIPT_TIMEOUT = 3600;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = "
        processmaker:garbage-collect
    ";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Resolve issues with unhandled errors when executing requests';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->writeln("Running Garbage Collector\n", 'info', true);

        $this->processHaltedScripts();

        $this->processUnhandledErrors();

        $this->processDuplicatedTimerEvents();
    }

    private function processHaltedScripts()
    {
        $tasks = $this->getTaskList();

        if (!$tasks->count()) {
            $this->writeln("No failing script or service tasks found.\n", 'line');
            return;
        }

        $this->writeln("Halted scripts found.\n", 'line');

        $bar = $this->output->createProgressBar($tasks->count());
        $bar->start();

        foreach ($tasks as $token) {
            $bar->advance();
            if (!$this->canRunScriptOfToken($token)) {
                $this->writeln("Script of the token { $token->id } is still running...\n", 'line', true);
                continue;
            }
            $instance = $token->processRequest;
            $process = $instance->process;

            $token->created_at = Carbon::now();
            $token->updated_at = Carbon::now();
            $token->save();

            if ($token->element_type === 'scriptTask') {
                $this->writeln("Dispatching script of token { $token->id } \n", 'line', true);
                RunScriptTask::dispatch($process, $instance, $token, []);
            }
            if ($token->element_type === 'serviceTask') {
                $this->writeln("Dispatching service task of token { $token->id } \n", 'line', true);
                RunServiceTask::dispatch($process, $instance, $token, []);
            }
        }
        $bar->finish();
    }

    private function processUnhandledErrors()
    {
        $fileName = storage_path('app/private') . '/unhandled_error.txt';
        if (file_exists($fileName)) {

            $this->writeln("Unhandled errors file found...", 'info', true);

            $tokens = [];
            if ($file = fopen($fileName, "r")) {
                while(!feof($file)) {
                    $token = fgets($file);
                    $tokens[] = trim($token);
                }
                fclose($file);
            }

            foreach ($tokens as $tokenId) {
                $token = ProcessRequestToken::find($tokenId);

                if (!$this->canRunScriptOfToken($token)) {
                    $this->writeln("Script of the token { $token->id } is still running...\n", 'line', true);
                    continue;
                }

                if ($token) {
                    $token->created_at = Carbon::now();
                    $token->updated_at = Carbon::now();
                    $token->save();

                    $instance = $token->processRequest;
                    $process = $instance->process;

                    if ($token->element_type === 'scriptTask') {
                        $this->writeln("Dispatching script of token { $token->id } \n", 'line', true);
                        RunScriptTask::dispatch($process, $instance, $token, []);
                    }
                    if ($token->element_type === 'serviceTask') {
                        $this->writeln("Dispatching service task of token { $token->id } \n", 'line', true);
                        RunServiceTask::dispatch($process, $instance, $token, []);
                    }
                }
            }
            unlink($fileName);
        }
    }

    private function processDuplicatedTimerEvents()
    {
        // Intermediate Timer Events should have just one scheduled task
        $scheduled = ScheduledTask::
            select(
                'process_request_id',
                'configuration->element_id as element_id',
                DB::raw('count(*)')
            )
            ->groupBy('process_request_id', 'configuration->element_id')
            ->having('count(*)', '>', 1)
            ->get();

        if ($scheduled->count() > 0) {
            $this->writeln("Duplicated timer events found...", 'info', true);
        }

        foreach($scheduled as $schedule) {
            $this->writeln("Cleaning scheduled task { $$schedule->id } 
                of token={ $schedule->process_request_token_id },
                request={ $schedule->process_request_id },  
                element={ $schedule->process_id }, 
                process { $schedule->process_id }", 'line', true);

            $maxId = ProcessRequestToken::where('process_request_id', $schedule->process_request_id)
                ->where('element_id', $schedule->element_id)
                ->where('status', 'ACTIVE')
                ->max('id');

            ScheduledTask::where('process_request_id', $schedule->request_id)
                ->where('process_request_token_id', '<>', $maxId)
                ->where('configuration->element_id', $schedule->element_id)
                ->delete();

            ProcessRequestToken::where('id', '<>', $maxId)
                ->where('process_request_id', $schedule->process_request_id)
                ->where('element_id', $schedule->element_id)
                ->delete();

            $maxScheduleId = ScheduledTask::where('process_request_id', $schedule->process_request_id)
                ->where('configuration->element_id', $schedule->element_id)
                ->max('id');

            ScheduledTask::where('process_request_id', $schedule->process_request_id)
                ->where('id', '<>', $maxScheduleId)
                ->where('configuration->element_id', $schedule->element_id)
                ->delete();
        }
    }

    private function getTaskList()
    {
        $tasks = ProcessRequestToken::whereIn('status', array('FAILING', 'ACTIVE'))->whereIn('element_type', ['scriptTask', 'serviceTask']);
        return $tasks->get();
    }

    private function canRunScriptOfToken($token)
    {
        if ($token === null || $token->getBpmnDefinition() === null) {
            return true;
        }
        $scriptId = $token->getBpmnDefinition()->getAttribute('pm:scriptRef');
        $script = Script::find($scriptId);
        $delta = time() - strtotime($token->created_at);

        if (empty($script->timeout) || $script->timeout === 0) {
           return $delta >  $this->MAX_SCRIPT_TIMEOUT;
        }

        return $delta > $script->timeout;
    }

    private function writeln($message, $type, $toLog = false)
    {
        $this->{$type}($message);
        if ($toLog) {
            Log::Info("Garbage Collector: " . $message);
        }
    }
}
