<?php
namespace ProcessMaker\Jobs;

use Exception;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\Script;

class ImportScreen extends ImportProcess
{
    /**
     * Parse files with version 1
     *
     * @return array
     */
    private function parseFileV1()
    {
        $this->prepareStatus('screens', 1);
        $this->saveScreen($this->file->screens);
        $this->finishStatus('screens');
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
