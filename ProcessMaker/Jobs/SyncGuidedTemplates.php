<?php

namespace ProcessMaker\Jobs;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Log;
use ProcessMaker\ImportExport\Importer;
use ProcessMaker\ImportExport\Options;
use ProcessMaker\Models\Media;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessCategory;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\User;
use ProcessMaker\Models\WizardTemplate;
use Storage;

class SyncGuidedTemplates implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $newConfigCollectionId = null;

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
     * Here the function fetches guided templates list from Github and saves them to the database.
     * @return void
     */
    public function handle()
    {
        try {
            // Fetch configuration from the environment
            $config = config('services.guided_templates_github');

            // Build the URL to fetch the guided templates list from GitHub
            $url = $config['base_url'] . $config['template_repo'] . '/' . $config['template_branch'] . '/index.json';

            // If there are multiple categories of templates defined in the .env, separate them into an array
            $categories = (strpos($config['template_categories'], ',') !== false)
                ? explode(',', $config['template_categories'])
                : [$config['template_categories']];

            // Create or get the ID of the 'Guided Templates' category
            $guidedTemplateCategoryId = ProcessCategory::firstOrCreate([
                'name' => 'Guided Templates',
            ], [
                'status' => 'ACTIVE',
                'is_system' => 1,
            ])->getKey();

            // Fetch the guided template list from Github
            $response = Http::get($url);

            // Check if the request was successful
            if (!$response->successful()) {
                throw new Exception('Unable to fetch guided template list.');
            }

            // Extract the JSON data from the response
            $data = $response->json();

            // Iterate over categories and templates to retrieve them
            foreach ($data as $templateCategory => $guidedTemplates) {
                if (!in_array($templateCategory, $categories) && !in_array('all', $categories)) {
                    continue;
                }

                try {
                    // Import templates from the index.json file.
                    foreach ($guidedTemplates as $template) {
                        $this->importTemplate($template, $config, $guidedTemplateCategoryId);
                    }
                } catch (Exception $e) {
                    Log::error("Error Importing Guided Templates: {$e->getMessage()}");
                }
            }
        } catch (Exception $e) {
            Log::error("Error Syncing Guided Templates: {$e->getMessage()}");
        }
    }

    /**
     * Import a guided template into the database.
     *
     * @param array $template
     * @param array $config
     * @param int $guidedTemplateCategoryId
     * @return void
     */
    private function importTemplate($template, $config, $guidedTemplateCategoryId)
    {
        // Check for template changes and determine if helper process and template process need to be imported
        [$importHelperProcess, $importTemplateProcess] = $this->checkForTemplateChanges($template);

        // Check for template asset hash changes
        $assetsHashChanged = $this->checkForTemplateAssetChanges($template);

        // Fetch payloads if necessary
        $helperProcessPayload = $importHelperProcess ?
            $this->fetchPayload($this->buildTemplateUrl($config, $template['helper_process'])) : null;
        $templateProcessPayload = $importTemplateProcess ?
            $this->fetchPayload($this->buildTemplateUrl($config, $template['template_process'])) : null;

        // Update process categories for the helper process and process template
        $this->updateProcessCategories($helperProcessPayload, $templateProcessPayload, $guidedTemplateCategoryId);

        // Initialize variables for new process IDs
        $newHelperProcessId = null;
        $newProcessTemplateId = null;

        // Import helper process if necessary and get new ID
        if ($importHelperProcess) {
            $newHelperProcessId = $this->importProcess($helperProcessPayload, 'GUIDED_HELPER_PROCESS');
        }

        // Import template process if necessary and get new ID
        if ($importTemplateProcess) {
            $newProcessTemplateId = $this->importProcess($templateProcessPayload, 'GUIDED_PROCESS_TEMPLATE');
        }

        // Update or create the guided template in the database
        $guidedTemplate = $this->updateOrCreateGuidedTemplate($template, $newHelperProcessId, $newProcessTemplateId);

        // Create a media collection for template assets
        $mediaCollectionName = $this->createMediaCollection($guidedTemplate);

        if ($assetsHashChanged) {
            // Import template assets and associate with the media collection
            $this->importTemplateAssets($template, $config, $mediaCollectionName, $guidedTemplate);
        }

        // Save the media collection name to the guided template and persist changes
        $guidedTemplate->media_collection = $mediaCollectionName;

        $guidedTemplate->save();
    }

    // Helper functions used within importTemplate
    private function buildTemplateUrl($config, $templatePath)
    {
        // Build the URL for a template based on the configuration and template path
        if (empty($templatePath)) {
            return null;
        }

        return $config['base_url'] .
            $config['template_repo'] . '/' .
            $config['template_branch'] . '/' .
            Str::replace('./', '', $templatePath);
    }

    private function fetchPayload($url)
    {
        // Fetch the JSON payload from a given URL
        return Http::get($url)->json();
    }

    private function updateProcessCategories(&$helperProcessPayload, &$templateProcessPayload,
    $guidedTemplateCategoryId)
    {
        // Update process categories for both the helper process and process template
        if ($helperProcessPayload !== null) {
            data_set(
                $helperProcessPayload,
                "export.{$helperProcessPayload['root']}.attributes.process_category_id",
                $guidedTemplateCategoryId
            );
        }

        if ($templateProcessPayload !== null) {
            data_set(
                $templateProcessPayload,
                "export.{$templateProcessPayload['root']}.attributes.process_category_id",
                $guidedTemplateCategoryId
            );
        }
    }

    private function importProcess($payload, $assetType)
    {
        // Import a process and return the new ID
        $postOptions = [];
        foreach ($payload['export'] as $key => $asset) {
            $postOptions[$key] = [
                'mode' => 'update',
                'is_template' => true,
                'asset_type' => $assetType,
                'saveAssetsMode' => 'saveAllAssets',
            ];
            if (in_array($asset['type'], ['Process', 'Screen', 'Script',
                'Collections', 'DataConnector', 'ProcessTemplates'])) {
                $payload['export'][$key]['attributes']['asset_type'] = $assetType;
            }

            if (Arr::get($asset, 'type') === 'Screen' && Arr::get($asset, 'attributes.key') === 'interstitial') {
                Arr::set($payload, "export.{$key}.attributes.key", null);
            }
        }

        $options = new Options($postOptions);
        try {
            $importer = new Importer($payload, $options);
            $manifest = $importer->doImport();
            $rootLog = $manifest[$payload['root']]->log;

            return $rootLog['newId'];
        } catch (Exception $e) {
            throw new Exception('Error: ' . $e->getMessage());
        }
    }

    private function updateOrCreateGuidedTemplate($template, $newHelperProcessId, $newProcessTemplateId)
    {
        $templateDetails = $template['template_details'];
        $uniqueTemplateId = $templateDetails['unique-template-id'];
        $cardTitle = $templateDetails['card-title'];
        $cardExcerpt = $templateDetails['card-excerpt'];
        $templateDetailsJson = json_encode($templateDetails);

        // Check if the wizard template exists
        $guidedTemplate = WizardTemplate::where('unique_template_id', $uniqueTemplateId)->first();

        if ($guidedTemplate) {
            // Update existing wizard template
            $guidedTemplate->update([
                'name' => $cardTitle,
                'description' => $cardExcerpt,
                'media_collection' => '',
                'template_details' => $templateDetailsJson,
            ]);

            if ($newHelperProcessId !== null) {
                $guidedTemplate['helper_process_id'] = $newHelperProcessId;
                $guidedTemplate->save();
            }
            if ($newProcessTemplateId !== null) {
                $guidedTemplate['process_template_id'] = $newProcessTemplateId;
                $guidedTemplate->save();
            }
        } else {
            // Create new wizard template
            $guidedTemplate = WizardTemplate::create([
                'unique_template_id' => $uniqueTemplateId,
                'name' => $cardTitle,
                'description' => $cardExcerpt,
                'helper_process_id' => $newHelperProcessId,
                'process_template_id' => $newProcessTemplateId,
                'media_collection' => '',
                'template_details' => $templateDetailsJson,
            ]);
        }

        return $guidedTemplate;
    }

    private function createMediaCollection($guidedTemplate)
    {
        // Create a media collection for template assets and return the collection name
        $mediaCollectionName = 'wt-' . $guidedTemplate->uuid . '-media';
        $guidedTemplate->addMediaCollection($mediaCollectionName);

        return $mediaCollectionName;
    }

    private function importTemplateAssets($template, $config, $mediaCollectionName, $guidedTemplate)
    {
        // Clear the collection to prevent duplicate images
        $guidedTemplate->clearMediaCollection($mediaCollectionName);

        // Build asset urls
        $templateIconUrl = $this->buildTemplateUrl($config, $template['assets']['icon']);
        $templateCardBackgroundUrl = $this->buildTemplateUrl($config, $template['assets']['card-background']);
        $templateListIconUrl = $this->buildTemplateUrl($config, $template['assets']['list-icon']);
        // Import template assets and associate with the media collection
        $this->importMedia($templateIconUrl, 'icon', $mediaCollectionName, $guidedTemplate);
        $this->importMedia($templateCardBackgroundUrl, 'cardBackground', $mediaCollectionName, $guidedTemplate);
        $this->importMedia($templateListIconUrl, 'listIcon', $mediaCollectionName, $guidedTemplate);

        if (!empty($template['assets']['launchpad']['process-card-background'])) {
            $templateProcessCardBackgroundUrl =
                $this->buildTemplateUrl($config, $template['assets']['launchpad']['process-card-background']);
            $this->importMedia($templateProcessCardBackgroundUrl, 'launchpadProcessCardBackground',
                $mediaCollectionName, $guidedTemplate);
        }

        foreach ($template['assets']['slides'] as $slide) {
            $templateSlideUrl = $this->buildTemplateUrl($config, $slide);
            $this->importMedia($templateSlideUrl, 'slide', $mediaCollectionName, $guidedTemplate);
        }

        if (!empty($template['assets']['launchpad']['slides'])) {
            foreach ($template['assets']['launchpad']['slides'] as $slide) {
                $templateSlideUrl = $this->buildTemplateUrl($config, $slide);
                $this->importMedia($templateSlideUrl, 'launchpadSlides', $mediaCollectionName, $guidedTemplate);
            }
        }
    }

    private function importMedia($assetUrl, $customProperty, $mediaCollectionName, $guidedTemplate)
    {
        // Import a media asset and associate it with the media collection
        if (!is_null($assetUrl)) {
            $guidedTemplate
                ->addMediaFromUrl($assetUrl)
                ->withCustomProperties(['media_type' => $customProperty])
                ->toMediaCollection($mediaCollectionName);
        }
    }

    private function checkForTemplateChanges($template)
    {
        // Initialize variables to track changes
        $helperProcessHashChanged = true;
        $templateProcessHashChanged = true;

        // Retrieve wizard template details if it exists
        $wizardTemplate =
            WizardTemplate::where('unique_template_id', $template['template_details']['unique-template-id'])
            ->select('template_details')
            ->first();

        if ($wizardTemplate) {
            $wizardTemplateDetails = json_decode($wizardTemplate->template_details, true);

            // Check if helper process hash has changed
            if (isset($wizardTemplateDetails['helper_process_hash']) &&
                $template['template_details']['helper_process_hash'] ===
                $wizardTemplateDetails['helper_process_hash']) {
                $helperProcessHashChanged = false;
            }

            // Check if template process hash has changed
            if (isset($wizardTemplateDetails['template_process_hash']) &&
                $template['template_details']['template_process_hash'] ===
                $wizardTemplateDetails['template_process_hash']) {
                $templateProcessHashChanged = false;
            }
        }

        return [$helperProcessHashChanged, $templateProcessHashChanged];
    }

    private function checkForTemplateAssetChanges($template)
    {
        // Initialize variables to track changes
        $assetHashChanged = true;
        // Retrieve wizard template details if it exists
        $wizardTemplate =
            WizardTemplate::where('unique_template_id', $template['template_details']['unique-template-id'])
            ->select('template_details')
            ->first();

        if ($wizardTemplate) {
            $wizardTemplateDetails = json_decode($wizardTemplate->template_details, true);
            // Check if helper process hash has changed
            if (isset($wizardTemplateDetails['asset_hash']) &&
                $template['template_details']['asset_hash'] ===
                $wizardTemplateDetails['asset_hash'] ||
                !isset($wizardTemplateDetails['asset_hash'])) {
                $assetHashChanged = false;
            }
        }

        return $assetHashChanged;
    }
}
