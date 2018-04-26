<?php

namespace ProcessMaker\Transformers;

use League\Fractal\TransformerAbstract;
use ProcessMaker\Model\Process;

/**
 * Process transformer, used to prepare the JSON response returned in the
 * designer's endpoints.
 *
 * @package ProcessMaker\Transformer
 */
class ProcessTransformer extends TransformerAbstract
{
    /**
     * List of resources possible to include
     *
     * @var array
     */
    protected $defaultIncludes = [
        'diagram'
    ];

    /**
     * Fields that will be returned by the transformer.
     *
     * @var array $fields
     */
    private $fields;

    public function __construct(array $fields = null)
    {
        $this->fields = $fields;
    }

    /**
     * Transform the process.
     *
     * @param Process $process
     *
     * @return array
     */
    public function transform(Process $process)
    {
        return $this->transformWithFieldFilter([
            "prj_uid"                => $process->PRO_UID,
            "prj_name"               => $process->PRO_NAME,
            "prj_description"        => $process->PRO_DESCRIPTION,
            "prj_target_namespace"   => $process->PRO_TARGET_NAMESPACE,
            "prj_expresion_language" => $process->PRO_EXPRESION_LANGUAGE,
            "prj_type_language"      => $process->PRO_TYPE_LANGUAGE,
            "prj_exporter"           => $process->PRO_EXPORTER,
            "prj_exporter_version"   => $process->PRO_EXPORTER_VERSION,
            "prj_create_date"        => $process->PRO_CREATE_DATE->toIso8601String(),
            "prj_update_date"        => $process->PRO_UPDATE_DATE->toIso8601String(),
            "prj_author"             => $process->PRO_AUTHOR,
            "prj_author_version"     => $process->PRO_AUTHOR_VERSION,
            "prj_original_source"    => $process->PRO_ORIGINAL_SOURCE,
            'prj_category'           => $process->PRO_CATEGORY,
            'prj_type'               => $process->PRO_TYPE,
            'prj_status'             => $process->PRO_STATUS,
        ]);
    }

    /**
     * Includes the process.
     *
     * @param Process $process
     *
     * @return \League\Fractal\Resource\Item
     */
    public function includeDiagram(Process $process)
    {
        return $this->item($process->diagram, new DiagramTransformer);
    }

    /**
     *
     * @param array $data
     *
     * @return array
     */
    protected function transformWithFieldFilter(array $data)
    {
        if (is_null($this->fields)) {
            return $data;
        }

        return array_intersect_key($data, array_flip((array) $this->fields));
    }
}
