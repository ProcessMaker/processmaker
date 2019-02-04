<?php

namespace ProcessMaker\Jobs;

use Cache;
use Illuminate\Bus\Queueable;
use ProcessMaker\Models\Process;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ExportProcess implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $process, $filePath, $file;
    
    private $package = [];

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Process $process, $filePath = null)
    {
        $this->process = $process;
        $this->filePath = $filePath;
    }
    
    private function packageFile()
    {
        $this->package['type'] = 'process_package';
        $this->package['version'] = '0.1';
        $this->package['process'] = $this->process->toArray();
        $this->package['process']['bpmn'] = $this->process->bpmn;
    }
    
    private function encodeFile()
    {
        $this->file = json_encode($this->package);
        $this->file = base64_encode($this->file);
    }

    /**
     * Save the file .
     *
     * @return void
     */
    private function saveFile()
    {
        $bytes = file_put_contents($this->filePath, $this->file);
        if ($bytes !== false) {
            return true;
        }
    }
    
    private function cacheFile()
    {
        $key = sha1($this->file);
        $value = $this->file;
        $expiresAt = now()->addHours(1);
        Cache::put($key, $value, $expiresAt);
        
        return $key;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->packageFile();
        $this->encodeFile();
        
        if ($this->filePath) {
            return $this->saveFile();
        } else {
            return $this->cacheFile();
        }
    }
}
