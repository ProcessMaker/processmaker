<?php

namespace ProcessMaker\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use ProcessMaker\Models\User;
use ProcessMaker\Notifications\SassCompiledNotification;

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
        $this->runCmd('node_modules/sass/sass.js --no-source-map '
            . $this->properties['origin'] . ' ' . $this->properties['target']);

        if (Str::contains($this->properties['tag'], 'app')) {
            $this->fixPathsInGeneratedAppCss();
            $this->updateCacheBuster();
        }

        $user = User::find($this->properties['user']);
        if (Str::contains($this->properties['tag'], 'queues') && $user) {
            Notification::send(collect([$user]), new SassCompiledNotification());
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
        exec($cmd . ' 2>&1', $output, $returnVal);
        $output = implode("\n", $output);
        if ($returnVal) {
            Log::info("Cmd returned: $returnVal " . $output);
            throw new \Exception("Cmd returned: $returnVal " . $output);
        }
        Log::info('Returned' . $output);

        return $output;
    }

    private function fixPathsInGeneratedAppCss()
    {
        chdir(app()->basePath());
        $file = file_get_contents('public/css/app.css');
        $file = preg_replace('/\.\/fonts(\/[A-Za-z]+\/)OpenSans\-/m', '/fonts/OpenSans-', $file);
        $file = str_replace('public/css/precompiled/vue-multiselect.min.css', 'css/precompiled/vue-multiselect.min.css', $file);
        $file = str_replace('public/css/precompiled/poppins/300.css', 'css/precompiled/poppins/300.css', $file);
        $file = str_replace('public/css/precompiled/poppins/500.css', 'css/precompiled/poppins/500.css', $file);
        $file = str_replace('url("../webfonts/', 'url("/fonts/', $file);
        $file = str_replace('url("../fonts/', 'url("/fonts/', $file);
        $file = str_replace('url("processmaker-font', 'url("/fonts/processmaker-font', $file);        
        $file = str_replace('url("fonts/', 'url("/fonts/', $file);
        $file = str_replace('content: /; }', 'content: "/"; }', $file);
        $re = '/(content:\s)\\\\\"(\\\\[0-9abcdef]+)\\\\\"/m';
        $file = preg_replace($re, '$1"$2"', $file);
        file_put_contents('public/css/app.css', $file);
    }

    private function updateCacheBuster()
    {
        chdir(app()->basePath());

        $file = file_get_contents('public/mix-manifest.json');
        $guid = bin2hex(random_bytes(16));
        $re = '/\"\:\s"\/css\/sidebar\.css.+id=(.*)\"/m';
        $file = preg_replace($re, '": "/css/sidebar.css?id=' . $guid . '"', $file);
        $guid = bin2hex(random_bytes(16));
        $re = '/\"\:\s"\/css\/app\.css.+id=(.*)\"/m';
        $file = preg_replace($re, '": "/css/app.css?id=' . $guid . '"', $file);
        $guid = bin2hex(random_bytes(16));
        $re = '/\"\:\s"\/css\/admin\/queues\.css.+id=(.*)\"/m';
        $file = preg_replace($re, '": "/css/admin/queues.css?id=' . $guid . '"', $file);

        file_put_contents('public/mix-manifest.json', $file);
    }
}
