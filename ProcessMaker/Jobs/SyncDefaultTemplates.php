<?php

namespace ProcessMaker\Jobs;

use Exception;
use Facades\ProcessMaker\JsonColumnIndex;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use ProcessMaker\ImportExport\Importer;
use ProcessMaker\ImportExport\Options;
use ProcessMaker\Models\ProcessCategory;
use ProcessMaker\Models\ProcessTemplates;

class SyncDefaultTemplates implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const GITHUB_URL = 'https://raw.githubusercontent.com/processmaker/';

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $config = config('services.github');
        $url = $config['base_url'] . $config['template_repo'] . '/' . $config['template_branch'] . '/index.json';

        $response = Http::get($url);

        if ($response->successful()) {
            $data = $response->json();
            foreach ($data as $category => $templates) {
                foreach ($templates as $template) {
                    $query = ProcessTemplates::where('uuid', $template['uuid'])->first();
                    if (is_null($query) || is_null($query->user_id)) {
                        $url = $config['base_url'] . $config['template_repo'] . '/' . $config['template_branch'] . '/' . $template['relative_path'];
                        $response = Http::get($url);
                        if ($response->successful()) {
                            $payload = $response->json();
                            $options = new Options([
                                'mode' => 'update',
                                'isTemplate' => true,
                                'saveAssetsMode' => 'saveAllAssets',
                            ]);
                            $importer = new Importer($payload, $options);
                            $manifest = $importer->doImport();

                            $template = ProcessTemplates::where('uuid', $template['uuid'])->first();
                            $template->setRawAttributes([
                                'key' => 'default_templates',
                                'process_id' => null,
                                'user_id' => null,
                                'process_category_id' => ProcessCategory::where('name', 'Default Templates')->firstOrFail()->getKey(),
                            ]);
                            $template->save();
                        } else {
                            throw new Exception("Unable to fetch default template {$template['name']}.");
                        }
                    }
                }
            }
        } else {
            throw new Exception('Unable to fetch default template list.');
        }
    }
}
