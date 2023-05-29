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

    private array $changes;
    private string $processType;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(array $changes, string $processType)
    {   
        $this->changes = $changes;
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
            return [
                'name' => [
                    'label' => $this->processType
                ],
                'updated_at' => Carbon::now()
            ];
        } else {
            $queryOldtemplate = ProcessTemplates::select('id', 'name', 'description', 'process_category_id')
            ->where('id', $this->changes['id'])
                ->get()->first()->toArray();

            $old_data = array_diff_assoc($queryOldtemplate, $this->changes);
            $new_data = array_diff_assoc($this->changes, $queryOldtemplate);

            return array_merge([
                'name' => [
                    'label' => $this->processType
                ],
            ], $this->formatChanges($new_data, $old_data));
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
