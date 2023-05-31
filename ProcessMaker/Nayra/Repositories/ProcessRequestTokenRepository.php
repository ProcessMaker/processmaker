<?php

namespace ProcessMaker\Nayra\Repositories;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;

class ProcessRequestTokenRepository extends EntityRepository
{
    /**
     * Create a new request token
     *
     * @param array $transaction
     * @return \ProcessMaker\Models\ProcessRequestToken
     */
    public function create(array $transaction): ? Model
    {
        // Create auxiliar variable
        $properties = $transaction['properties'];

        // Get required values if not are in the response
        if (isset($properties['request_id'])) {
            // Get the current request
            $request = ProcessRequest::where('uuid', $properties['request_id'])->first();

            // Overrides the request id with the correct value
            $properties['request_id'] = $request->getKey();

            // Complete missing values
            if (!isset($properties['user_id'])) {
                $properties['user_id'] = $request->user_id;
            }
            if (!isset($properties['process_id'])) {
                $properties['process_id'] = $request->process_id;
            }
        }
        try {
            // Create new request token
            $token = ProcessRequestToken::create([
                'uuid' => $properties['id'],
                'user_id' => $properties['user_id'] ?? null,
                'process_id' => $properties['process_id'] ?? null,
                'process_request_id' => $properties['request_id'],
                'element_id' => $properties['element_id'],
                'element_name' => $properties['element_name'],
                'element_type' => $properties['element_type'],
                'status' => $properties['status'],
                // TO DO:
                //'due_at' => '',
                //'riskchanges_at' => '',
                'self_service_groups' => [],
                'token_properties' => [],
            ]);

            return $token;
        } catch (Exception $e) {
            // Log the error
            Log::error("Cannot create token: {$e->getMessage()}");
            return null;
        }
    }

    /**
     * Update a request token
     *
     * @param array $transaction
     * @return \ProcessMaker\Models\ProcessRequestToken
     */
    public function update(array $transaction): ? Model
    {
        // Update the request token
        $properties = $transaction['properties'];
        $model = ProcessRequestToken::find($transaction['id']);
        $model->fill($properties);
        $model->save();

        return $model;
    }

    /**
     * Save the request token
     *
     * @param array $transaction
     * @return \ProcessMaker\Models\ProcessRequestToken
     */
    public function save(array $transaction): ? Model {
        if ($transaction['type'] === 'create') {
            return $this->create($transaction);
        }
        if ($transaction['type'] === 'update') {
            return $this->update($transaction);
        }
    }
}
