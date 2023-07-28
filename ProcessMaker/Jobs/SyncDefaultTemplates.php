<?php

namespace ProcessMaker\Jobs;

use Exception;
use Facades\ProcessMaker\JsonColumnIndex;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
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
     * Function to handle the execution of this job when it is run.
     * Here the function fetches default templates list from Github and saves them to the database.
     * @return void
     */
    public function handle()
    {
        $config = config('services.github');
        $url = $config['base_url'] . $config['template_repo'] . '/' . $config['template_branch'] . '/index.json';

        // If there are multiple categories of templates defined in the .env, then separate them into an array.
        $categories = (strpos($config['template_categories'], ',') !== false) ? explode(',', $config['template_categories']) : [$config['template_categories']];

        $processCategoryId = ProcessCategory::firstOrCreate(
            ['name' => 'Default Templates'],
            [
                'name' => 'Default Templates',
                'status' => 'ACTIVE',
                'is_system' => 0,
            ]
        )->getKey();

        try {
            $client = new Client();
            $response = $client->get($url);

            if ($response->getStatusCode() !== 200) {
                throw new Exception('Unable to fetch default template list.');
            }

            // Extract the json data from the response and iterate over the categories and templates to retrieve them.
            $data = json_decode($response->getBody(), true);
            foreach ($data as $templateCategory => $templates) {
                if (!in_array($templateCategory, $categories) && !in_array('all', $categories)) {
                    continue;
                }
                foreach ($templates as $template) {
                    $existingTemplate = ProcessTemplates::where('uuid', $template['uuid'])->first();
                    // If the template already exists in the database with a user then skip it,
                    // since we don't want to overwrite their changes.
                    if (!is_null($existingTemplate) && !is_null($existingTemplate->user_id)) {
                        continue;
                    }

                    $url = $config['base_url'] . $config['template_repo'] . '/' . $config['template_branch'] . '/' . $template['relative_path'];

                    try {
                        $response = $client->get($url);
                        if ($response->getStatusCode() !== 200) {
                            throw new Exception("Unable to fetch default template {$template['name']}.");
                        }

                        $payload = json_decode($response->getBody(), true);
                        data_set($payload, 'export.' . $payload['root'] . '.attributes.process_category_id', $processCategoryId);

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
                    } catch (RequestException $e) {
                        throw new Exception("Unable to fetch default template {$template['name']}. Error: " . $e->getMessage());
                    }
                }
            }
        } catch (RequestException $e) {
            throw new Exception('Unable to fetch default template list. Error: ' . $e->getMessage());
        }
    }
}
