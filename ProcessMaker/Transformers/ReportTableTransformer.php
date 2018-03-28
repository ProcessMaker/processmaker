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
            'rep_tab_uid' => $report->ADD_TAB_UID,
            'rep_tab_name' => $report->ADD_TAB_NAME,
            'rep_tab_description' => $report->ADD_TAB_DESCRIPTION,
            'rep_tab_plg_uid' => $report->ADD_TAB_PLG_UID,
            'rep_tab_connection' => $report->DBS_UID,
            'pro_uid' => $report->PRO_UID,
            'rep_tab_type' => $report->ADD_TAB_TYPE,
            'rep_tab_grid' => $report->ADD_TAB_GRID,
            'rep_tab_tag' => $report->ADD_TAB_TAG
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
