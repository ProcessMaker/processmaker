<?php

namespace ProcessMaker\Nayra\Repositories;

use Exception;
use Illuminate\Database\Eloquent\Model;
use ProcessMaker\Events\ProcessCompleted;
use ProcessMaker\Models\ProcessRequest;

class ProcessRequestRepository extends EntityRepository
{
    /**
     * Create a new request
     *
     * @param array $transaction
     * @return \ProcessMaker\Models\ProcessRequest
     */
    public function create(array $transaction): Model
    {
        // Create auxiliar variable
        $properties = $transaction['properties'];

        // Create new request
        $request = ProcessRequest::create([
            'process_id' => $properties['process_id'],
            'data' => $properties['data'],
            'status' => $properties['status'],
            'user_id' => $properties['user_id'],
            'callable_id' => $properties['callable_id'],
            'name'  => $properties['name'],
            'process_version_id' => $properties['process_version_id'],
        ]);

        return $request;
    }

    /**
     * Update a request
     *
     * @param array $transaction
     * @return \ProcessMaker\Models\ProcessRequest
     */
    public function update(array $transaction): Model
    {
        // Update the request
        $properties = $transaction['properties'];
        $model = ProcessRequest::find($transaction['id']);
        $model->fill($properties);
        $model->save();

        // Notify to engine
        $model->notifyProcessUpdated('ACTIVITY_ACTIVATED');

        // Trigger event
        if ($model->status === 'COMPLETED') {
            event(new ProcessCompleted($model));
        }

        return $model;
    }

    /**
     * Save the request
     *
     * @param array $transaction
     * @return \ProcessMaker\Models\ProcessRequest
     */
    public function save(array $transaction): Model
    {
        if ($transaction['type'] === 'create') {
            return $this->create($transaction);
        }
        if ($transaction['type'] === 'update') {
            return $this->update($transaction);
        }
    }
}
