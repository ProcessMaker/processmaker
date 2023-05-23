<?php

namespace ProcessMaker\Events;

use Illuminate\Foundation\Events\Dispatchable;
use ProcessMaker\Contracts\SecurityLogEventInterface;

class AuthClientUpdated implements SecurityLogEventInterface
{
    use Dispatchable;

    public $original_values;
    public $changed_values;
    public $data;
    public $changes;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(array $original_values, array $changed_values)
    {
        $this->original_values = $original_values;
        $this->changed_values = $changed_values;
        $this->buildData();
    }

    /**
     * Building the data
     */
    public function buildData() {
        $this->data = [
            'Auth Client Id' => $this->original_values['id']
        ];

        if ($this->original_values['name'] !== $this->changed_values['name']) {
            $this->data['- name'] = $this->original_values['name'];
            $this->data['+ name'] = $this->changed_values['name'];
        }

        if ($this->original_values['revoked'] !== $this->changed_values['revoked']) {
            $this->data['- revoked'] = $this->original_values['revoked'];
            $this->data['+ revoked'] = $this->changed_values['revoked'];
        }

        if ($this->original_values['user_id'] !== $this->changed_values['user_id']) {
            $this->data['- user id'] = $this->original_values['user_id'];
            $this->data['+ user id'] = $this->changed_values['user_id'];
        }

        if ($this->original_values['provider'] !== $this->changed_values['provider']) {
            $this->data['- provider'] = $this->original_values['provider'];
            $this->data['+ provider'] = $this->changed_values['provider'];
        }

        if ($this->original_values['redirect'] !== $this->changed_values['redirect']) {
            $this->data['- redirect'] = $this->original_values['redirect'];
            $this->data['+ redirect'] = $this->changed_values['redirect'];
        }

        if($this->original_values['password_client']) {
            $this->original_values['password_client'] = true;
        } else {
            $this->original_values['password_client'] = false;
        }
        if ($this->original_values['password_client'] !== $this->changed_values['password_client']) {
            $this->data['- password client'] = $this->original_values['password_client'];
            $this->data['+ password client'] = $this->changed_values['password_client'];
        }

        if($this->original_values['personal_access_client']) {
            $this->original_values['personal_access_client'] = true;
        } else {
            $this->original_values['personal_access_client'] = false;
        }
        if ($this->original_values['personal_access_client'] !== $this->changed_values['personal_access_client']) {
            $this->data['- personal access client'] = $this->original_values['personal_access_client'];
            $this->data['+ personal access client'] = $this->changed_values['personal_access_client'];
        }
    }
    
    /**
     * Return event data 
     */
    public function getData(): array
    {
        return $this->data;
    }
    
    /**
     * Return event changes 
     */
    public function getChanges(): array
    {
        return $this->changes;
    }

    /**
     * return event name
     */
    public function getEventName(): string
    {
        return 'AuthClientUpdated';
    }
}
