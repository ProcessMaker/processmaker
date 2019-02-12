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
        'node'
    ];

    public static function rules($existing = null)
    {
        return [
            'process_id' => 'exists:processes,id',
            'node' => 'required|string',
        ];
    }

    public function process()
    {
        return $this->belongsTo(Process::class);
    }
}
