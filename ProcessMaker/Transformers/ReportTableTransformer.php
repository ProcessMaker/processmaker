<?php
namespace ProcessMaker\Transformers;

use League\Fractal\TransformerAbstract;
use ProcessMaker\Model\ReportTable;

/**
 * Transformer to convert a report table to the endpoint return format
 */
class ReportTableTransformer extends TransformerAbstract
{
    protected $defaultIncludes = ['fields'];

    public function transform(ReportTable $report)
    {
        return [
            'uid' => $report->uid,
            'name' => $report->name,
            'description' => $report->description,
            'connection' => $report->dbSource->uid,
            'process' => $report->process->uid,
            'type' => $report->type,
            'grid' => $report->grid,
            'tag' => $report->tag
        ];
    }

    /**
     * Fractal includer to add the fields of the report table in the transformation
     *
     * @param ReportTable $report
     * @return \League\Fractal\Resource\Collection
     */
    public function includeFields(ReportTable $report)
    {
        return $this->collection($report->fields, new PmTableColumnTransformer());
    }
}
