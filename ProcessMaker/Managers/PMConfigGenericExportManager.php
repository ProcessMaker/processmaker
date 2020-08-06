<?php
namespace ProcessMaker\Managers;

use DOMXPath;
use ProcessMaker\Models\Process;
use ProcessMaker\Providers\WorkflowServiceProvider;

class PMConfigGenericExportManager
{
    private $tag, $class, $keys;

    public function __construct(string $tag, string $class, array $keys)
    {
        $this->tag = $tag;
        $this->class = $class;
        $this->keys = $keys;
    }

    /**
     * Get screens references used in a process
     *
     * @param Process $process
     * @param array $screens
     *
     * @return array
     */
    public function referencesToExport(Process $process, array $references = [])
    {
        $xpath = new DOMXPath($process->getDefinitions());
        $xpath->registerNamespace('pm', WorkflowServiceProvider::PROCESS_MAKER_NS);

        // Used in config
        $nodes = $xpath->query("//{$this->tag}[@pm:config!='']");
        foreach ($nodes as $node) {
            $config = json_decode($node->getAttributeNS(WorkflowServiceProvider::PROCESS_MAKER_NS, 'config'));
            foreach ($this->keys as $key) {
                if (!isset($config->$key)) {
                    continue;
                }

                if (!is_numeric($config->$key)) {
                    continue;
                }
                
                $references[] = [$this->class, $config->$key];
            }
        }

        return $references;
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

        // Used in config
        $nodes = $xpath->query("//{$this->tag}[@pm:config!='']");
        foreach ($nodes as $node) {
            $config = json_decode($node->getAttributeNS(WorkflowServiceProvider::PROCESS_MAKER_NS, 'config'));
            foreach ($this->keys as $key) {
                if (!isset($config->$key)) {
                    continue;
                }

                if (!is_numeric($config->$key)) {
                    continue;
                }

                $oldRef = $config->$key;
                $newRef = $references[$this->class][$oldRef]->getKey();
                $config->$key = $newRef;
            }
            $node->setAttributeNS(WorkflowServiceProvider::PROCESS_MAKER_NS, 'config', json_encode($config));
        }

        $process->bpmn = $definitions->saveXML();
        $process->save();
    }
}