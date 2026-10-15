<?php

namespace ProcessMaker\Assets;

use DOMXPath;
use ProcessMaker\Models\Process;
use ProcessMaker\Providers\WorkflowServiceProvider;

class DataSourcesInProcess
{
    public $type = 'ProcessMaker\Packages\Connectors\DataSources\Models\DataSource';

    public $owner = Process::class;

    /**
     * Get the data sources used in a process
     *
     * @param Process $process
     * @param array $dataSources
     *
     * @return array
     */
    public function referencesToExport(Process $process, array $dataSources = [])
    {
        // Data Sources used in BPMN Service Tasks
        $xpath = new DOMXPath($process->getDefinitions());
        $xpath->registerNamespace('pm', WorkflowServiceProvider::PROCESS_MAKER_NS);
        
        // Find all nodes with pm:config
        $nodes = $xpath->query("//*[@pm:config!='']");
        foreach ($nodes as $node) {
            $configString = $node->getAttributeNS(WorkflowServiceProvider::PROCESS_MAKER_NS, 'config');
            $config = json_decode($configString, true);
            if (isset($config['dataSource']) && is_numeric($config['dataSource'])) {
                $dataSources[] = [$this->type, (int) $config['dataSource']];
            }
        }

        return $dataSources;
    }

    /**
     * Update references used in an imported process
     *
     * @param Process $process
     * @param array $references
     *
     * @return void
     */
    public function updateReferences(Process $process, array $references = [])
    {
        $definitions = $process->getDefinitions();
        $xpath = new DOMXPath($definitions);
        $xpath->registerNamespace('pm', WorkflowServiceProvider::PROCESS_MAKER_NS);

        $nodes = $xpath->query("//*[@pm:config!='']");
        foreach ($nodes as $node) {
            $configString = $node->getAttributeNS(WorkflowServiceProvider::PROCESS_MAKER_NS, 'config');
            $config = json_decode($configString, true);
            if (isset($config['dataSource']) && is_numeric($config['dataSource'])) {
                $oldRef = $config['dataSource'];
                if (isset($references[$this->type][$oldRef])) {
                    $newRef = $references[$this->type][$oldRef]->getKey();
                    $config['dataSource'] = $newRef;
                    $node->setAttributeNS(WorkflowServiceProvider::PROCESS_MAKER_NS, 'config', json_encode($config));
                }
            }
        }
        $process->bpmn = $definitions->saveXML();
        $process->save();
    }
}
