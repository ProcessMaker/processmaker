<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Model;
/**
 * Represents a webhook for a process and start event node
 *
 * @property string $id
 * @property integer $process_id
 * @property string $node
 * @property string $token
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $created_at
 *
 * @OA\Schema(
 *   schema="ProcessWebhookEditable",
 * ),
 * @OA\Schema(
 *   schema="ProcessWebhook",
 *   allOf={@OA\Schema(ref="#/components/schemas/ProcessWebhookEditable")},
 *   @OA\Property(property="id", type="integer", format="id"),
 *   @OA\Property(property="process_id", type="integer", format="id"),
 *   @OA\Property(property="node", type="string"),
 *   @OA\Property(property="token", type="string"),
 *   @OA\Property(property="created_at", type="string", format="date-time"),
 *   @OA\Property(property="updated_at", type="string", format="date-time"),
 * )
 */
class ProcessWebhook extends Model
{
    protected $fillable = [
        'process_id',
        'node',
        'token',
    ];

    public static function rules($existing = null)
    {
        return [
            'process_id' => 'exists:processes,id',
            'node' => 'required|string',
            'token' => 'required|string|unique',
        ];
    }

    public function process()
    {
        return $this->belongsTo(Process::class);
    }

    public function url()
    {
        return route('webhook.start_event', ['token' => $this->token]);
    }
}
