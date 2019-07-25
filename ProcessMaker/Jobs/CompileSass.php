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

        if (str_contains($this->properties['tag'], 'app')) {
            $this->fixPathsInGeneratedAppCss();
        }
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

    private function fixPathsInGeneratedAppCss ()
    {
        chdir(app()->basePath());
        $file = file_get_contents("public/css/app.css");
        $file = str_replace('url("./fonts/','url("/fonts/vendor/npm-font-open-sans/', $file );
        $file = str_replace('public/css/precompiled/vue-multiselect.min.css','css/precompiled/vue-multiselect.min.css', $file );
        $file = str_replace('url("../webfonts/','url("/fonts/', $file );
        $file = str_replace('url("../fonts/','url("/fonts/', $file );
        $file = str_replace('url("fonts/','url("/fonts/', $file );
        $re = '/(content:\s)\\\\\"(\\\\[0-9abcdef]+)\\\\\"/m';
        $file = preg_replace($re,'$1"$2"', $file );
        file_put_contents('public/css/app.css', $file);
    }
}
