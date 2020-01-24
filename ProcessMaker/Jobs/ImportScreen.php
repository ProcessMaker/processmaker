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
                $new->watchers =  $this->watcherScriptsToSave($new);
            }

            $new->save();

            $this->finishStatus('screens');
        } catch (\Exception $e) {
            $this->finishStatus('screens', true);
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
