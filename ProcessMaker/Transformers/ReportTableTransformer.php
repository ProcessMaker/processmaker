<?php
namespace ProcessMaker\Transformers;

use League\Fractal\Resource\Collection;
use League\Fractal\TransformerAbstract;
use ProcessMaker\Model\DbSource;
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
            'connection' => $report->db_source_id ? DbSource::where('id', $report->db_source_id)->first()->uid : 'workflow',
            'description' => $report->description,
            'process' => $report->process->uid,
            'type' => $report->type,
            'grid' => $report->grid,
            'tag' => $report->tag
        ];
    }

    /**
     * Fractal include to add the fields of the report table in the transformation
     *
     * @param ReportTable $report
     *
     * @return Collection
     */
    public function includeFields(ReportTable $report): Collection
    {
        return $this->collection($report->fields, new PmTableColumnTransformer(), false);
    }
}
