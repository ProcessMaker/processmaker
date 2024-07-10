<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use ProcessMaker\Models\ProcessMakerModel;
use ProcessMaker\Traits\HasUuids;

class ProcessAbeRequestToken extends ProcessMakerModel
{
    use HasFactory;
    use HasUuids;

    protected $connection = 'processmaker';

    protected $table = 'process_abe_request_tokens';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'process_id',
        'process_request_id',
        'process_request_token_id',
        'completed_screen_id',
        'data',
        'is_answered',
        'require_login',
        'answered_at'
    ];

    public static function rules(): array
    {
        return [
            'process_request_id' => 'required',
            'process_request_token_id' => 'required',
        ];
    }
}
