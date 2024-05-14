<?php

namespace ProcessMaker\Jobs;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\RequestException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Log;
use ProcessMaker\Models\Media;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\ScreenCategory;
use ProcessMaker\Models\ScreenTemplates;
use ProcessMaker\Models\User;
use Storage;

class SyncScreenTemplates implements ShouldQueue
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
     * Here the function fetches screen templates list from Github and saves them to the database.
     * @return void
     */
    public function handle()
    {
        try {
            // Fetch configuration from the environment
            $config = config('services.screen_templates_github');
            if (!$config) {
                return;
            }
            // Build the URL to fetch the guided templates list from GitHub
            $url = $config['base_url'] . $config['template_repo'] . '/' . $config['template_branch'] . '/index.json';

            // If there are multiple categories of templates defined in the .env, separate them into an array
            $categories = (strpos($config['template_categories'], ',') !== false)
                ? explode(',', $config['template_categories'])
                : [$config['template_categories']];

            // Create or get the ID of the 'Default Templates' category
            $screenTemplateCategoryId = ScreenCategory::firstOrCreate([
                'name' => 'Default Templates',
            ], [
                'status' => 'ACTIVE',
                'is_system' => 1,
            ])->getKey();

            // Fetch the screen template list from Github
            $response = Http::get($url);

            // Check if the request was successful
            if (!$response->successful()) {
                throw new RequestException('Unable to fetch screen template list.');
            }

            // Extract the JSON data from the response
            $data = $response->json();

            // Iterate over categories and templates to retrieve them
            foreach ($data as $templateCategory => $screenTemplates) {
                if (!in_array($templateCategory, $categories) && !in_array('all', $categories)) {
                    continue;
                }

                try {
                    // Import templates from the index.json file.
                    foreach ($screenTemplates as $template) {
                        $this->importTemplate($template, $config, $screenTemplateCategoryId);
                    }
                } catch (Exception $e) {
                    Log::error("Error Importing Screen Templates: {$e->getMessage()}");
                }
            }
        } catch (Exception $e) {
            Log::error("Error Syncing Screen Templates: {$e->getMessage()}");
        }
    }

    /**
     * Import a screen template into the database.
     *
     * @param array $template
     * @param array $config
     * @param int $screenTemplateCategoryId
     * @return void
     */
    private function importTemplate($template, $config, $screenTemplateCategoryId)
    {
        // Configure URLs for the screen
        $screenUrl = $this->buildTemplateUrl($config, $template['screen_template']);
        $templatePayload = $this->fetchPayload($screenUrl);
        $rootKey = $templatePayload['root'] ?? null;
        $screenType = null;
        $screenPayload = null;
        $screenCustomCss = null;

        if ($rootKey) {
            $screenType = Arr::get($templatePayload, "export.$rootKey.attributes.screen_type");
            $screenPayload = Arr::get($templatePayload, "export.$rootKey.attributes.manifest");
            $screenCustomCss = Arr::get($templatePayload, "export.$rootKey.attributes.screen_custom_css");
        }

        Arr::set($template, 'template_details.screen_type', $screenType);
        Arr::set($template, 'template_details.screen_custom_css', $screenCustomCss);

        // Update or create the screen template in the database
        try {
            $screenTemplate = $this->updateOrCreateScreenTemplate($template, $screenPayload, $screenTemplateCategoryId);
            // Create a media collection for template assets
            $mediaCollectionName = $this->createMediaCollection($screenTemplate);

            // Import template assets and associate with the media collection
            $this->importTemplateAssets($template, $config, $mediaCollectionName, $screenTemplate);
            $screenTemplate->media_collection = $mediaCollectionName;
            $screenTemplate->save();
        } catch (Exception $e) {
            Log::error("Error updating or creating Screen Templates: {$e->getMessage()}");
        }
    }

    // Helper functions used within importTemplate
    private function buildTemplateUrl($config, $templatePath)
    {
        // Build the URL for a template based on the configuration and template path
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

    private function updateOrCreateScreenTemplate($template, $screenPayload, $screenTemplateCategoryId)
    {
        // Update or create the screen template in the database
        return ScreenTemplates::updateOrCreate([
            'unique_template_id' => $template['template_details']['unique_template_id'],
        ], [
            'name' => $template['template_details']['name'],
            'description' => $template['template_details']['description'],
            'version' => $template['template_details']['version'],
            'screen_type' => $template['template_details']['screen_type'],
            'screen_category_id' => $screenTemplateCategoryId,
            'media_collection' => '',
            'screen_custom_css' => $template['template_details']['screen_custom_css'],
            'user_id' => null,
            'manifest' => $screenPayload,
            'is_public' => 1,
        ]);
    }

    private function createMediaCollection($screenTemplate)
    {
        // Create a media collection for template assets and return the collection name
        $mediaCollectionName = 'st-' . $screenTemplate->uuid . '-media';
        $screenTemplate->addMediaCollection($mediaCollectionName);

        return $mediaCollectionName;
    }

    private function importTemplateAssets($template, $config, $mediaCollectionName, $screenTemplate)
    {
        // Clear the collection to prevent duplicate images
        $screenTemplate->clearMediaCollection($mediaCollectionName);
        // Build asset urls
        $templateThumbnailUrl = $this->buildTemplateUrl($config, $template['assets']['thumbnail']);
        // Import template assets and associate with the media collection
        $this->importMedia($templateThumbnailUrl, 'thumbnail', $mediaCollectionName, $screenTemplate);

        foreach ($template['assets']['preview-thumbs'] as $thumbnail) {
            $templateThumbnailUrl = $this->buildTemplateUrl($config, $thumbnail);
            $mediaId =
                $this->importMedia($templateThumbnailUrl, 'preview-thumbs', $mediaCollectionName, $screenTemplate);
            $this->setPreviewThumbOrder($mediaId, $mediaCollectionName);
        }
    }

    private function importMedia($assetUrl, $customProperty, $mediaCollectionName, $screenTemplate)
    {
        // Import a media asset and associate it with the media collection
        $media = $screenTemplate
             ->addMediaFromUrl($assetUrl)
             ->withCustomProperties(['media_type' => $customProperty])
             ->toMediaCollection($mediaCollectionName);

        return $media->id;
    }

    private function setPreviewThumbOrder($id, $mediaCollectionName)
    {
        $media = Media::where('id', $id)->where('collection_name', $mediaCollectionName)->first();
        // Extract order index from file name
        $orderIndex = $this->extractOrderIndexFromFileName($media->name);

        // Set order column if available
        if (!is_null($orderIndex)) {
            $media->order_column = $orderIndex;
            $media->save();
        }
    }

    /**
     * Extracts order index from the file name.
     *
     * @param string $fileName
     * @return int|null
     */
    protected function extractOrderIndexFromFileName($fileName)
    {
        preg_match('/\d+/', basename($fileName), $matches);
        if (!empty($matches)) {
            return intval($matches[0]) - 1;
        }

        return null;
    }
}
