<?php

namespace ProcessMaker\Jobs;

use DB;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class GenerateUuidsForTable implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $table;

    private $field;

    private $primary;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(String $table, String $field = 'uuid', String $primary = 'id')
    {
        $this->table = $table;
        $this->field = $field;
        $this->primary = $primary;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $query = DB::table($this->table)
            ->select($this->primary, $this->field)
            ->orderBy($this->primary)->chunk(1000, function ($records) {
                // Assign UUIDs if needed
                foreach ($records as &$record) {
                    if (!$record->{$this->field} || trim($record->{$this->field}) === '') {
                        $record->{$this->field} = (string) Str::orderedUuid();
                    }
                }

                // Set UUIDs in transaction for performance reasons
                DB::transaction(function () use ($records) {
                    foreach ($records as &$record) {
                        DB::table($this->table)
                            ->where($this->primary, $record->{$this->primary})
                            ->update(['uuid' => $record->{$this->field}]);
                    }
                });
            });
    }
}
