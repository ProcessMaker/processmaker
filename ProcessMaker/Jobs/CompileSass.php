<?php

namespace ProcessMaker\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

class CompileSass implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $properties;

    /**
     * Create a new job instance.
     *
     * @param $properties
     */
    public function __construct($properties)
    {
        $this->properties = $properties;
    }

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array
     */
    public function tags()
    {
        return ['compile-css', $this->properties['tag']];
    }

    /**
     * Execute compile docker sass
     *
     * @throws \Exception
     */
    public function handle()
    {
        chdir(app()->basePath());
        $this->runCmd("docker run --rm -v $(pwd):$(pwd) -w $(pwd) jbergknoff/sass "
            . $this->properties['origin'] . ' ' . $this->properties['target']);
            //. "resources/sass/sidebar/sidebar.scss public/css/sidebar.css");
    }

    /**
     * @param $cmd
     * @return string
     * @throws \Exception
     */
    private function runCmd($cmd)
    {
        Log::info('Start css rebuild: ' . $cmd);
        exec($cmd . " 2>&1", $output, $returnVal);
        $output = implode("\n", $output);
        if ($returnVal) {
            Log::info("Cmd returned: $returnVal " . $output);
            throw new \Exception("Cmd returned: $returnVal " . $output);
        }
        Log::info('Returned' . $output);
        return $output;
    }
}
