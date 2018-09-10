<?php

namespace ProcessMaker\Models;

use Laravel\Passport\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

use Spatie\BinaryUuid\HasBinaryUuid;

class User extends Authenticatable
{
    use HasApiTokens;
    use Notifiable;
    use HasBinaryUuid;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password', 'firstname', 'lastname', 'status', 'address', 'city', 'state', 'postal', 'country', 'phone', 'fax', 'cell', 'title', 'birthdate', 'timezone', 'language', 'expires_at',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    public function memberships()
    {
        return $this->morphMany('ProcessMaker\Models\GroupMember', 'member');
    }
        
}
