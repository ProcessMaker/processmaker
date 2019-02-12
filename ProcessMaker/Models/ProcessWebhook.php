<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Model;
// use Illuminate\Validation\Rule;
// use ProcessMaker\Models\Process;
// use ProcessMaker\Traits\SerializeToIso8601;

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
