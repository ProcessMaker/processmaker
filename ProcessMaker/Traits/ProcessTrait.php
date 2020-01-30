<?php

namespace ProcessMaker\Traits;

use ProcessMaker\Models\ProcessVersion;
use ProcessMaker\Nayra\Contracts\Storage\BpmnDocumentInterface;
use ProcessMaker\Nayra\Storage\BpmnDocument;

trait ProcessTrait
{
    /**
     * Parsed process BPMN definitions.
     *
     * @var \ProcessMaker\Nayra\Contracts\Storage\BpmnDocumentInterface
     */
    private $bpmnDefinitions;

    /**
     * Get the process definitions from BPMN field.
     *
     * @param bool $forceParse
     *
     * @return \ProcessMaker\Nayra\Contracts\Storage\BpmnDocumentInterface
     */
    public function getDefinitions($forceParse = false, $engine = null)
    {
        if ($forceParse || empty($this->bpmnDefinitions)) {
            $options = ['process' => $this instanceof ProcessVersion ? $this->process : $this];
            !$engine ?: $options['engine'] = $engine;
            $this->bpmnDefinitions = app(BpmnDocumentInterface::class, $options);
            if ($this->bpmn) {
                $this->bpmnDefinitions->loadXML($this->bpmn);
                //Load the collaborations if exists
                $collaborations = $this->bpmnDefinitions->getElementsByTagNameNS(BpmnDocument::BPMN_MODEL, 'collaboration');
                foreach ($collaborations as $collaboration) {
                    try {
                        $collaboration->getBpmnElementInstance();
                    } catch (\ProcessMaker\Nayra\Exceptions\ElementNotFoundException $e) {
                        if (is_array($this->warnings)) {
                            $warnings = $this->warnings;
                        } else {
                            $warnings = [];
                        }

                        $warnings[] = [
                            'title' => __('Element Not Found'),
                            'text' => $e->getMessage()
                        ];
                        $this->warnings = $warnings;
                    }
                }
            }
        }
        return $this->bpmnDefinitions;
    }
}
