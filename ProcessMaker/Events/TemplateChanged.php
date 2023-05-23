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
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessPermission;

class TemplateChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private Request $request;
    //private Process $process;
    private $processType;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($request,$processType)
    {   dd($request->all());
        $this->request = $request;
        //$this->process = $process;
        $this->processType = $processType;
    }

    public function getData(): array
    {dd($this->request);
        if($this->processType == "updateProcess"){
            //for process Changes
            //$old_template_data= array_intersect_key($this->process->getOriginal(), array_flip(['updated_at']));
            //$old_template_data['updated_at'] = date('Y-m-d H:i:s', strtotime($old_template_data['updated_at']));
            
            return [
                //'modified_at' => $old_template_data  
            ];
        }else{
            //For config Changes
            dd($this->request);
            /*return [
                '+ id' => $this->process->getAttribute('id'),
                '+ name' => $this->process->getAttribute('name'),
                '+ process_category_id' => $this->process->getAttribute('process_category_id'),
                '+ description' => $this->process->getAttribute('description'),
                '- id' => $this->process->getOriginal()['id'],
                '- name' => $this->process->getOriginal()['name'],
                '- process_category_id' => $this->process->getOriginal()['process_category_id'],
                '- description' => $this->process->getOriginal()['description']
            ];*/
            
        }
        
        /*$new_template_data = array_intersect_key($this->process->getAttributes(), array_flip(['id,','process_category_id', 'description','name','created_at','updated_at']));
        $new_template_data['created_at'] = date('Y-m-d H:i:s', strtotime($new_template_data['created_at']));
        $new_template_data['updated_at'] = date('Y-m-d H:i:s', strtotime($new_template_data['updated_at']));*/

      
        

    }

    public function getEventName(): string
    {
        return 'TemplateChanged';
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
