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
      'username',
      'email',
      'password',
      'firstname',
      'lastname',
      'status',
      'address',
      'city',
      'state',
      'postal',
      'country',
      'phone',
      'fax',
      'cell',
      'title',
      'birthdate',
      'timezone',
      'language',
      'expires_at'

    ];

    /**
     * Validation rules
     *
     * @param $existing
     *
     * @return array
     */
    public static function rules($existing = null)
    {
        $rules = [
            'username' => 'required|unique:users,username',
            'email' => 'required|email',
        ];
        if ($existing) {
            // ignore the unique rule for this id
            $rules['username'] .= ',' . $existing->uuid . ',uuid';
        }
        return $rules;
    }

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
