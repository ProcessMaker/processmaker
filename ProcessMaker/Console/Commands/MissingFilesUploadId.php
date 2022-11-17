<?php

namespace ProcessMaker\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use ProcessMaker\Models\ProcessRequest;

class MissingFilesUploadId extends Command
{
    private $MAX_SCRIPT_TIMEOUT = 3600;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'processmaker:fix-missing-file-upload-id';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix broken file IDs in requests';

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
        $this->writeln("Searching for request with calls to sub-process\n", 'info', true);
        $aux = '';
        $dataAux = [];

        foreach (ProcessRequest::where('process_collaboration_id', '!=', null)
            ->whereNotIn('STATUS', ['ERROR', 'COMPLETED', 'CANCELED'])
            ->orderBy('id', 'asc')
            ->get() as $request) {
            $data = $request->data;
            $modify = false;
            foreach ($request->getMedia() as $file) {
                if (Arr::has($data, $file->getCustomProperty('data_name')) && !is_array(Arr::get($data, $file->getCustomProperty('data_name')))) {
                    $modify = true;
                    Arr::set($data, $file->getCustomProperty('data_name'), $file->id);
                } else if (Arr::has($data, $file->getCustomProperty('data_name')) && is_array(Arr::get($data, $file->getCustomProperty('data_name')))) {
                    if ($aux != '' && $aux != $file->getCustomProperty('data_name')) {
                        Arr::set($data, $aux, $dataAux);
                        $dataAux = [];
                        $modify = true;
                    }
                    $aux = $file->getCustomProperty('data_name');
                    $dataAux[] = (object)['file' => $file->id];
                }
            }
            if (count($dataAux)> 0) {
                Arr::set($data, $aux, $dataAux);
                $modify = true;
            }
            if ($modify) {
                $this->writeln("Process Request: {$request->id} modified", 'info', true);
                $request->data = $data;
                $request->save();
            }
        }
    }

    private function writeln($message, $type, $toLog = false)
    {
        $this->{$type}($message);
        if ($toLog) {
            Log::Info('File ID Broken: ' . $message);
        }
    }
}
