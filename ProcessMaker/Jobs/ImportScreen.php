<?php
namespace ProcessMaker\Jobs;


use ProcessMaker\Models\Screen;
use ProcessMaker\Models\Script;

class ImportScreen extends ImportProcess
{
    /**
     * Create a new Screen model for each screen object in the imported file,
     * then save it to the database.
     *
     * @param Screen $screen
     *
     * @return void
     */
    private function saveScreens($screen)
    {
        try {
            $this->new['screens'] = [];
            $this->prepareStatus('screens', true);

            $new = new Screen();
            $new->fill((array)$screen);
            $new->title = $this->formatName($screen->title, 'title', Screen::class);
            $new->created_at = $this->formatDate($screen->created_at);
            if (property_exists($screen, 'watchers')) {
                \Illuminate\Support\Facades\Log::info("*** Tiene watchers");
                $new->watchers = $screen->watchers;
                $this->saveWatcherScripts($new);
            }

            $new->save();
            $this->new['screens'][] = $new;

            $this->finishStatus('screens');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error(print_r($e->getTraceAsString()));
            $this->finishStatus('screens', true);
        }
    }

    private function saveWatcherScripts($screen)
    {
        foreach($screen->watchers as $watcher) {
            \Illuminate\Support\Facades\Log::info("*** Salvando watcher");
            $script = $watcher->script;
            $new = new Script;
            $new->title = $this->formatName($script->title, 'title', Script::class);
            $new->description = $script->description;
            $new->language = $script->language;
            $new->code = $script->code;
            $new->created_at = $this->formatDate($script->created_at);
            $new->save();

            // save categories
            if (isset($script->categories)) {
                foreach ($script->categories as $categoryDef) {
                    $category = $this->saveCategory('script', $categoryDef);
                    $new->categories()->save($category);
                }
            }

            $watcher->script_id = $new->id;

            \Illuminate\Support\Facades\Log::info("script id:", $new->id);
        }
    }

    /**
     * Parse files with version 1
     *
     * @return array
     */
    private function parseFileV1()
    {
        $this->saveScreens($this->file->screens);
        return $this->status;
    }


    /**
     * Execute the job.
     *
     * @return boolean
     */
    public function handle()
    {
        //First, decode the file
        $this->decodeFile();

        //Then, process it based on version number
        if ($this->file->type === 'screen_package') {
            if ($method = $this->getParser()) {
                $this->status = [];
                $this->status['screens'] = [
                    'label' => __('Screens'),
                    'success' => false,
                    'message' => __('Starting')];
                return $this->{$method}();
            }
        }

        //Return false by default
        return false;
    }
}
