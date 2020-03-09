<?php

namespace ProcessMaker\Assets;

use ProcessMaker\Contracts\ProcessAssetsInterface;
use ProcessMaker\Models\Process;

abstract class ProcessAssets// implements ProcessAssetsInterface
{
    /**
     * Get references to export from a process
     *
     * @param Process $process
     * @param array $references
     *
     * @return array
     */
    //abstract public function referencesToExport(Process $process, array $references = []);

    /**
     * Update references when import as process
     *
     * @param Process $process
     * @param array $references
     * @return void
     */
    //abstract public function updatetReferences(Process $process, array $references = []);
}
