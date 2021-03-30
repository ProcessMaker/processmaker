<?php

namespace ProcessMaker\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use ProcessMaker\Events\TestStatusEvent;

class TestStatusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $name;
    public $description;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($name, $description = '')
    {
        $this->name = $name;
        $this->description = $description;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        event(new TestStatusEvent($this->name, $this->description));
        DB::table('test_status')->insert([
            'name' => $this->name,
            'description' => $this->description,
        ]);
    }
}
