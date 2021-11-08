<?php

namespace ProcessMaker\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessCategory;
use ProcessMaker\Package\SavedSearch\Models\SavedSearchReport;
use StdClass;
use Throwable;

class SsrReports extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ssr:report
                            {--start= : The start date of the report in YYYY/MM/DD}
                            {--end=   : The end date of the report in YYYY/MM/DD}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a report on the number of SSR tasks';

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
        $dates = $this->parseDates();
        
        $keys = [
            'connector-pdf-print/processmaker-communication-pdf-print', 
            'connector-send-email/processmaker-communication-email-send',
            'package-actions-by-email/sub-process',
        ];
        $ssrProcesses = [];
        $totalSsrTasks = [];

        $systemCategoryId = ProcessCategory::where('is_system', true)->pluck('id');
        $processes = Process::where('process_category_id', '!=', $systemCategoryId)->get()->all();
        
        foreach($processes as $process) {
            $bpmn = $process->bpmn;
            $xml = simplexml_load_string($bpmn);
            $serviceTasks = $xml->xpath('//bpmn:serviceTask');
            foreach($serviceTasks as $serviceTask) {
                if (in_array($serviceTask->attributes()->implementation, $keys) )  {
                    $nodeId = $serviceTask->attributes()->id;
                    $obj = new StdClass();
                    $obj->process_id = $process->id;
                    $obj->node_id = $nodeId;
                    array_push($ssrProcesses, $obj);
                }
            }
            
            // ABE
            $callActivities = $xml->xpath('//bpmn:callActivity');
            foreach($callActivities as $callActivity) {
                $calledElement = $callActivity->attributes()->calledElement;
                $subprocessId = explode('-', $calledElement)[1];
                $subprocess = Process::where('id', $subprocessId)->first();
                if (isset($subprocess['package_key']) && in_array($subprocess->package_key, $keys) )  {
                    $obj = new StdClass();
                    $obj->process_id = $subprocess->id;
                    $obj->node_id = 'node_13'; // Will always be node_13 for ABE subprocess
                    array_push($ssrProcesses, $obj);
                }
            }
        }
        
        foreach($ssrProcesses as $ssrProcess) {
            $query = ProcessRequestToken::where('process_id', $ssrProcess->process_id)
                ->where("element_id", $ssrProcess->node_id);
            
            if ($dates['start']) {
                $query->where('created_at', '>=', $dates['start']);
            }
            
            if ($dates['end']) {
                $query->where('created_at', '<=', $dates['end']);
            }
                
            $total = $query->count();
                
            array_push($totalSsrTasks, $total);
        }
        
        // Saved Search Scheduled Reports
        $scheduledReports = SavedSearchReport::where('screen_id', '!=', null);
        if ($dates['start']) {
            $scheduledReports->where('created_at', '>=', $dates['start']);
        }

        if ($dates['end']) {
            $scheduledReports->where('created_at', '<=', $dates['end']);
        }
        $totalScheduledReports = $scheduledReports->count();

        $total = array_sum($totalSsrTasks) + $totalScheduledReports;
        
        $this->info('Total SSR Tasks: ' . $total );
    }
    
    /**
     * Parse any input dates.
     *
     * @return array
     */
    public function parseDates()
    {
        $dates = [
            'start' => null,
            'end' => null,
        ];
        
        if ($this->option('start')) {
            $dates['start'] = $this->parseDate($this->option('start'));
        }
        
        if ($this->option('end')) {
            $dates['end'] = $this->parseDate($this->option('end'));
        }
        
        return $dates;
    }
    
    /**
     * Parse an individual date; return error if unable to parse.
     *
     * @return mixed
     */
    public function parseDate($string)
    {
        try {
            return Carbon::parse($string);
        } catch (Throwable $e) {
            $this->error('Unable to parse input date.');
            exit;
        }
    }

    
}
