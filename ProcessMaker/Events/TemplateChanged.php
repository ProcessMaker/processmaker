<?php

namespace ProcessMaker\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Request;
use Illuminate\Queue\SerializesModels;
use ProcessMaker\Contracts\SecurityLogEventInterface;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessPermission;
use ProcessMaker\Models\ProcessTemplates;

class TemplateChanged implements SecurityLogEventInterface
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private Request $request;
    private $process;
    //private Process $process;
    private $processType;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Request $request,$process = null,$processType)
    {   
        $this->request = $request;
        $this->process = $process;
        $this->processType = $processType;
    }

    public function getData(): array
    {   
        if($this->processType == "updateProcess"){
            //for process Changes
            $old_template_data= array_intersect_key($this->process->getOriginal(), array_flip(['updated_at']));
            $old_template_data['updated_at'] = date('Y-m-d H:i:s', strtotime($old_template_data['updated_at']));
          
            
             return $old_template_data;  
            
        }else{
            //For config Changes
            $queryOldtemplate= ProcessTemplates::select('id', 'name', 'process_category_id','created_at','updated_at')
            ->where('id', $this->request['id'])
            ->get()->toArray();

            return [
                '+ name' => $this->request['name'],
                '+ process_category_id' => $this->request['process_category_id'],
                '+ description' => $this->request['description'],
                '- name' => $queryOldtemplate[0]['name'],
                '- process_category_id' => $queryOldtemplate[0]['process_category_id']
            ];
            
        }

    }

    public function getEventName(): string
    {
        return 'TemplateChanged';
    }

    public function getChanges(): array
    {
        // return $this->changes;
        return array($this->request['id'],$this->request['name'],$this->request['process_category_id'],$this->request['description']);
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}
