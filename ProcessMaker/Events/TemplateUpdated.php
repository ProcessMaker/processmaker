<?php

namespace ProcessMaker\Events;

use Carbon\Carbon;
use Illuminate\Foundation\Events\Dispatchable;
use ProcessMaker\Contracts\SecurityLogEventInterface;
use ProcessMaker\Models\ProcessTemplates;
use ProcessMaker\Traits\FormatSecurityLogChanges;

class TemplateUpdated implements SecurityLogEventInterface
{
    use Dispatchable, FormatSecurityLogChanges;

    private $changes;
    private $process;
    private $processType;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(array $changes, $process = null, $processType)
    {   
        $this->changes = $changes;
        $this->process = $process;
        $this->processType = $processType;
    }

    /**
     * Get specific data related to the event
     *
     * @return array
     */
    public function getData(): array
    {
        if ($this->processType == "Update Template Process") {
            //for process Changes
            $old_template_data = array_intersect_key($this->process->getOriginal(), array_flip(['updated_at']));
            $old_template_data['updated_at'] = date('Y-m-d H:i:s', strtotime($old_template_data['updated_at']));

            return [
                'name' => [
                    'label' => $this->processType
                ],
                '+ Process/Template_Last_Modified' => Carbon::now()
            ];
        } else {
            $queryOldtemplate = ProcessTemplates::select('id', 'name', 'description', 'process_category_id')
            ->where('id', $this->changes['id'])
                ->get()->first()->toArray();

            return array_merge([
                'name' => [
                    'label' => $this->processType
                ],
            ], $this->formatChanges($this->changes, $queryOldtemplate));
        }
    }

    /**
     * Get the Event name with the syntax ‘[Past-test Action] [Object]’
     *
     * @return string
     */
    public function getEventName(): string
    {
        return 'TemplateUpdated';
    }

    /**
     * Get specific changes without format related to the event
     *
     * @return array
     */
    public function getChanges(): array
    {
        return $this->changes;
    }
}
