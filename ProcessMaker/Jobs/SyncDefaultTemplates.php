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
        $categories = (strpos($config['template_categories'], ',') !== false) ? explode(',', $config['template_categories']) : [$config['template_categories']];
        $processCategoryId = ProcessCategory::firstOrCreate(
            ['name' => 'Default Templates'],
            [
                'name' => 'Default Templates',
                'status' => 'ACTIVE',
                'is_system' => 0,
            ]
        )->getKey();

        $response = Http::get($url);

        if (!$response->successful()) {
            throw new Exception('Unable to fetch default template list.');
        }

        $data = $response->json();
        foreach ($data as $templateCategory => $templates) {
            if (!in_array($templateCategory, $categories)) {
                continue;
            }
            foreach ($templates as $template) {
                $existingTemplate = ProcessTemplates::where('uuid', $template['uuid'])->first();
                if (!is_null($existingTemplate) && !is_null($existingTemplate->user_id)) {
                    continue;
                }

                $url = $config['base_url'] . $config['template_repo'] . '/' . $config['template_branch'] . '/' . $template['relative_path'];
                $response = Http::get($url);
                if (!$response->successful()) {
                    throw new Exception("Unable to fetch default template {$template['name']}.");
                }
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
                    'process_category_id' => $processCategoryId,
                ]);
                $template->save();
            }
        }
    }
}
