<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use ProcessMaker\Models\ProcessMakerModel;
use ProcessMaker\Models\User;

class Bookmark extends ProcessMakerModel
{
    use HasFactory;

    protected $connection = 'processmaker';

    protected $table = 'user_process_bookmarks';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'process_id',
    ];

    public static function rules(): array
    {
        return [
            'user_id' => 'required',
            'process_id' => 'required',
        ];
    }

    public function users()
    {
        return $this->hasMany(User::class, 'id');
    }
}
