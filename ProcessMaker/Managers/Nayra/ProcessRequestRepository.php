<?php

namespace ProcessMaker\Managers\Nayra;

use Exception;
use Illuminate\Database\Eloquent\Model;
use ProcessMaker\Events\ProcessCompleted;
use ProcessMaker\Models\ProcessRequest;

class ProcessRequestRepository extends EntityRepository
{
    private $uid2id = [];

    public function create(array $transaction): Model
    {
        $properties = $transaction['properties'];
        $request = ProcessRequest::create([
            'process_id' => $properties['process_id'],
            'data' => $properties['data'],
            'status' => $properties['status'],
            'user_id' => $properties['user_id'],
            'callable_id' => $properties['callable_id'],
            'name'  => $properties['name'],
            'process_version_id' => $properties['process_version_id'],
        ]);
        $this->storeUid($transaction['id'], $request->getKey());
        return $request;
    }

    public function update(array $transaction): Model
    {
        $id = $this->uid2id[$transaction['id']];
        if (!$id) {
            throw new Exception("Cannot find id for uid {$transaction['id']}");
        }
        $properties = $transaction['properties'];
        $model = ProcessRequest::find($id);
        $model->fill($properties);
        $model->save();
        $model->notifyProcessUpdated('ACTIVITY_ACTIVATED');
        if ($model->status === 'COMPLETED') {
            event(new ProcessCompleted($model));
        }
        return $model;
    }

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
