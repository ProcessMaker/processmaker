<?php

namespace ProcessMaker\Jobs;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
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

class SyncWizardTemplates implements ShouldQueue
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
     * Here the function fetches wizard templates list from Github and saves them to the database.
     * @return void
     */
    public function handle()
    {
        try {
            // Fetch configuration from the environment
            $config = config('services.wizard_templates_github');

            // Build the URL to fetch the default templates list from GitHub
            $url = $config['base_url'] . $config['wizard_repo'] . '/' . $config['wizard_branch'] . '/index.json';

            // If there are multiple categories of templates defined in the .env, separate them into an array
            $categories = (strpos($config['wizard_categories'], ',') !== false)
                ? explode(',', $config['wizard_categories'])
                : [$config['wizard_categories']];

            // Create or get the ID of the 'Wizard Templates' category
            $wizardTemplateCategoryId = ProcessCategory::firstOrCreate([
                'name' => 'Wizard Templates',
            ], [
                'status' => 'ACTIVE',
                'is_system' => 0,
            ])->getKey();

            // Fetch the default template list from Github
            $response = Http::get($url);

            // Check if the request was successful
            if (!$response->successful()) {
                throw new Exception('Unable to fetch wizard template list.');
            }

            // Extract the JSON data from the response
            $data = $response->json();

            // Iterate over categories and templates to retrieve them
            foreach ($data as $templateCategory => $wizardTemplates) {
                if (!in_array($templateCategory, $categories) && !in_array('all', $categories)) {
                    continue;
                }

                foreach ($wizardTemplates as $template) {
                    $this->importTemplate($template, $config, $wizardTemplateCategoryId);
                }
            }
        } catch (Exception $e) {
            Log::error("Error Syncing Wizard Templates: {$e->getMessage()}");
        }
    }

    /**
     * Import a wizard template into the database.
     *
     * @param array $template
     * @param array $config
     * @param int $wizardTemplateCategoryId
     * @return void
     */
    private function importTemplate($template, $config, $wizardTemplateCategoryId)
    {
        // Configure URLs for the helper process, process template, and collection
        $helperProcessUrl = $this->buildTemplateUrl($config, $template['helper_process']);
        $processTemplateUrl = $this->buildTemplateUrl($config, $template['template_process']);
        $configCollectionUrl = $this->buildTemplateUrl($config, $template['config_collection']);

        // Get manifests of the exported helper process, process template, and collection
        $helperProcessPayload = $this->fetchPayload($helperProcessUrl);
        $templateProcessPayload = $this->fetchPayload($processTemplateUrl);
        $configCollectionPayload = $this->fetchPayload($configCollectionUrl);

        // Update process categories for the helper process and process template
        $this->updateProcessCategories($helperProcessPayload, $templateProcessPayload, $wizardTemplateCategoryId);

        // Import the helper process and get the new ID
        $newHelperProcessId = $this->importProcess($helperProcessPayload, 'WIZARD_HELPER_PROCESS');

        // Import the process template and get the new ID
        $newProcessTemplateId = $this->importProcess($templateProcessPayload, 'WIZARD_PROCESS_TEMPLATE');

        // Update or create the wizard template in the database
        $wizardTemplate = $this->updateOrCreateWizardTemplate($template, $newHelperProcessId, $newProcessTemplateId);

        // Create a media collection for template assets
        $mediaCollectionName = $this->createMediaCollection($wizardTemplate);

        // Import template assets and associate with the media collection
        $this->importTemplateAssets($template, $config, $mediaCollectionName, $wizardTemplate);

        // Import the config collection and update associations
        $this->importConfigCollection($configCollectionUrl, $configCollectionPayload, $wizardTemplate);
        // Update the wizard template with the config collection ID

        // TODO: An issue occurs at this point as there is currently no direct way to update an existing collection when importing a collection.
        // When importing a collection from the wizard template, a new collection is created each time.
        // As a temporary solution, we could check if the wizard template already has a config_collection_id associated.
        // If so, we need to delete the existing collection along with all its assets (screens, process signals, etc.).
        // Subsequently, we import a new collection and associate it with the wizard template.
        // This approach prevents the database from being cluttered with orphaned collections and collection assets.
        // Considerations:
        // - Could this cause issues with already configured wizard templates?
        // - Could this cause issues with the helper process/process template if they reference a specific collection?
        // - Does deleting a collection automatically remove all associated assets?
        // - How can we implement an import method similar to processes for collections, and what is the expected duration of implementation?
        $wizardTemplate->config_collection_id = $this->newConfigCollectionId;
        $wizardTemplate->media_collection = $mediaCollectionName;
        $wizardTemplate->save();
    }

    // Helper functions used within importTemplate
    private function buildTemplateUrl($config, $templatePath)
    {
        // Build the URL for a template based on the configuration and template path
        return $config['base_url'] .
            $config['wizard_repo'] . '/' .
            $config['wizard_branch'] . '/' .
            Str::replace('./', '', $templatePath);
    }

    private function fetchPayload($url)
    {
        // Fetch the JSON payload from a given URL
        return Http::get($url)->json();
    }

    private function updateProcessCategories(&$helperProcessPayload, &$templateProcessPayload, $wizardTemplateCategoryId)
    {
        // Update process categories for both the helper process and process template
        data_set(
            $helperProcessPayload,
            "export.{$helperProcessPayload['root']}.attributes.process_category_id",
            $wizardTemplateCategoryId
        );
        data_set(
            $templateProcessPayload,
            "export.{$templateProcessPayload['root']}.attributes.process_category_id",
            $wizardTemplateCategoryId
        );
    }

    private function importProcess($payload, $assetType)
    {
        // Import a process and return the new ID
        // TODO: Ensure the asset_type is being properly updated in the database
        $importOptions = new Options([
            'mode' => 'update',
            'asset_type' => $assetType,
            'saveAssetsMode' => 'saveAllAssets',
        ]);

        $importer = new Importer($payload, $importOptions);
        $manifest = $importer->doImport();
        $rootLog = $manifest[$payload['root']]->log;

        return $rootLog['newId'];
    }

    private function updateOrCreateWizardTemplate($template, $newHelperProcessId, $newProcessTemplateId)
    {
        // Update or create the wizard template in the database
        // TODO: Currently, the index.json in the GitHub repository contains identical wizard template test data for each category,
        // resulting in a single record creation that gets updated for each template within the category.
        // This behavior will be corrected once we ensure that each category has only one instance of the test data.

        return WizardTemplate::updateOrCreate([
            'name' => $template['template_details']['card-title'],
        ], [
            'description' => $template['template_details']['card-excerpt'],
            'helper_process_id' => $newHelperProcessId,
            'process_template_id' => $newProcessTemplateId,
            'media_collection' => '',
            'config_collection_id' => null,
            'template_details' => json_encode($template['template_details']),
        ]);
    }

    private function createMediaCollection($wizardTemplate)
    {
        // Create a media collection for template assets and return the collection name
        $mediaCollectionName = 'wt-' . $wizardTemplate->uuid . '-media';
        $wizardTemplate->addMediaCollection($mediaCollectionName);

        return $mediaCollectionName;
    }

    private function importTemplateAssets($template, $config, $mediaCollectionName, $wizardTemplate)
    {
        // Clear the collection to prevent duplicate images
        $wizardTemplate->clearMediaCollection($mediaCollectionName);
        // Build asset urls
        $templateIconUrl = $this->buildTemplateUrl($config, $template['assets']['icon']);
        $templateCardBackgroundUrl = $this->buildTemplateUrl($config, $template['assets']['card-background']);
        // Import template assets and associate with the media collection
        $this->importMedia($templateIconUrl, 'icon', $mediaCollectionName, $wizardTemplate);
        $this->importMedia($templateCardBackgroundUrl, 'cardBackground', $mediaCollectionName, $wizardTemplate);

        foreach ($template['assets']['slides'] as $slide) {
            $templateSlideUrl = $this->buildTemplateUrl($config, $slide);
            $this->importMedia($templateSlideUrl, 'slide', $mediaCollectionName, $wizardTemplate);
        }
    }

    private function importMedia($assetUrl, $customProperty, $mediaCollectionName, $wizardTemplate)
    {
        // Import a media asset and associate it with the media collection
        $wizardTemplate->addMediaFromUrl($assetUrl)->withCustomProperties(['media_type' => $customProperty])->toMediaCollection($mediaCollectionName);
    }

    private function importConfigCollection($configCollectionUrl, $configCollectionPayload, $wizardTemplate)
    {
        // Import the config collection and update associations
        if (class_exists('ProcessMaker\Plugins\Collections\Jobs\ImportCollection')) {
            // Obtain the user ID for the admin user
            $adminUserId = User::where('username', 'admin')->first()->value('id');
            // Create a temporary file to store the JSON payload
            $tempFileName = $this->createTempFile($configCollectionUrl, $configCollectionPayload);

            // Store the temporary file in the storage directory
            $pathInStorage = $this->storeInStorage($tempFileName);

            // Import the config collection using the Collection package
            $importedData = $this->importCollection($pathInStorage, $adminUserId);

            // Process the imported data and update asset types and associations
            $this->processImportedData($importedData);
        } else {
            Log::debug('Error Syncing Wizard Templates: The Collection package is not installed. Please install the Collection package to enable full functionality for Wizard Templates.');
        }
    }

    /**
     * Create a temporary file and store the JSON payload.
     *
     * @param string $configCollectionUrl
     * @param array $configCollectionPayload
     * @return string
     */
    private function createTempFile($configCollectionUrl, $configCollectionPayload)
    {
        $info = pathinfo($configCollectionUrl);
        $tempFileName = '/tmp/' . $info['basename'];
        file_put_contents($tempFileName, json_encode($configCollectionPayload));

        return $tempFileName;
    }

    /**
     * Store the temporary file in the storage directory.
     *
     * @param string $tempFileName
     * @return string
     */
    private function storeInStorage($tempFileName)
    {
        return Storage::putFile('imports', $tempFileName);
    }

    /**
     * Import the config collection using the Collection package.
     *
     * @param string $pathInStorage
     * @param int $adminUserId
     * @return array
     */
    private function importCollection($pathInStorage, $adminUserId)
    {
        $collectionImporterClass = 'ProcessMaker\Plugins\Collections\Jobs\ImportCollection';

        return (new $collectionImporterClass($pathInStorage, $adminUserId))->handle();
    }

    /**
     * Process the imported data and update asset types and associations.
     *
     * @param array $importedData
     * @return void
     */
    private function processImportedData($importedData)
    {
        foreach ($importedData as $element => $value) {
            if (isset($value['uuids'])) {
                foreach ($value['uuids'] as $uuid) {
                    if ($element === 'collections') {
                        $this->updateCollectionAssetType($uuid);
                    } elseif ($element === 'screeens') {
                        $this->updateScreenAssetType($uuid);
                    }
                }
            }
        }
    }

    /**
     * Update the asset type on the imported collection.
     *
     * @param string $uuid
     * @return void
     */
    private function updateCollectionAssetType($uuid)
    {
        $collectionClass = 'ProcessMaker\Plugins\Collections\Models\Collection';
        $importedCollection = (new $collectionClass)->where('uuid', $uuid)->first();
        $importedCollection->asset_type = 'WIZARD_CONFIG_COLLECTION';
        $importedCollection->save();
        $this->newConfigCollectionId = $importedCollection->id;
    }

    /**
     * Update the asset type for imported collection screens.
     *
     * @param string $uuid
     * @return void
     */
    private function updateScreenAssetType($uuid)
    {
        $importedCollectionScreen = Screen::where('uuid', $uuid)->first();
        $importedCollectionScreen->asset_type = 'WIZARD_CONFIG_COLLECTION_SCREEN';
        $importedCollectionScreen->save();
    }
}
