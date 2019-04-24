<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Model;
/**
 * Represents a web entry a process and start event node
 *
 * @property string $id
 * @property integer $process_id
 * @property string $node
 * @property string $token
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $created_at
 *
 * @OA\Schema(
 *   schema="ProcessWebEntryEditable",
 * ),
 * @OA\Schema(
 *   schema="ProcessWebEntry",
 *   allOf={@OA\Schema(ref="#/components/schemas/ProcessWebEntryEditable")},
 *   @OA\Property(property="id", type="integer", format="id"),
 *   @OA\Property(property="process_id", type="integer", format="id"),
 *   @OA\Property(property="node", type="string"),
 *   @OA\Property(property="mode", type="string"),
 *   @OA\Property(property="completed_action", type="string"),
 *   @OA\Property(property="completed_screen_id", type="integer", format="id"),
 *   @OA\Property(property="completed_url", type="string"),
 *   @OA\Property(property="created_at", type="string", format="date-time"),
 *   @OA\Property(property="updated_at", type="string", format="date-time"),
 * )
 */
class ProcessWebEntry extends Model
{
    protected $fillable = [
        'process_id',
        'node',
        'mode',
        'completed_action',
        'completed_screen_id',
        'completed_url',
    ];

    public static function rules($existing = null)
    {
        return [
            'process_id' => 'exists:processes,id',
            'node' => 'required|string',
            'mode' => 'required|in:AUTHENTICATED,ANONYMOUS|nullable',
            'completed_action' => 'required|in:SCREEN,URL',
            'completed_screen_id' => 'exists:screen,id|nullable',
            'completed_url' => 'string|nullable',
        ];
    }

    public function process()
    {
        return $this->belongsTo(Process::class);
    }

    public function url()
    {
        return route('web_entry.start_event', ['token' => $this->token]);
    }
}
