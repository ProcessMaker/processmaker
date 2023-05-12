<?php

namespace ProcessMaker\Managers\Nayra;

use Exception;
use Illuminate\Database\Eloquent\Model;
use ProcessMaker\Models\ProcessRequestToken;
use Illuminate\Support\Facades\Log;
use ProcessMaker\Models\ProcessRequest;

class ProcessRequestTokenRepository extends EntityRepository
{

    public function save(array $transaction): ?Model {
        if ($transaction['type'] === 'create') {
            return $this->create($transaction);
        }
        if ($transaction['type'] === 'update') {
            return $this->update($transaction);
        }
    }

    public function create(array $transaction): ?Model
    {
        $properties = $transaction['properties'];
        if (isset($properties['request_id'])) {
            // $properties['request_id'] = $this->resolveId($properties['request_id']);
            $request = ProcessRequest::where('uuid', $properties['request_id'])->first();
            $properties['request_id'] = $request->getKey();
            if (!isset($properties['user_id'])) {
                $properties['user_id'] = $request->user_id;
            }
            if (!isset($properties['process_id'])) {
                $properties['process_id'] = $request->process_id;
            }
        }
        try {
            $token = ProcessRequestToken::create([
                'uuid' => $properties['id'],
                'user_id' => $properties['user_id'] ?? null,
                'process_id' => $properties['process_id'] ?? null,
                'process_request_id' => $properties['request_id'],
                'element_id' => $properties['element_id'],
                'element_name' => $properties['element_name'],
                'element_type' => $properties['element_type'],
                'status' => $properties['status'],
            ]);
            $this->storeUid($transaction['id'], $token->getKey());
            return $token;
        } catch (Exception $e) {
            Log::error("Cannot create token: {$e->getMessage()}");
            return null;
        }
    }

    public function update(array $transaction): ?Model
    {
        $id = $this->resolveId($transaction['id']);
        if (!$id) {
            throw new Exception("Cannot find id for uid {$transaction['id']}");
        }
        $properties = $transaction['properties'];
        $model = ProcessRequestToken::find($id);
        $model->fill($properties);
        $model->save();
        return $model;
    }
}
