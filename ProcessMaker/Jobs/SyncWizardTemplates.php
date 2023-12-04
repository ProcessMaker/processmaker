<?php

namespace ProcessMaker\Jobs;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use ProcessMaker\ImportExport\Importer;
use ProcessMaker\ImportExport\Options;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessCategory;
use ProcessMaker\Models\WizardTemplate;

class SyncWizardTemplates implements ShouldQueue
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
        $config = config('services.wizard_templates_github');
        $url = $config['base_url'] .
                $config['wizard_repo'] . '/' .
                $config['wizard_branch'] . '/index.json';

        // If there are multiple categories of templates defined in the .env then separate them into an array.
        $categories = (strpos($config['wizard_categories'], ',') !== false)
                        ? explode(',', $config['wizard_categories'])
                        : [$config['wizard_categories']];

        $wizardTemplateCategoryId = ProcessCategory::firstOrCreate(
            ['name' => 'Wizard Templates'],
            [
                'name' => 'Wizard Templates',
                'status' => 'ACTIVE',
                'is_system' => 0,
            ]
        )->getKey();

        // Get the default template list from Github.
        $response = Http::get($url);

        if (!$response->successful()) {
            throw new Exception('Unable to fetch wizard template list.');
        }

        // Extract the json data from the response and iterate over the categories and templates to retrieve them.
        $data = $response->json();
        foreach ($data as $templateCategory => $wizardTemplates) {
            if (!in_array($templateCategory, $categories) && !in_array('all', $categories)) {
                continue;
            }
            foreach ($wizardTemplates as $template) {
                $existingTemplate = WizardTemplate::where('uuid', $template['uuid'])->first();
                // If the template already exists in the database with a user then skip it,
                // since we don't want to overwrite their changes.
                if (!is_null($existingTemplate) && !is_null($existingTemplate->user_id)) {
                    continue;
                }

                $url = $config['base_url'] .
                        $config['wizard_repo'] . '/' .
                        $config['wizard_branch'] . '/' .
                        $template['relative_path'];

                $response = Http::get($url);
                if (!$response->successful()) {
                    throw new Exception("Unable to fetch wizard template {$template['name']}.");
                }
                $payload = $response->json();
                $dataKey = "export.{$payload['root']}.attributes.process_category_id";
                data_set($payload, $dataKey, $wizardTemplateCategoryId);

                $options = new Options([
                    'mode' => 'update',
                    'asset_type' => 'WIZARD_TEMPLATE',
                    'saveAssetsMode' => 'saveAllAssets',
                ]);
                $importer = new Importer($payload, $options);
                $importer->doImport();

                $template = Process::where('uuid', $template['uuid'])->first();

                WizardTemplate::create([
                    'helper_process_id' => $template->id,
                ]);
            }
        }
    }
}
