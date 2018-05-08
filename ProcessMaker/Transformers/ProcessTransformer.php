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
            "prj_uid"                => $process->uid,
            "prj_name"               => $process->name,
            "prj_description"        => $process->description,
            "prj_target_namespace"   => $process->target_namespace,
            "prj_expresion_language" => $process->expresion_language,
            "prj_type_language"      => $process->type_language,
            "prj_exporter"           => $process->exporter,
            "prj_exporter_version"   => $process->exporter_version,
            "prj_create_date"        => $process->created_at->toIso8601String(),
            "prj_update_date"        => $process->updated_at->toIso8601String(),
            "prj_author"             => $process->author,
            "prj_author_version"     => $process->author_version,
            "prj_original_source"    => $process->original_source,
            'prj_category'           => $process->category ? $process->category->name : null,
            'prj_type'               => $process->type,
            'prj_status'             => $process->status,
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
