<?php

namespace ProcessMaker\Jobs;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use ProcessMaker\ImportExport\Importer;
use ProcessMaker\ImportExport\Options;
use ProcessMaker\Models\ProcessCategory;
use ProcessMaker\Models\ProcessTemplates;

class SyncDefaultTemplates implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     * Function to handle the execution of this job when it is run.
     * Here the function fetches default templates list from Github and saves them to the database.
     */
    public function handle()
    {
        $config = config('services.github');
        $url = $config['base_url'] . $config['template_repo'] . '/' . $config['template_branch'] . '/index.json';

        // If there are multiple categories of templates defined in the .env then separate them into an array.
        $categories = (strpos($config['template_categories'], ',') !== false) ? explode(',', $config['template_categories']) : [$config['template_categories']];

        $processCategoryId = ProcessCategory::firstOrCreate(
            ['name' => 'Default Templates'],
            [
                'name' => 'Default Templates',
                'status' => 'ACTIVE',
                'is_system' => 0,
            ]
        )->getKey();

        // Get the default template list from Github.
        $response = Http::timeout(10)->get($url);
        if (!$response->successful()) {
            Log::warning("[SyncDefaultTemplates] Failed to fetch template index from GitHub: {$url} - Status: {$response->status()}");

            return; // skip the job gracefully
        }

        // Extract the json data from the response and iterate over the categories and templates to retrieve them.
        $data = $response->json();
        foreach ($data as $templateCategory => $templates) {
            if (!in_array($templateCategory, $categories) && !in_array('all', $categories)) {
                continue;
            }
            foreach ($templates as $template) {
                $existingTemplate = ProcessTemplates::where('uuid', $template['uuid'])->first();
                // If the template already exists in the database with a user then skip it, since we don't want to overwrite their changes.
                if (!is_null($existingTemplate) && !is_null($existingTemplate->user_id)) {
                    continue;
                }

                $relativePath = ltrim($template['relative_path'], './');
                $url = $config['base_url'] . $config['template_repo'] . '/' . $config['template_branch'] . '/' . $relativePath;
                $response = Http::timeout(10)->get($url);
                if (!$response->successful()) {
                    Log::warning("[SyncDefaultTemplates] Skipped template due to failed fetch: {$template['name']} ({$template['uuid']}) - Status: {$response->status()}");
                    continue;
                }
                $payload = $response->json();
                data_set($payload, 'export.' . $payload['root'] . '.attributes.process_category_id', $processCategoryId);
                $options = new Options([
                    'mode' => 'update',
                    'isTemplate' => true,
                    'saveAssetsMode' => 'saveAllAssets',
                ]);
                $importer = new Importer($payload, $options);
                $importer->doImport();
            }
        }
    }
}
